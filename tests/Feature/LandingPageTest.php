<?php

namespace Tests\Feature;

use App\Content\Example;
use App\Content\Package;
use App\Data\JuphyterCell;
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
        foreach (config('phpstats.explorer') as $key => $entry) {
            // juphyter's example is a Quarto document, which no page runs.
            $this->assertFileExists(
                isset($entry['source'])
                    ? resource_path('examples/'.$entry['source']['file'])
                    : Example::path("packages/{$key}"),
            );
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

    public function test_juphyter_has_an_explorer_panel_with_the_cell_its_kernel_rendered(): void
    {
        $juphyter = Package::find('juphyter');
        Example::reset();

        $response = $this->get('http://phpstats.test/')->assertOk()
            ->assertSee($juphyter->url(), false)
            ->assertSee('A Jupyter and Quarto kernel for PHP.')
            ->assertSee('package-tab-juphyter', false)
            ->assertSee('package-code-juphyter', false);

        // The example is a Quarto document, so the panel shows the front
        // matter line that makes the kernel run the cell below it.
        $response->assertSee('jupyter: php')->assertSee('group_by');

        // Its output is the cell the kernel drew, with phibble's size in the
        // label rather than in the markup, and the word tibble nowhere.
        $cell = JuphyterCell::explorerOutput();
        $this->assertSame('3 × 4', $cell['size']);
        $response->assertSee('Rendered notebook cell · 3 × 4')
            ->assertSee('juphyter-table', false)
            ->assertDontSee("can't run the example");
        // phibble's own bullet links tibble, so the rule is checked on the
        // cell rather than on the page.
        $this->assertStringNotContainsString('tibble', $cell['html']);

        // No page request may start a kernel or run Quarto: the cell is read
        // from the committed fixture, so no example file runs for juphyter.
        $this->assertArrayNotHasKey(Example::path('packages/juphyter'), Example::runs());

        // Every package in a layer now has a panel, in the layers' order.
        foreach (array_keys(config('phpstats.explorer')) as $key) {
            $response->assertSee("package-code-{$key}", false);
        }
        $this->assertSame(
            ['phibble', 'phpolars', 'phplyr', 'juphyter', 'phiz', 'phit', 'cmdstanphp'],
            array_keys(config('phpstats.explorer')),
        );

        // Its own page still stands on the overview from config/projects.php,
        // with no Docs tab, because it syncs no site content yet.
        $this->get($juphyter->url())->assertOk()
            ->assertSee($juphyter->tagline())
            ->assertSee('IRkernel')
            ->assertSee('has not published its site content yet')
            ->assertSee($juphyter->repository(), false)
            ->assertDontSee($juphyter->url('docs'), false);
    }
}
