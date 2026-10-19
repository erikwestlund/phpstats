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
        $cold = Example::output('orders/read', 'example-result', $this->render());
        $this->assertSame([Example::path('orders/read') => 1], Example::runs());

        Example::reset();
        $warm = Example::output('orders/read', 'example-result', $this->render());

        $this->assertSame($cold, $warm);
        $this->assertSame([], Example::runs(), 'the second call should come from the cache');
        $this->assertStringContainsString('<table', $cold);
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

        $path = Example::path('orders/read');
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
                Example::output('orders/read', 'failing', $this->render(), $boom);
                $this->fail("attempt {$attempt} should have thrown");
            } catch (\RuntimeException) {
                // Nothing is stored, so the next request tries again.
            }
        }

        $this->assertSame('ok', Example::output('orders/read', 'failing', fn (mixed $v) => $v, fn () => 'ok'));
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

    public function test_the_phiz_runtime_is_emitted_once_on_a_warm_page(): void
    {
        $this->assertSame(1, substr_count($this->landing(), 'phiz: renders'));
        $this->assertSame(1, substr_count($this->landing(), 'phiz: renders'));
    }

    /**
     * CmdStanPHP's panel renders stored draws rather than running its example,
     * and the key follows the example file, not the draws. This is why
     * stan:fit-school-posterior flushes: without it the panel would keep
     * showing the old posterior.
     */
    public function test_a_flush_recomputes_the_stored_posterior_panel(): void
    {
        $label = config('phpstats.explorer.cmdstanphp.output');
        $stored = config('phpstats.explorer.cmdstanphp.stored');
        $calls = 0;
        $read = function () use (&$calls, $stored) {
            $calls++;

            return $stored();
        };
        $output = fn () => Example::output(
            'packages/cmdstanphp',
            'package-output|'.$label,
            fn (mixed $value) => view('components.package-output', ['value' => $value, 'label' => $label])->render(),
            $read,
        );

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
