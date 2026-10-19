<?php

namespace Tests\Feature;

use App\Content\Example;
use App\Content\Package;
use App\Data\SchoolPosterior;
use App\Livewire\ScriptVignette;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_landing_page_links_every_package(): void
    {
        $response = $this->get('http://phpstats.test/')->assertOk()->assertSee('Modern statistical computing for PHP.');

        foreach (Package::keys() as $key) {
            $response->assertSee("http://{$key}.phpstats.test", false);
        }
    }

    public function test_landing_tabs_come_from_markdown_pages_that_call_php(): void
    {
        $response = $this->get('http://phpstats.test/')->assertOk()
            ->assertSee('Simplify Statistical Workflows in PHP Applications with Native PHP')
            ->assertSee('Read data from your app')
            ->assertSee('&lt;date&gt;', false)            // phibble's inferred column types
            ->assertSee('2,400 rows')
            ->assertDontSee('failed to run')
            ->assertDontSee('checked against R')
            ->assertDontSee('<!--blade:', false);

        if (function_exists('Phit\\fit_lm')) {
            $response->assertSee('customerReturning');      // the lm() coefficients
        }
    }

    public function test_every_package_has_code_on_the_landing_page_and_it_runs(): void
    {
        $requires = ['phibble' => 'phibble', 'phpolars' => 'phpolars', 'phplyr' => 'phplyr', 'phiz' => 'phiz', 'phit' => 'phit'];

        $response = $this->get('http://phpstats.test/')->assertOk();
        foreach (array_keys(config('phpstats.explorer')) as $key) {
            $this->assertFileExists(Example::path("packages/{$key}"));
            $response->assertSee("package-code-{$key}", false);
        }

        // CmdStanPHP's snippet needs CmdStan, which pages never run: its output is the stored fit's summary.
        foreach ($requires as $key => $package) {
            if (ScriptVignette::available($package) && ScriptVignette::available('phplyr')) {
                $this->assertNotNull(Example::run("packages/{$key}"), $key);
            }
        }
        $this->assertSame(['beta[1]', 'beta[2]', 'sigma', 'tau'], SchoolPosterior::explorerSummary()->column('variable'));
        $response->assertSee('PHP 8.3–8.4')->assertDontSee('· Pipe')->assertSee('More about')->assertDontSee("can't run the example");
    }

    public function test_juphyter_is_on_the_site_without_an_explorer_panel(): void
    {
        $juphyter = Package::find('juphyter');

        // It is in the menus and the footer, but the explorer has no example for it.
        $this->get('http://phpstats.test/')->assertOk()
            ->assertSee($juphyter->url(), false)
            ->assertSee('PHP in Jupyter notebooks and Quarto documents.')
            ->assertDontSee('package-tab-juphyter', false)
            ->assertDontSee('package-code-juphyter', false);

        // The explorer still opens on phibble and panels every package that has an example.
        $explorer = $this->get('http://phpstats.test/')->assertOk();
        foreach (array_keys(config('phpstats.explorer')) as $key) {
            $explorer->assertSee("package-code-{$key}", false);
        }
        $this->assertSame(
            ['phibble', 'phpolars', 'phplyr', 'phiz', 'phit', 'cmdstanphp'],
            array_keys(config('phpstats.explorer')),
        );

        // Its own page stands on the overview from config/projects.php, with no Docs tab.
        $this->get($juphyter->url())->assertOk()
            ->assertSee($juphyter->tagline())
            ->assertSee('IRkernel')
            ->assertSee('has not published its site content yet')
            ->assertSee($juphyter->repository(), false)
            ->assertDontSee($juphyter->url('docs'), false);
    }
}
