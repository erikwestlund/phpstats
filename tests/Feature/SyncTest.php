<?php

namespace Tests\Feature;

use App\Console\Commands\SyncSiteContent;
use App\Content\Example;
use App\Content\Package;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Tests\TestCase;

class SyncTest extends TestCase
{
    /** A package key of its own, so a test never writes over real synced content. */
    private const PACKAGE = 'fixturepkg';

    private string $source;

    private string $content;

    private string $realContent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->source = $this->makeCheckout();
        $this->realContent = config('phpstats.content_path');
        $this->content = base_path('storage/framework/testing/content-'.uniqid());
        File::ensureDirectoryExists($this->content);
        config(['phpstats.content_path' => $this->content]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->source);
        File::deleteDirectory($this->content);
        // public/content is not configurable, so the fixture's copy is removed by hand.
        File::deleteDirectory(public_path('content/'.self::PACKAGE));
        Example::reset();

        parent::tearDown();
    }

    /** A checkout shaped like a package's: site/ content and rendered Quarto docs. */
    private function makeCheckout(?array $nav = null): string
    {
        $root = base_path('storage/framework/testing/checkout-'.uniqid());
        $nav ??= [
            ['title' => 'Overview', 'path' => 'index.qmd'],
            ['title' => 'Advanced', 'path' => 'guide/advanced.qmd', 'section' => 'Reference'],
        ];

        File::ensureDirectoryExists("{$root}/site/benchmarks");
        File::ensureDirectoryExists("{$root}/site/verifications");
        File::ensureDirectoryExists("{$root}/site/vignettes");
        File::ensureDirectoryExists("{$root}/site/data");
        File::ensureDirectoryExists("{$root}/docs/guide");
        File::ensureDirectoryExists("{$root}/docs/images");

        File::put("{$root}/site/site.json", json_encode([
            'package' => self::PACKAGE,
            'title' => self::PACKAGE,
            'tagline' => 'A fixture package.',
            'repository' => 'https://github.com/phpstats/'.self::PACKAGE,
            'docs' => ['root' => 'docs', 'nav' => $nav],
            'benchmarks' => ['site/benchmarks/one.json'],
            'verifications' => ['site/verifications/one.json'],
            'vignettes' => [['slug' => 'demo', 'title' => 'Demo', 'script' => 'site/vignettes/demo.php']],
        ]));
        File::put("{$root}/site/benchmarks/one.json", json_encode(['title' => 'A benchmark', 'cases' => []]));
        File::put("{$root}/site/verifications/one.json", json_encode(['title' => 'A verification', 'cases' => []]));
        File::put("{$root}/site/vignettes/demo.php", "<?php\n\nreturn 1;\n");
        File::put("{$root}/site/data/sample.csv", "a,b\n1,2\n");
        File::put("{$root}/docs/images/fig.png", 'not-really-a-png');
        File::put("{$root}/docs/index.html", $this->page('Overview', '<img src="images/fig.png" alt="A figure">'));
        File::put("{$root}/docs/index.qmd", "---\ntitle: Overview\n---\n");
        File::put("{$root}/docs/guide/advanced.html", $this->page('Advanced', '<p>More.</p>'));
        File::put("{$root}/docs/guide/advanced.qmd", "---\ntitle: Advanced\n---\n");

        return $root;
    }

    private function page(string $title, string $body): string
    {
        return <<<HTML
            <!DOCTYPE html><html><head><title>{$title}</title></head><body>
            <main id="quarto-document-content"><h1 class="title">{$title}</h1>{$body}</main>
            </body></html>
            HTML;
    }

    /** Every file written under a directory, with its contents, for comparing two runs. */
    private function tree(string $directory): array
    {
        if (! is_dir($directory)) {
            return [];
        }
        $files = [];
        foreach (File::allFiles($directory) as $file) {
            $files[str_replace('\\', '/', $file->getRelativePathname())] = $file->getContents();
        }
        ksort($files);

        return $files;
    }

    private function syncFromPath(array $parameters = []): int
    {
        return $this->artisan('site:sync', [
            'package' => [self::PACKAGE],
            '--from' => $this->source,
            ...$parameters,
        ])->run();
    }

    public function test_a_local_sync_writes_the_same_content_as_a_pinned_one(): void
    {
        // Both paths hand a checkout to the one import method, so this compares
        // the pinned provenance against --from over the same files. The clone
        // itself is not exercised: it needs the network.
        $import = new \ReflectionMethod(SyncSiteContent::class, 'import');
        $command = $this->app->make(SyncSiteContent::class);
        $command->setLaravel($this->app);
        $command->setOutput(new OutputStyle(
            new ArrayInput([]),
            new NullOutput,
        ));
        $import->invoke($command, self::PACKAGE, 'phpstats/'.self::PACKAGE, $this->source, [
            'repo' => 'phpstats/'.self::PACKAGE,
            'commit' => str_repeat('a', 40),
        ]);
        $pinnedContent = $this->tree($this->content);
        $pinnedAssets = $this->tree(public_path('content/'.self::PACKAGE));

        File::deleteDirectory($this->content);
        File::deleteDirectory(public_path('content/'.self::PACKAGE));
        $this->assertSame(0, $this->syncFromPath());

        $localContent = $this->tree($this->content);
        $localAssets = $this->tree(public_path('content/'.self::PACKAGE));

        // sync.json is the one file that must differ: it records the source.
        $provenance = self::PACKAGE.'/sync.json';
        $this->assertSame(array_keys($pinnedContent), array_keys($localContent));
        $this->assertSame($pinnedAssets, $localAssets);
        $this->assertArrayHasKey($provenance, $pinnedContent);
        foreach ($pinnedContent as $path => $contents) {
            if ($path === $provenance) {
                continue;
            }
            $this->assertSame($contents, $localContent[$path], $path);
        }
        $this->assertNotSame($pinnedContent[$provenance], $localContent[$provenance]);
    }

    public function test_a_local_sync_marks_sync_json_local_and_writes_no_pin(): void
    {
        $config = config_path('phpstats.php');
        $before = File::get($config);

        $this->assertSame(0, $this->syncFromPath());

        $sync = json_decode(File::get("{$this->content}/".self::PACKAGE.'/sync.json'), true);
        $this->assertTrue($sync['local']);
        $this->assertSame($this->source, $sync['source']);
        $this->assertArrayNotHasKey('commit', $sync);
        $this->assertSame('phpstats/'.self::PACKAGE, $sync['repo']);
        $this->assertSame($before, File::get($config), 'a local sync must not pin anything');
    }

    public function test_a_local_sync_copies_site_files_and_imports_the_docs(): void
    {
        $this->assertSame(0, $this->syncFromPath());
        $target = "{$this->content}/".self::PACKAGE;

        $this->assertFileExists("{$target}/benchmarks/one.json");
        $this->assertFileExists("{$target}/verifications/one.json");
        $this->assertFileExists("{$target}/vignettes/demo.php");
        $this->assertFileExists("{$target}/data/sample.csv");
        $this->assertFileExists("{$target}/docs/index.html");
        $this->assertFileExists("{$target}/docs/guide/advanced.html");
        $this->assertFileExists(public_path('content/'.self::PACKAGE.'/docs/images/fig.png'));
        $this->assertStringContainsString('A figure', File::get("{$target}/docs/index.html"));
    }

    public function test_the_nav_keeps_each_item_section(): void
    {
        $this->assertSame(0, $this->syncFromPath());

        $docs = json_decode(File::get("{$this->content}/".self::PACKAGE.'/docs.json'), true);

        $this->assertSame([
            ['title' => 'Overview', 'path' => 'index'],
            ['title' => 'Advanced', 'path' => 'guide/advanced', 'section' => 'Reference'],
        ], $docs['nav']);
    }

    public function test_a_nav_without_sections_gains_no_section_key(): void
    {
        File::deleteDirectory($this->source);
        $this->source = $this->makeCheckout([
            ['title' => 'Overview', 'path' => 'index.qmd'],
            ['title' => 'Advanced', 'path' => 'guide/advanced.qmd'],
        ]);

        $this->assertSame(0, $this->syncFromPath());

        $docs = json_decode(File::get("{$this->content}/".self::PACKAGE.'/docs.json'), true);

        $this->assertSame([
            ['title' => 'Overview', 'path' => 'index'],
            ['title' => 'Advanced', 'path' => 'guide/advanced'],
        ], $docs['nav']);
    }

    public function test_a_sync_flushes_the_example_cache(): void
    {
        config(['app.debug' => false]);
        $render = fn (mixed $value) => 'rendered';
        $calls = 0;
        $read = function () use (&$calls) {
            $calls++;

            return 'value';
        };

        Example::output('orders/read', 'sync-test', $render, $read);
        Example::reset();
        Example::output('orders/read', 'sync-test', $render, $read);
        $this->assertSame(1, $calls, 'the fragment should be cached before the sync');

        $this->assertSame(0, $this->syncFromPath());

        Example::reset();
        Example::output('orders/read', 'sync-test', $render, $read);
        $this->assertSame(2, $calls, 'the sync should have flushed the fragment');
    }

    public function test_from_is_rejected_with_update(): void
    {
        $this->artisan('site:sync', ['package' => [self::PACKAGE], '--from' => $this->source, '--update' => true])
            ->expectsOutputToContain('use it without --update')
            ->assertFailed();

        $this->assertSame([], $this->tree($this->content));
    }

    public function test_from_is_rejected_without_exactly_one_package(): void
    {
        $this->artisan('site:sync', ['--from' => $this->source])
            ->expectsOutputToContain('name exactly one')
            ->assertFailed();

        $this->artisan('site:sync', ['package' => ['phit', 'phplyr'], '--from' => $this->source])
            ->expectsOutputToContain('name exactly one')
            ->assertFailed();

        $this->assertSame([], $this->tree($this->content));
    }

    public function test_from_is_rejected_when_the_path_is_not_a_directory(): void
    {
        $this->artisan('site:sync', ['package' => [self::PACKAGE], '--from' => $this->source.'/nope'])
            ->expectsOutputToContain('is not a directory')
            ->assertFailed();
    }

    public function test_an_unknown_package_without_from_still_fails(): void
    {
        $this->artisan('site:sync', ['package' => [self::PACKAGE]])
            ->expectsOutputToContain('has no content entry')
            ->assertFailed();
    }

    /**
     * Write synced content for a real, routable package straight into the
     * content path, so a page can be rendered. phibble is not synced in this
     * repository, so nothing real is shadowed.
     */
    private function localContent(string $key): void
    {
        $target = "{$this->content}/{$key}";
        File::ensureDirectoryExists("{$target}/vignettes");
        File::put("{$target}/site.json", json_encode([
            'package' => $key,
            'title' => $key,
            'tagline' => 'Synced from a local checkout.',
            // A string, not a list: Package::summary() mishandles a list, which is task 11a's.
            'summary' => 'One paragraph of summary.',
            'repository' => "https://github.com/phpstats/{$key}",
            'vignettes' => [[
                'slug' => 'demo',
                'title' => 'A demo vignette',
                'summary' => 'Gated on an extension this server does not load.',
                'uses' => ['php_polars'],
                'script' => 'site/vignettes/demo.php',
            ]],
        ]));
        File::put("{$target}/vignettes/demo.php", <<<'PHP'
            <?php

            return [
                'inputs' => [],
                'run' => fn (array $input, string $data): array => ['text' => 'ran'],
            ];
            PHP);
        File::put("{$target}/sync.json", json_encode([
            'repo' => "phpstats/{$key}",
            'local' => true,
            'source' => $this->source,
            'synced_at' => now()->toIso8601String(),
        ]));

        Package::flush();
    }

    public function test_a_locally_synced_package_renders_its_overview_and_vignette(): void
    {
        $this->localContent('phibble');
        $package = Package::find('phibble');
        $this->assertTrue($package->sync()['local']);

        // Both pages print where the content came from, and neither reads a
        // commit that a local sync does not have.
        $this->get($package->url())->assertOk()
            ->assertSee('Content from a local checkout of')
            ->assertSee('phpstats/phibble')
            ->assertDontSee('/tree/', false);

        $this->get($package->url('vignette', ['slug' => 'demo']))->assertOk()
            ->assertSee('A demo vignette')
            ->assertSee('in a local checkout of')
            ->assertDontSee('/blob/', false);
    }

    /** A pinned sync still links the commit on both pages. */
    public function test_a_pinned_package_still_links_its_commit(): void
    {
        config(['phpstats.content_path' => $this->realContent]);
        Package::flush();
        $package = Package::find('phplyr');
        $commit = $package->sync()['commit'];

        $this->get($package->url())->assertOk()
            ->assertSee("/tree/{$commit}", false)
            ->assertDontSee('local checkout');
    }

    /** The pinned path still clones, sparse-checks out, and reads the commit. */
    public function test_a_pinned_sync_runs_the_same_git_commands(): void
    {
        Process::fake();

        try {
            $this->artisan('site:sync', ['package' => ['phit']])->run();
        } catch (\Throwable) {
            // The faked clone leaves no files, so reading site.json fails after
            // the git steps this test is about.
        }

        Process::assertRan(fn ($process) => $process->command === [
            'git', 'clone', '--quiet', '--filter=blob:none', '--no-checkout',
            'https://github.com/phpstats/phit.git', storage_path('app/site-sync/phit'),
        ]);
        Process::assertRan(fn ($process) => $process->command === ['git', 'sparse-checkout', 'set', '--no-cone', '/site/', '/docs/']);
        Process::assertRan(fn ($process) => $process->command === ['git', 'checkout', '--quiet', config('phpstats.content.phit.ref')]);
        Process::assertRan(fn ($process) => $process->command === ['git', 'rev-parse', 'HEAD']);
    }
}
