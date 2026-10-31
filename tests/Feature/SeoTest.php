<?php

namespace Tests\Feature;

use App\Content\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    /** The length search engines show, which layout.blade.php cuts descriptions to. */
    private const LENGTH = 160;

    /** robots.txt as a host the site is published on, or any other host, answers it. */
    private function robots(bool $published, string $sitemapHost = 'phpstats.test'): string
    {
        return "User-agent: *\nDisallow:".($published ? '' : ' /')."\n\nSitemap: http://{$sitemapHost}/sitemap.xml\n";
    }

    protected function tearDown(): void
    {
        // Back to phpunit.xml's value. Unsetting it would let a local .env
        // in path mode decide the next test's routes.
        putenv('PHPSTATS_PATHS=false');
        $_ENV['PHPSTATS_PATHS'] = $_SERVER['PHPSTATS_PATHS'] = 'false';

        parent::tearDown();
    }

    public function test_the_sitemap_is_valid_xml_and_lists_every_section(): void
    {
        $urls = $this->sitemap('http://phpstats.test/sitemap.xml');

        $this->assertContains('http://phpstats.test', $urls);
        $this->assertContains('http://phpstats.test/about', $urls);
        $this->assertContains('http://phit.phpstats.test', $urls);
        $this->assertContains('http://phit.phpstats.test/docs', $urls);
        $this->assertContains('http://phit.phpstats.test/docs/formulas', $urls);
        $this->assertContains('http://phit.phpstats.test/vignettes', $urls);
        $this->assertContains('http://phit.phpstats.test/vignettes/school-scores', $urls);
        $this->assertContains('http://phit.phpstats.test/benchmarks', $urls);
        $this->assertContains('http://phit.phpstats.test/verification', $urls);

        foreach (Package::keys() as $key) {
            $this->assertContains("http://{$key}.phpstats.test", $urls);
        }

        // The docs pages outside the nav, such as phit's notebooks, are listed too.
        $docs = array_filter($urls, fn (string $url) => str_starts_with($url, 'http://phit.phpstats.test/docs'));
        $this->assertGreaterThan(count(Package::find('phit')->docsNav()), count($docs));

        $this->assertSame($urls, array_values(array_unique($urls)), 'the sitemap lists a URL twice');
    }

    public function test_the_sitemap_leaves_out_companions_and_pages_with_nothing_to_show(): void
    {
        $urls = $this->sitemap('http://phpstats.test/sitemap.xml');

        foreach (array_keys(config('phpstats.companions')) as $companion) {
            $this->assertEmpty(
                array_filter($urls, fn (string $url) => str_contains($url, "{$companion}.phpstats.test")),
                "the sitemap lists the companion {$companion}",
            );
        }

        // Every library package's docs are synced since task 10, so each is
        // listed. juphyter has none, and its /docs is not a page.
        $this->assertTrue(Package::find('phibble')->hasDocs());
        $this->assertContains('http://phibble.phpstats.test/docs', $urls);
        $this->assertFalse(Package::find('juphyter')->hasDocs());
        $this->assertNotContains('http://juphyter.phpstats.test/docs', $urls);

        // The sections the package bar hides when a package has none of them.
        foreach (Package::all() as $key => $package) {
            foreach (['benchmarks' => $package->benchmarks(), 'verification' => $package->verifications(), 'vignettes' => $package->vignettes()] as $section => $content) {
                $this->assertSame(
                    $content !== [],
                    in_array("http://{$key}.phpstats.test/{$section}", $urls, true),
                    "{$key}/{$section}",
                );
            }
        }

        // phit publishes both; phibble syncs docs and no reports, so neither.
        $this->assertContains('http://phit.phpstats.test/benchmarks', $urls);
        $this->assertContains('http://phit.phpstats.test/verification', $urls);
        $this->assertEmpty(Package::find('phibble')->benchmarks());
        $this->assertNotContains('http://phibble.phpstats.test/benchmarks', $urls);
        $this->assertNotContains('http://phibble.phpstats.test/verification', $urls);

        // A vignette belongs to the host of the package that owns it.
        $this->assertNotContains('http://phplyr.phpstats.test/vignettes/simulated-rents', $urls);
        $this->assertContains('http://phit.phpstats.test/vignettes/simulated-rents', $urls);
    }

    public function test_every_page_in_the_sitemap_answers_with_a_description_and_its_own_canonical_url(): void
    {
        $urls = $this->sitemap('http://phpstats.test/sitemap.xml');
        $this->assertNotEmpty($urls);

        foreach ($urls as $url) {
            $html = $this->get($url)->assertOk()->content();

            $this->assertStringContainsString('<link rel="canonical" href="'.$url.'">', $html, $url);
            $this->assertStringContainsString('<meta property="og:url" content="'.$url.'">', $html, $url);
            $this->assertStringContainsString('<meta property="og:type" content="website">', $html, $url);
            $this->assertStringContainsString('<meta property="og:site_name" content="PHPstats">', $html, $url);
            $this->assertStringContainsString('<meta name="twitter:card" content="summary">', $html, $url);

            // The description, the Open Graph one, and the Twitter one say the same thing.
            $descriptions = $this->descriptions($html);
            $this->assertCount(3, $descriptions, $url);
            $this->assertCount(1, array_unique($descriptions), $url);
            $this->assertNotSame('', $descriptions[0], $url);
            $this->assertLessThanOrEqual(self::LENGTH, mb_strlen($descriptions[0]), $url);
        }
    }

    public function test_each_page_type_describes_itself(): void
    {
        $pages = [
            // The landing page and the Markdown pages: the site's own copy.
            'http://phpstats.test/' => 'A family of PHP libraries for data frames, pipelines, charts, and statistical models, run inside the application that holds the data.',
            'http://phpstats.test/about' => 'PHPstats is a family of open-source PHP packages for data frames, pipelines, statistical models, and charts. Every package is on Packagist.',
            // A package overview: its tagline.
            'http://phit.phpstats.test/' => 'Fast, verified statistical models for PHP.',
            // Docs and the other sections: the page title, then the tagline.
            'http://phit.phpstats.test/docs/contracts' => 'Public contracts in phit. Fast, verified statistical models for PHP.',
            'http://phit.phpstats.test/vignettes' => 'Examples in phit. Fast, verified statistical models for PHP.',
            'http://phit.phpstats.test/vignettes/school-scores' => 'School scores in phit. Fast, verified statistical models for PHP.',
            'http://phit.phpstats.test/benchmarks' => 'Benchmarks in phit. Fast, verified statistical models for PHP.',
            'http://phit.phpstats.test/verification' => 'Verification in phit. Fast, verified statistical models for PHP.',
            // A tagline that does not end in a period still reads as a sentence.
            'http://phpolars.phpstats.test/benchmarks' => 'Benchmarks in phpolars. Polars data frames for PHP.',
            // A Livewire vignette renders its own view through the same layout.
            'http://phpolars.phpstats.test/vignettes/polars-pipeline' => 'Polars pipeline in phpolars. Polars data frames for PHP.',
        ];

        foreach ($pages as $url => $description) {
            $this->assertSame(
                [$description, $description, $description],
                $this->descriptions($this->get($url)->assertOk()->content()),
                $url,
            );
        }
    }

    public function test_a_long_description_is_cut_on_a_word_boundary(): void
    {
        $this->get('http://phpstats.test/')->assertOk();

        $long = trim(str_repeat('phibble reads tables from the application database. ', 5));
        $html = Blade::render('<x-layout :description="$description">x</x-layout>', ['description' => $long]);

        $descriptions = $this->descriptions($html);
        $this->assertSame(Str::limit($long, self::LENGTH, preserveWords: true), $descriptions[0]);
        $this->assertLessThanOrEqual(self::LENGTH, mb_strlen($descriptions[0]));
        $this->assertStringEndsWith('...', $descriptions[0]);
    }

    public function test_robots_invites_crawlers_on_the_hosts_the_site_is_published_on(): void
    {
        // No file in public/, which the web server would serve ahead of the route.
        $this->assertFileDoesNotExist(public_path('robots.txt'));

        config(['phpstats.indexable' => true]);
        $hosts = ['phpstats.test', 'phit.phpstats.test', 'cmdstanphp.phpstats.test', 'phit-polyfill.phpstats.test'];

        foreach ($hosts as $host) {
            $response = $this->get("http://{$host}/robots.txt")->assertOk();

            $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
            $this->assertSame($this->robots(true), $response->content(), $host);
        }
    }

    public function test_robots_keeps_crawlers_off_staging_and_any_other_host(): void
    {
        config(['phpstats.indexable' => true]);

        // The staging and tunnel hosts, and a host the site does not answer on.
        $hosts = ['phpstats.pequod.sh', 'phpstats-phit.pequod.sh', 'frosty-wasp.polyscope.test', 'not-a-package.phpstats.test'];

        foreach ($hosts as $host) {
            $this->assertSame($this->robots(false), $this->get("http://{$host}/robots.txt")->assertOk()->content(), $host);
        }
    }

    public function test_robots_keeps_crawlers_off_a_published_host_that_is_not_indexable(): void
    {
        // Staging answers on its own root host, so only this switch keeps it out
        // of search. APP_ENV is testing here, so it is off without being set.
        $this->assertFalse(config('phpstats.indexable'));

        foreach (['phpstats.test', 'phit.phpstats.test'] as $host) {
            $this->assertSame($this->robots(false), $this->get("http://{$host}/robots.txt")->assertOk()->content(), $host);
        }

        config(['phpstats.indexable' => true]);
        $this->assertSame($this->robots(true), $this->get('http://phpstats.test/robots.txt')->assertOk()->content());
    }

    public function test_robots_keeps_crawlers_off_a_path_mode_preview(): void
    {
        $this->enterPathMode();
        config(['phpstats.indexable' => true]);

        // The tunnel the preview is behind. In path mode the sitemap is on the
        // host the request came in on, so the line names that host.
        $this->assertSame(
            $this->robots(false, 'preview.test'),
            $this->get('http://preview.test/robots.txt')->assertOk()->content(),
        );

        // Path mode serves no package hosts, so they invite nobody either.
        $this->assertSame(
            $this->robots(false, 'phit.phpstats.test'),
            $this->get('http://phit.phpstats.test/robots.txt')->assertOk()->content(),
        );

        // The landing host is still the site itself, whichever mode it runs in.
        $this->assertSame($this->robots(true), $this->get('http://phpstats.test/robots.txt')->assertOk()->content());
    }

    public function test_the_page_catch_all_still_serves_about_and_leaves_the_sitemap_alone(): void
    {
        $this->get('http://phpstats.test/about')->assertOk()->assertSee('About PHPstats');
        $this->get('http://phpstats.test/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function test_the_sitemap_uses_the_request_host_in_path_mode(): void
    {
        $this->enterPathMode();

        $urls = $this->sitemap('http://preview.test/sitemap.xml');

        $this->assertContains('http://preview.test', $urls);
        $this->assertContains('http://preview.test/about', $urls);
        $this->assertContains('http://preview.test/phit', $urls);
        $this->assertContains('http://preview.test/phit/docs/formulas', $urls);
        $this->assertContains('http://preview.test/phit/benchmarks', $urls);

        foreach ($urls as $url) {
            $this->assertStringStartsWith('http://preview.test', $url, 'a URL left the request host');
            $this->get($url)->assertOk();
        }
    }

    public function test_canonical_urls_follow_the_request_host_in_path_mode(): void
    {
        $this->enterPathMode();

        $this->get('http://preview.test/phit/benchmarks')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="http://preview.test/phit/benchmarks">', false);
    }

    /**
     * Reboot the application with PHPSTATS_PATHS on, which routes/web.php
     * reads when it registers the routes, and put the seeded data back.
     */
    private function enterPathMode(): void
    {
        putenv('PHPSTATS_PATHS=true');
        $_ENV['PHPSTATS_PATHS'] = $_SERVER['PHPSTATS_PATHS'] = 'true';
        $this->refreshApplication();
        $this->artisan('migrate:fresh', ['--seed' => true]);

        $this->assertTrue(config('phpstats.paths'), 'path mode did not take effect');
    }

    /**
     * The meta description, the Open Graph one, and the Twitter one, in that
     * order, as the reader sees them.
     *
     * @return list<string>
     */
    private function descriptions(string $html): array
    {
        preg_match_all(
            '/<meta (?:name|property)="(?:description|og:description|twitter:description)" content="([^"]*)"/',
            $html,
            $matches,
        );

        return array_map(fn (string $content) => html_entity_decode($content, ENT_QUOTES), $matches[1]);
    }

    /**
     * The URLs in the sitemap at this address, parsed strictly so an invalid
     * document fails the test.
     *
     * @return list<string>
     */
    private function sitemap(string $url): array
    {
        $body = $this->get($url)->assertOk()->content();

        $document = new \DOMDocument;
        $this->assertTrue($document->loadXML($body, LIBXML_NONET), "the sitemap at {$url} is not valid XML");
        $this->assertSame('urlset', $document->documentElement->localName);
        $this->assertSame('http://www.sitemaps.org/schemas/sitemap/0.9', $document->documentElement->namespaceURI);

        $urls = [];
        foreach ($document->getElementsByTagName('loc') as $loc) {
            $urls[] = $loc->textContent;
        }

        return $urls;
    }
}
