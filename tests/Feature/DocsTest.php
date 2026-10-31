<?php

namespace Tests\Feature;

use App\Content\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DocsTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    /** @var array<string, array<string, list<string>>> ids per package and docs page */
    private array $anchors = [];

    public function test_docs_nav_is_grouped_by_its_sections(): void
    {
        $groups = Package::find('phpolars')->docsGroups();

        $this->assertSame(['Guide', 'Reference'], array_keys($groups));
        $this->assertContains('reference/series', array_column($groups['Reference'], 'path'));

        // Since task 21 the nav is derived from the package's own
        // docs/_quarto.yml, so a page sits in the section phit files it under
        // rather than under the folder it happens to live in.
        $phit = Package::find('phit');
        $this->assertSame('Mathematics', $phit->docsGroupOf('notebooks/lmm-math'));
        $this->assertSame('Benchmarks and verification', $phit->docsGroupOf('benchmarks/ols'));
        $this->assertSame('Benchmarks and verification', $phit->docsGroupOf('data/lmm/index'));
    }

    public function test_synced_docs_render_with_resolved_links(): void
    {
        // Every package serves its docs here, juphyter's since the 2026-10-31
        // docs cleanup.
        $this->assertEqualsCanonicalizing(
            ['phibble', 'phplyr', 'phpolars', 'phit', 'phiz', 'cmdstanphp', 'juphyter'],
            array_keys(array_filter(Package::all(), fn (Package $package) => $package->hasDocs())),
        );

        foreach (array_filter(Package::all(), fn (Package $package) => $package->hasDocs()) as $key => $package) {
            $index = $this->get($package->url('docs'))->assertOk();
            foreach ($package->docsNav() as $item) {
                $index->assertSee($item['title']);
                $page = $this->get($package->url('docs', ['path' => $item['path'] === 'index' ? null : $item['path']]))->assertOk();
                $this->assertStringNotContainsString('phpstats://', $page->getContent(), "{$key}/{$item['path']}");
            }
        }

        $this->get('http://phit.phpstats.test/docs/notebooks/lmm-math')->assertOk()
            ->assertSee('class="math inline"', false)
            ->assertSee('mathjax@4', false);
    }

    public function test_benchmarks_and_verification_render_every_file(): void
    {
        $shipping = [];
        foreach (Package::all() as $key => $package) {
            if ($package->benchmarks() === [] && $package->verifications() === []) {
                continue;
            }
            $shipping[] = $key;

            if ($package->benchmarks() !== []) {
                $benchmarks = $this->get($package->url('benchmarks'))->assertOk();
                foreach ($package->benchmarks() as $benchmark) {
                    $benchmarks->assertSee($benchmark['title']);
                }
            }
            if ($package->verifications() !== []) {
                $verification = $this->get($package->url('verification'))->assertOk();
                foreach ($package->verifications() as $report) {
                    $verification->assertSee($report['title']);
                }
            }
        }

        // phibble, phiz, and cmdstanphp ship docs and nothing else: their
        // site/ folders hold no benchmark or verification reports, which is
        // why neither section appears in their bars.
        $this->assertEqualsCanonicalizing(['phit', 'phplyr', 'phpolars'], $shipping);
    }

    // Task 10: the three packages that joined the site, and the audits that
    // keep a re-pin from quietly breaking a link or dropping a figure.

    public function test_the_newly_pinned_packages_serve_their_own_docs(): void
    {
        foreach (['phibble' => 'types', 'phiz' => 'geoms', 'cmdstanphp' => 'installation'] as $key => $path) {
            $package = Package::find($key);
            $this->assertTrue($package->hasDocs(), $key);
            $this->assertNotNull($package->sync(), $key);

            $this->get($package->url('docs'))->assertOk();
            $this->get($package->url('docs', ['path' => $path]))->assertOk();

            // The Docs button on the overview no longer leaves the site.
            $this->get($package->url())->assertOk()
                ->assertSee($package->url('docs'), false)
                ->assertDontSee('phpstats.github.io', false);
        }
    }

    /**
     * The drift task 21 removed: phit rendered 52 pages and named 16 in its
     * nav, so 36 were synced and reachable and in no sidebar. The nav now
     * holds every page the package publishes, less the one in docs/data that
     * documents the datasets rather than the library.
     */
    public function test_every_page_a_package_renders_is_in_its_docs_nav(): void
    {
        $outside = [];
        foreach (array_filter(Package::all(), fn (Package $package) => $package->hasDocs()) as $key => $package) {
            foreach (array_diff($package->docsPaths(), array_column($package->docsNav(), 'path')) as $path) {
                $outside[] = "{$key}/{$path}";
            }
        }

        $this->assertSame([], $outside);
        $this->assertCount(60, Package::find('phit')->docsNav());
    }

    public function test_phiz_ships_every_docs_page_with_its_gallery(): void
    {
        // phiz's gallery has had a .qmd source since its 2026-10-31 docs
        // cleanup, so it imports like any other page, and the CmdStanPHP
        // figures plan left the public docs.
        $paths = Package::find('phiz')->docsPaths();

        $this->assertCount(18, $paths);
        $this->assertContains('gallery', $paths);
        $this->assertNotContains('cmdstanphp', $paths);
        $this->get('http://phiz.phpstats.test/docs/gallery')->assertOk();
    }

    public function test_every_content_asset_the_synced_docs_reference_exists(): void
    {
        $referenced = 0;
        $missing = [];
        foreach ($this->syncedDocs() as $file => $html) {
            preg_match_all('#(?:src|href)="(/content/[^"\#?]+)#', $html, $matches);
            foreach (array_unique($matches[1]) as $asset) {
                $referenced++;
                if (! is_file(public_path(ltrim($asset, '/')))) {
                    $missing[] = "{$file} -> {$asset}";
                }
            }
        }

        $this->assertGreaterThan(0, $referenced);
        $this->assertSame([], $missing);
    }

    /**
     * Links between packages' docs are stored as phpstats:// placeholders and
     * resolved per host. A re-pin that renames a page or a heading would leave
     * one pointing at nothing, so both halves are checked here.
     */
    public function test_every_docs_placeholder_resolves_to_a_page_and_an_anchor(): void
    {
        $links = 0;
        $fragments = 0;
        $broken = [];
        foreach ($this->syncedDocs() as $file => $html) {
            preg_match_all('#phpstats://([a-z0-9-]+)/docs/([^"\'\#\s]*)(\#[^"\'\s]*)?#', $html, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                $links++;
                [$key, $path] = [$match[1], $match[2]];
                $fragment = ltrim($match[3] ?? '', '#');
                $package = Package::find($key);
                if ($package === null || ! in_array($path, $package->docsPaths(), true)) {
                    $broken[] = "{$file} -> {$key}/{$path}";

                    continue;
                }
                if ($fragment !== '') {
                    $fragments++;
                    if (! in_array($fragment, $this->anchors($package, $path), true)) {
                        $broken[] = "{$file} -> {$key}/{$path}#{$fragment}";
                    }
                }
            }
        }

        $this->assertGreaterThan(0, $links);
        $this->assertGreaterThan(0, $fragments);
        $this->assertSame([], $broken);
    }

    /**
     * Every rendered page of every synced package, as path => HTML.
     *
     * @return array<string, string>
     */
    private function syncedDocs(): array
    {
        $html = [];
        foreach (Package::all() as $package) {
            if (! $package->hasDocs()) {
                continue;
            }
            foreach (File::allFiles($package->path('docs')) as $file) {
                if ($file->getExtension() === 'html') {
                    $html[$package->key.'/'.$file->getRelativePathname()] = $file->getContents();
                }
            }
        }

        return $html;
    }

    /**
     * The ids a docs page offers as link targets.
     *
     * @return list<string>
     */
    private function anchors(Package $package, string $path): array
    {
        $this->anchors[$package->key][$path] ??= preg_match_all(
            '#\bid="([^"]+)"#',
            File::get($package->path("docs/{$path}.html")),
            $matches,
        ) ? $matches[1] : [];

        return $this->anchors[$package->key][$path];
    }

    // Sidebar group headings (task 9). A package whose nav is one group gets no
    // heading, the same convention as a bar menu holding one layer.

    public function test_the_docs_sidebar_heads_each_group_when_there_are_several(): void
    {
        $package = Package::find('phplyr');
        $this->assertGreaterThan(1, count($package->docsGroups()));

        $sidebar = $this->docsSidebar($package);

        foreach (array_keys($package->docsGroups()) as $group) {
            $this->assertStringContainsString(">{$group}</p>", $sidebar);
        }
    }

    public function test_the_docs_sidebar_omits_a_lone_group_heading(): void
    {
        $this->flattenDocsNav('phplyr', ['index', 'getting-started', 'coming-from-dplyr']);
        $package = Package::find('phplyr');
        $this->assertSame(['Guide'], array_keys($package->docsGroups()));

        $sidebar = $this->docsSidebar($package);

        $this->assertStringNotContainsString('>Guide</p>', $sidebar);
        $this->assertStringContainsString('Getting started</a>', $sidebar);
        $this->assertStringContainsString('Coming from dplyr</a>', $sidebar);
    }

    /** Just the sidebar, so a group name in the page's own prose cannot count. */
    private function docsSidebar(Package $package): string
    {
        $html = $this->get($package->url('docs'))->assertOk()->getContent();
        $pattern = '#<nav[^>]*aria-label="'.preg_quote($package->name(), '#').' documentation".*?</nav>#s';
        $this->assertSame(1, preg_match($pattern, $html, $matches), 'the docs sidebar should be in the page');

        return $matches[0];
    }

    /**
     * Point the content path at a copy of the package whose docs nav lists only
     * these top-level pages, so every item falls in one group. No synced
     * package has a flat nav yet; phibble, phiz, and cmdstanphp will.
     */
    private function flattenDocsNav(string $key, array $paths): void
    {
        $root = base_path('storage/framework/testing/docsnav-'.uniqid());
        File::copyDirectory(config('phpstats.content_path'), $root);

        $file = "{$root}/{$key}/docs.json";
        $docs = json_decode(File::get($file), true, flags: JSON_THROW_ON_ERROR);
        // A flat nav names no sections, so every item falls in the default group.
        $docs['nav'] = array_values(array_map(
            fn (array $item) => array_diff_key($item, ['section' => true]),
            array_filter($docs['nav'], fn (array $item) => in_array($item['path'], $paths, true)),
        ));
        File::put($file, json_encode($docs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        config(['phpstats.content_path' => $root]);
        Package::flush();
        $this->beforeApplicationDestroyed(fn () => File::deleteDirectory($root));
    }
}
