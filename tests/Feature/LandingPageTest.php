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

    public function test_the_landing_page_is_the_hero_and_the_package_explorer(): void
    {
        $html = $this->get('http://phpstats.test/')->assertOk()
            ->assertSee('>Packages</h2>', false)
            ->assertDontSee('Simplify Statistical Workflows')
            ->assertDontSee('About PHPstats Packages')
            ->assertDontSee('checked against R')
            ->assertDontSee('<!--blade:', false)
            ->content();

        // The hero is one workflow: load, summarize, chart. No model fit.
        preg_match('#OrderReport\.php.*?</section>#s', $html, $hero);
        $this->assertStringNotContainsString('lm(', strip_tags($hero[0]));

        // No "About {package}" panels: each package shows code, output, and a link.
        $this->assertDoesNotMatchRegularExpression('#>About <span class="font-mono">#', $html);
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
        $response->assertSee('PHP 8.3–8.4')->assertDontSee('· Pipe')->assertDontSee("can't run the example");

        // Each panel links its fuller example in the docs, a section of a page
        // when the link has a #fragment, or the docs until that page syncs.
        foreach (config('phpstats.explorer') as $key => $entry) {
            $package = Package::find($key);
            [$path, $fragment] = array_pad(explode('#', $entry['link'], 2), 2, null);
            $full = in_array($path, $package->docsPaths(), true);
            $url = $full ? $package->url('docs', ['path' => $path]).($fragment ? "#{$fragment}" : '') : $package->url('docs');
            $response->assertSee('href="'.e($url).'"', false);
            $response->assertSee('aria-label="'.($full ? 'View full example' : 'View documentation').' for '.$package->name().'"', false);
        }
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
        $this->assertMatchesRegularExpression('/jupyter\s*:\s*php/', strip_tags($response->content()));
        $response->assertSee('group_by');

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

        // The fixture has to be the output of the code shown above it, which
        // nothing else here would catch: the panel would look right with any
        // table in it. These are the three rows the kernel returned for the
        // document's grouped summary of the seeded orders.
        foreach ([
            'Web', '1185', '61735.9', '1.83',
            'App', '723', '42157.12', '1.85',
            'Marketplace', '492', '22817.18', '1.74',
        ] as $value) {
            $this->assertStringContainsString($value, $cell['html'], $value);
        }

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

        // Its own page carries its synced overview and links its docs.
        $this->get($juphyter->url())->assertOk()
            ->assertSee($juphyter->tagline())
            ->assertSee('IRkernel')
            ->assertDontSee('has not published its site content yet')
            ->assertSee($juphyter->repository(), false)
            ->assertSee($juphyter->url('docs'), false);
    }
}
