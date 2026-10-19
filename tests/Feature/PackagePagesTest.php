<?php

namespace Tests\Feature;

use App\Content\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackagePagesTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_package_pages_link_the_family_and_related_packages(): void
    {
        $response = $this->get('http://phplyr.phpstats.test/')->assertOk();

        // The three menus link every package, and the mark links the landing page.
        foreach (Package::keys() as $key) {
            $response->assertSee("http://{$key}.phpstats.test", false);
        }
        $response->assertSee('Processing &amp; Visualization', false)
            ->assertSee('Statistical Modeling')
            ->assertSee('aria-label="PHPstats home"', false)
            ->assertSee('Connected packages')
            ->assertSee('phplyr functions take phibble frames and return new ones.');

        // The other side of the relation lists it too.
        $this->get('http://phibble.phpstats.test/')->assertSee('Used by')->assertSee('phplyr functions take phibble frames');
    }

    public function test_each_package_has_an_overview_on_its_own_host(): void
    {
        foreach (Package::keys() as $key) {
            $package = Package::find($key);
            $this->get("http://{$key}.phpstats.test/")->assertOk()->assertSee($package->tagline());
            foreach (['vignettes', 'benchmarks', 'verification'] as $section) {
                $this->get("http://{$key}.phpstats.test/{$section}")->assertOk();
            }
        }
    }

    public function test_unknown_hosts_and_paths_are_not_found(): void
    {
        $this->get('http://not-a-package.phpstats.test/')->assertNotFound();
        $this->get('http://phit.phpstats.test/vignettes/not-a-vignette')->assertNotFound();
        $this->get('http://phit.phpstats.test/docs/not-a-page')->assertNotFound();
        $this->get('http://phpstats.test/not-a-project')->assertNotFound();
    }

    public function test_companions_are_part_of_their_parent(): void
    {
        $this->assertArrayNotHasKey('phit-polyfill', Package::all());
        $this->get('http://phit-polyfill.phpstats.test/docs')->assertRedirect('http://phit.phpstats.test');
        $this->get('http://phpstats.test/php-stats-polyfill')->assertRedirect('http://phit.phpstats.test');
        $this->get('http://phit.phpstats.test/')->assertSee('defines the same functions in plain PHP');
        $this->get('http://phpstats.test/')->assertDontSee('phit-polyfill.phpstats.test');
    }

    public function test_old_paths_redirect_to_package_hosts(): void
    {
        $this->get('http://phpstats.test/php-stats')->assertRedirect('http://phit.phpstats.test');
        $this->get('http://phpstats.test/phibble')->assertRedirect('http://phibble.phpstats.test');
        $this->get('http://phpstats.test/vignettes/school-effects')->assertRedirect('http://phit.phpstats.test/vignettes/school-shrinkage');
        $this->get('http://phpstats.test/vignettes/sql-pipeline')->assertRedirect('http://phibble.phpstats.test/vignettes/sql-pipeline');
        $this->get('http://phpstats.test/benchmarks')->assertRedirect('http://phit.phpstats.test/benchmarks');
    }
}
