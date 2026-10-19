<?php

namespace Tests\Feature;

use App\Content\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
