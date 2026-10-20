<?php

namespace Tests\Feature;

use App\Content\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Phiz\Assets;
use Phiz\Laravel\PhizServiceProvider;
use Tests\TestCase;

/**
 * phiz draws a chart from the Vega-Lite spec in a [data-phiz] attribute, and
 * the script that finds those elements is phiz's loader. `DocsImporter` keeps
 * only the body of a rendered Quarto page, so a synced docs page carries the
 * containers and not the loader, and a page with vega but no loader shows
 * silent empty divs. The layout emits @phizScripts for every page instead, so
 * these tests assert the loader itself, never merely that scripts exist.
 */
class PhizScriptsTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_a_synced_docs_page_ships_phizs_own_loader(): void
    {
        $html = $this->get('http://phiz.phpstats.test/docs/geoms')->assertOk()->getContent();

        $this->assertSame(20, substr_count($html, 'data-phiz='), 'geoms is the page with the most charts');
        // Byte-for-byte what the package ships, so a copy in a Blade file
        // could not drift from it unnoticed.
        $this->assertStringContainsString('<script>'.Assets::loader().'</script>', $html);
        $this->assertStringContainsString(PhizServiceProvider::scripts(), $html);

        // The libraries the loader hands the spec to: phiz's own tags when
        // phiz.assets names a source, or this site's bundle, which defines
        // window.vegaEmbed itself to follow the .dark class.
        $this->assertTrue(
            str_contains($html, 'vega.min.js') || preg_match('#/build/assets/app-[^"]+\.js#', $html) === 1,
            'the page must load vega-embed from somewhere',
        );
    }

    public function test_the_loader_is_next_to_the_containers_it_has_to_find(): void
    {
        foreach (['phiz' => 'docs/geoms', 'phibble' => 'docs/laravel'] as $key => $path) {
            $package = Package::find($key);
            $html = $this->get($package->url('docs', ['path' => substr($path, strlen('docs/'))]))->assertOk()->getContent();

            $this->assertGreaterThan(0, substr_count($html, 'data-phiz='), $key);
            $this->assertStringContainsString(Assets::loader(), $html, $key);
        }
    }

    public function test_every_page_carries_the_runtime_exactly_once(): void
    {
        // The layout owns it, so a page with charts cannot miss it and a page
        // that renders <x-phiz> cannot get a second copy. The cost is that a
        // page with no chart carries the loader too.
        $pages = [
            'landing' => 'http://phpstats.test/',
            'about' => 'http://phpstats.test/about',
            'overview' => 'http://phiz.phpstats.test/',
            'docs with charts' => 'http://phiz.phpstats.test/docs/geoms',
            'docs without charts' => 'http://phiz.phpstats.test/docs/ggplot2',
            'vignettes' => 'http://phit.phpstats.test/vignettes',
        ];

        foreach ($pages as $name => $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertSame(1, substr_count($html, Assets::loader()), $name);
        }
    }

    public function test_x_phiz_leaves_the_script_tags_to_the_layout(): void
    {
        $this->assertFalse(config('phiz.inject_scripts'), 'config/phiz.php must leave them to @phizScripts');

        // The landing page's charts are <x-phiz>, so this is the page where a
        // second copy used to appear, and where task 3's cache had to record
        // which fragment had carried the runtime.
        $html = $this->get('http://phpstats.test/')->assertOk()->getContent();

        $this->assertGreaterThan(0, substr_count($html, 'data-phiz='), 'the landing page should render charts');
        $this->assertSame(1, substr_count($html, Assets::loader()));
    }

    /**
     * What the other tests cannot show: that the loader, running in a browser,
     * turns a container into a drawn chart. The spec and the loader are the
     * real ones; vega-embed is a stub that records the call, because the point
     * is phiz's initialisation and not vega's rendering.
     */
    public function test_the_loader_initialises_a_chart_container_in_a_browser(): void
    {
        $chrome = $this->chrome();
        if ($chrome === null) {
            $this->markTestSkipped('No Chrome to run the loader in. Set CHROME_BINARY to one.');
        }

        $directory = base_path('storage/framework/testing/phiz-loader-'.uniqid());
        File::ensureDirectoryExists($directory);
        $this->beforeApplicationDestroyed(fn () => File::deleteDirectory($directory));

        $stub = <<<'JS'
            window.vegaEmbed = function (el, spec) {
                var drawn = document.createElement('svg');
                drawn.setAttribute('data-drawn', spec && spec.$schema ? 'yes' : 'no');
                el.appendChild(drawn);
                return Promise.resolve({ view: { finalize: function () {} } });
            };
            JS;

        $page = '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>'
            ."<script>{$stub}</script>"
            .$this->firstChartContainer('phiz')
            .PhizServiceProvider::scripts()
            .'</body></html>';
        File::put($file = "{$directory}/page.html", $page);

        $result = Process::timeout(120)->run([
            $chrome, '--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check',
            '--user-data-dir='.$directory.'/profile', '--virtual-time-budget=5000', '--dump-dom', "file://{$file}",
        ]);
        $this->assertTrue($result->successful(), $result->errorOutput());

        // The loader's own text holds words like "Chart error", so the scripts
        // go before anything is read out of the page.
        $dom = preg_replace('#<script.*?</script>#s', '', $result->output());

        $this->assertStringContainsString('data-phiz-view', $dom, 'the loader should have made a view element');
        $this->assertMatchesRegularExpression('#<svg[^>]*data-drawn="yes"#', $dom, 'the chart should have been drawn from its spec');
        $this->assertStringNotContainsString('Chart error', $dom);
    }

    /** The first chart container of a package's synced docs, with its spec. */
    private function firstChartContainer(string $key): string
    {
        foreach (File::allFiles(Package::find($key)->path('docs')) as $file) {
            if ($file->getExtension() === 'html'
                && preg_match('#<div class="phiz-figure">.*?</div></div></div>#s', $file->getContents(), $matches)) {
                return $matches[0];
            }
        }

        $this->fail("No chart container in {$key}'s synced docs.");
    }

    /** A Chrome or Chromium binary to run the loader in, if this machine has one. */
    private function chrome(): ?string
    {
        $candidates = array_filter([
            env('CHROME_BINARY'),
            '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
            '/Applications/Chromium.app/Contents/MacOS/Chromium',
            '/usr/bin/google-chrome',
            '/usr/bin/chromium',
            '/usr/bin/chromium-browser',
        ]);

        foreach ($candidates as $candidate) {
            if (is_executable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
