<?php

namespace App\Http\Controllers;

use App\Content\Package;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\View as ViewFactory;

class PackageController extends Controller
{
    public function overview(string $package): View
    {
        return $this->page($package, 'overview');
    }

    public function docs(string $package, ?string $path = null)
    {
        $item = $this->package($package);
        if (! $item->hasDocs()) {
            return $item->externalDocs() ? redirect()->away($item->externalDocs()) : abort(404);
        }
        // Quarto's sitemaps and the GitHub Pages redirects name a page x.html.
        if ($path !== null && str_ends_with($path, '.html')) {
            $bare = substr($path, 0, -5);
            if ($bare === 'index') {
                return redirect()->to($item->url('docs'), 301);
            }
            if ($item->docsPage($bare) !== null) {
                return redirect()->to($item->url('docs', ['path' => $bare]), 301);
            }
        }
        $page = $item->docsPage($path ?? 'index') ?? abort(404);

        return $this->page($package, 'docs', ['page' => $page, 'path' => $path ?? 'index']);
    }

    public function examples(string $package): View
    {
        return $this->page($package, 'examples');
    }

    /**
     * A live page kept outside the curated examples, or a redirect to the
     * docs page that replaced an example that left the live pages.
     */
    public function vignette(string $package, string $slug)
    {
        $item = $this->package($package);
        $vignette = $item->vignette($slug);
        if ($vignette === null) {
            return redirect()->away(Package::exampleRedirect($slug) ?? abort(404), 301);
        }

        if ($vignette['kind'] === 'livewire') {
            ViewFactory::share('currentPackage', $item);

            return view("vignettes.{$slug}");
        }

        return $this->page($package, 'vignette', ['vignette' => $vignette]);
    }

    public function benchmarks(string $package): View
    {
        return $this->page($package, 'benchmarks');
    }

    public function verification(string $package): View
    {
        return $this->page($package, 'verification');
    }

    private function package(string $key): Package
    {
        return Package::find($key) ?? abort(404);
    }

    private function page(string $key, string $section, array $data = []): View
    {
        $package = $this->package($key);
        ViewFactory::share('currentPackage', $package);

        return view("package.{$section}", ['package' => $package, ...$data]);
    }
}
