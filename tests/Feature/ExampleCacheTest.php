<?php

namespace Tests\Feature;

use App\Content\Example;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ExampleCacheTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        // The suite runs with APP_DEBUG on, which turns the cache off.
        config(['app.debug' => false]);
        Cache::flush();
        Example::reset();
    }

    protected function tearDown(): void
    {
        Example::reset();

        parent::tearDown();
    }

    /** Render the landing page as a fresh request would. */
    private function landing(): string
    {
        Example::reset();
        $this->app->forgetScopedInstances();

        return $this->get('http://phpstats.test/')->assertOk()->getContent();
    }

    private function render(): callable
    {
        return fn (mixed $value) => view('components.example-result', ['value' => $value])->render();
    }

    /**
     * The files a panel's output is computed from besides its example .php,
     * built the way package-explorer.blade.php builds them: the document a
     * source entry renders, and the fixtures a stored result is read from.
     *
     * @return list<string>
     */
    private function panelFiles(string $key): array
    {
        $panel = config("phpstats.explorer.{$key}");

        return array_values(array_filter([
            isset($panel['source']) ? resource_path('examples/'.$panel['source']['file']) : null,
            ...($panel['stored']['files'] ?? []),
        ]));
    }

    /**
     * The panel's stored result, counting each time it is computed: a warm
     * panel is a count that did not move. The counter is the caller's own
     * variable, so the closure and the test see the same number.
     */
    private function counted(string $key, int &$calls): callable
    {
        $stored = config("phpstats.explorer.{$key}.stored");

        return function () use (&$calls, $stored) {
            $calls++;

            return ($stored['result'])();
        };
    }

    /** The output call the explorer view makes for one panel, with $result counted. */
    private function panelOutput(string $key, callable $result): string
    {
        $label = config("phpstats.explorer.{$key}.output");

        return Example::output(
            "packages/{$key}",
            'package-output|'.$label,
            fn (mixed $value) => view('components.package-output', ['value' => $value, 'label' => $label])->render(),
            $result,
            $this->panelFiles($key),
        );
    }

    /**
     * The examples this server cannot run, such as the phpolars one without
     * the extension. Running them throws, nothing is cached, and every
     * request retries them, which is what the empty-result sentence needs.
     *
     * @return list<string>
     */
    private function uncacheable(): array
    {
        $paths = [];
        foreach (config('phpstats.explorer') as $key => $panel) {
            if (isset($panel['stored'])) {
                continue;
            }
            Example::reset();
            try {
                Example::run("packages/{$key}");
            } catch (\Throwable) {
                $paths[] = Example::path("packages/{$key}");
            }
        }
        Example::reset();
        sort($paths);

        return $paths;
    }

    private function assertOnlyUncacheableRan(string $message = ''): void
    {
        $ran = array_keys(Example::runs());
        sort($ran);

        $this->assertSame($this->uncacheable(), $ran, $message);
    }

    public function test_a_warm_request_runs_only_what_it_could_not_cache(): void
    {
        $this->landing();
        $cold = Example::runs();

        $this->landing();

        $this->assertNotEmpty($cold, 'the first request should run the examples');
        $this->assertLessThan(count($cold), count(Example::runs()));
        $this->assertOnlyUncacheableRan('a warm request should rerun only the examples that throw');
    }

    public function test_the_cached_fragment_matches_the_computed_one(): void
    {
        $cold = Example::output('packages/phplyr', 'example-result', $this->render());
        $this->assertSame([Example::path('packages/phplyr') => 1], Example::runs());

        Example::reset();
        $warm = Example::output('packages/phplyr', 'example-result', $this->render());

        $this->assertSame($cold, $warm);
        $this->assertSame([], Example::runs(), 'the second call should come from the cache');
        $this->assertStringContainsString('Marketplace', $cold);
    }

    public function test_the_page_shows_the_cached_fragment_verbatim(): void
    {
        // The explorer panel is plain Blade. The tab examples go through
        // Markdown, which reflows the HTML it passes through.
        $label = config('phpstats.explorer.phibble.output');
        $render = fn (mixed $value) => view('components.package-output', ['value' => $value, 'label' => $label])->render();

        $this->landing();
        Example::reset();
        $fragment = Example::output('packages/phibble', 'package-output|'.$label, $render);

        $this->assertSame([], Example::runs(), 'the fragment should come from the cache');
        $this->assertStringContainsString($fragment, $this->landing());
    }

    public function test_an_edited_example_recomputes(): void
    {
        $this->landing();
        $this->landing();
        $this->assertOnlyUncacheableRan();

        $path = Example::path('packages/phplyr');
        $mtime = filemtime($path);
        try {
            touch($path, $mtime + 10);
            clearstatcache(true, $path);
            $this->landing();

            $this->assertArrayHasKey($path, Example::runs(), 'an edited example should run again');
        } finally {
            touch($path, $mtime);
            clearstatcache(true, $path);
        }
    }

    public function test_a_failing_example_is_not_cached(): void
    {
        $boom = function () {
            throw new \RuntimeException('no');
        };

        foreach ([1, 2] as $attempt) {
            try {
                Example::output('packages/phplyr', 'failing', $this->render(), $boom);
                $this->fail("attempt {$attempt} should have thrown");
            } catch (\RuntimeException) {
                // Nothing is stored, so the next request tries again.
            }
        }

        $this->assertSame('ok', Example::output('packages/phplyr', 'failing', fn (mixed $v) => $v, fn () => 'ok'));
    }

    public function test_a_missing_extension_still_says_so_on_a_warm_page(): void
    {
        if ($this->uncacheable() === []) {
            $this->markTestSkipped('Every example runs on this server, so none reports a missing extension.');
        }

        $cold = $this->landing();
        $warm = $this->landing();

        foreach (['cold' => $cold, 'warm' => $warm] as $state => $html) {
            $this->assertStringContainsString("can't run the example", $html, $state);
        }
    }

    /**
     * The layout emits phiz's loader for every page, so a cached chart no
     * longer has to record whether its fragment carried the runtime. This
     * stays as the regression it was: once cold, and once warm.
     */
    public function test_the_phiz_runtime_is_emitted_once_on_a_warm_page(): void
    {
        $this->assertSame(1, substr_count($this->landing(), 'phiz: renders'));
        $this->assertSame(1, substr_count($this->landing(), 'phiz: renders'));
    }

    /**
     * CmdStanPHP's panel renders stored draws rather than running its example.
     * A flush still recomputes it, as it does every panel; what the draws
     * themselves do to the key is the test below.
     */
    public function test_a_flush_recomputes_the_stored_posterior_panel(): void
    {
        $calls = 0;
        $read = $this->counted('cmdstanphp', $calls);
        $output = fn () => $this->panelOutput('cmdstanphp', $read);

        $cold = $output();
        $this->assertSame(1, $calls);

        Example::reset();
        $this->assertSame($cold, $output());
        $this->assertSame(1, $calls, 'a warm panel should not read the draws again');

        Example::flush();
        Example::reset();
        $this->assertSame($cold, $output());
        $this->assertSame(2, $calls, 'a flush should read the draws again');
    }

    /**
     * Task 24. A stored panel's output is computed from committed files
     * rather than from running the example, so those files are in its key:
     * re-capturing one reaches a warm page on its own, and regenerating the
     * Stan posterior no longer needs `php artisan examples:clear`. Every
     * file the entry declares has to count, which is why this walks them.
     */
    public function test_a_stored_panels_key_follows_every_file_it_declares(): void
    {
        // juphyter's two: the Quarto document it shows, whose mtime used to
        // ride in the cache variant, and the capture of the cell it drew.
        foreach (['cmdstanphp' => 1, 'juphyter' => 2] as $key => $declared) {
            $files = $this->panelFiles($key);
            $this->assertCount($declared, $files, $key);

            foreach ($files as $path) {
                // One panel, so each file's turn starts from a cold cache
                // rather than from the fragment the file before it left.
                Example::flush();
                Example::reset();
                $calls = 0;
                $read = $this->counted($key, $calls);
                $output = fn () => $this->panelOutput($key, $read);
                $name = $key.': '.basename($path);

                $cold = $output();
                Example::reset();
                $this->assertSame($cold, $output());
                $this->assertSame(1, $calls, "{$name} should be read once and then cached");

                $mtime = filemtime($path);
                try {
                    touch($path, $mtime + 10);
                    clearstatcache(true, $path);
                    Example::reset();
                    $output();

                    $this->assertSame(2, $calls, "touching {$name} should move the panel's key");
                } finally {
                    touch($path, $mtime);
                    clearstatcache(true, $path);
                }
            }
        }
    }

    /**
     * The key the view builds is the key this test builds, so the files it
     * passes are the ones the entry declares and its variant carries only
     * the label: otherwise the page's fragment would be under another key
     * and the panel below would read its result again.
     */
    public function test_a_page_caches_a_stored_panel_under_the_key_the_view_builds(): void
    {
        $page = $this->landing();

        foreach (['cmdstanphp', 'juphyter'] as $key) {
            $calls = 0;
            $read = $this->counted($key, $calls);
            Example::reset();
            $fragment = $this->panelOutput($key, $read);

            $this->assertSame(0, $calls, "{$key}: the fragment the page cached should come back as it is");
            $this->assertStringContainsString($fragment, $page, $key);
        }
    }

    public function test_debug_mode_skips_the_cache(): void
    {
        config(['app.debug' => true]);

        $this->landing();
        $first = Example::runs();
        $this->landing();

        $this->assertNotEmpty($first);
        $this->assertSame(array_keys($first), array_keys(Example::runs()), 'with APP_DEBUG on every request recomputes');
    }

    public function test_flush_clears_the_cache(): void
    {
        $this->landing();
        $this->landing();
        $this->assertOnlyUncacheableRan();

        Example::flush();
        $this->landing();
        $ran = Example::runs();

        $this->assertGreaterThan(count($this->uncacheable()), count($ran), 'a flush should force a recompute');
    }

    public function test_the_artisan_command_flushes(): void
    {
        $this->landing();
        $this->landing();
        $this->assertOnlyUncacheableRan();

        $this->artisan('examples:clear')->assertSuccessful();
        $this->landing();
        $ran = Example::runs();

        $this->assertGreaterThan(count($this->uncacheable()), count($ran));
    }
}
