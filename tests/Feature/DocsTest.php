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

    public function test_docs_nav_is_grouped_by_folder(): void
    {
        $groups = Package::find('phpolars')->docsGroups();

        $this->assertSame(['Guide', 'Reference'], array_keys($groups));
        $this->assertContains('reference/series', array_column($groups['Reference'], 'path'));
        $this->assertSame('Notebooks', Package::find('phit')->docsGroupOf('notebooks/lmm-math'));
    }

    public function test_synced_docs_render_with_resolved_links(): void
    {
        $package = Package::find('phit');
        $this->assertTrue($package->hasDocs());

        $index = $this->get('http://phit.phpstats.test/docs')->assertOk();
        foreach ($package->docsNav() as $item) {
            $index->assertSee($item['title']);
            $page = $this->get($package->url('docs', ['path' => $item['path'] === 'index' ? null : $item['path']]))->assertOk();
            $this->assertStringNotContainsString('phpstats://', $page->getContent());
        }

        $this->get('http://phit.phpstats.test/docs/notebooks/lmm-math')->assertOk()
            ->assertSee('class="math inline"', false)
            ->assertSee('mathjax@4', false);
    }

    public function test_benchmarks_and_verification_render_every_file(): void
    {
        foreach (['phit', 'phpolars'] as $key) {
            $package = Package::find($key);
            $this->assertNotEmpty($package->benchmarks());
            $this->assertNotEmpty($package->verifications());

            $benchmarks = $this->get($package->url('benchmarks'))->assertOk();
            foreach ($package->benchmarks() as $benchmark) {
                $benchmarks->assertSee($benchmark['title']);
            }
            $verification = $this->get($package->url('verification'))->assertOk();
            foreach ($package->verifications() as $report) {
                $verification->assertSee($report['title']);
            }
        }
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
        $docs['nav'] = array_values(array_filter(
            $docs['nav'],
            fn (array $item) => in_array($item['path'], $paths, true),
        ));
        File::put($file, json_encode($docs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        config(['phpstats.content_path' => $root]);
        Package::flush();
        $this->beforeApplicationDestroyed(fn () => File::deleteDirectory($root));
    }
}
