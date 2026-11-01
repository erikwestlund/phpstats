<?php

use App\Content\Markdown;
use App\Content\Package;
use App\Http\Controllers\ExampleIndexController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

$packages = implode('|', array_map(fn (string $key) => preg_quote($key, '#'), Package::keys()));
$companions = config('phpstats.companions');

// One host per package: {package}.phpstats.org, or phpstats-{package}.pequod.sh on
// staging. In path mode, for previews behind a single tunnel URL, each package
// is served under /{package} on whatever host the request came in on.
$paths = config('phpstats.paths');

// Every host answers robots.txt, the hosts the site is not published on
// included, so the route takes no domain. Laravel matches the routes that do
// have one first, so the package hosts need their own, ahead of the companion
// catch-all below. RouteCollection::get() returns domainRoutes before routes,
// whatever order they were registered in.
//
// Do not delete the registration below because the suite is green. Without
// it, phit-polyfill.phpstats.test/robots.txt answers the companion redirect,
// a 301, instead of robots.txt. One reviewer's run passed the companion-host
// test without it for reasons nobody could explain, so a green suite is not
// evidence that one registration is enough. Check the response on a
// companion host by hand before touching this.
if (! $paths) {
    Route::domain(config('phpstats.package_host'))->get('/robots.txt', RobotsController::class);
}
Route::get('/robots.txt', RobotsController::class)->name('robots');

// Companions, such as phit-polyfill, are part of their parent's pages.
Route::domain($paths ? null : config('phpstats.package_host'))
    ->prefix($paths ? '{package}' : '')
    ->whereIn('package', array_keys($companions))
    ->group(function () use ($companions) {
        Route::get('/{any?}', fn (string $package) => redirect()->away(Package::find($companions[$package])->url(), 301))->where('any', '.*');
    });

Route::domain($paths ? null : config('phpstats.package_host'))
    ->prefix($paths ? '{package}' : '')
    ->where(['package' => $packages])
    ->name('package.')
    ->controller(PackageController::class)
    ->group(function () {
        Route::get('/', 'overview')->name('overview');
        Route::get('/docs/{path?}', 'docs')->where('path', '[A-Za-z0-9._/-]+')->name('docs');
        Route::get('/examples', 'examples')->name('examples');
        // The Examples tab was /vignettes until the 2026-10-31 curation.
        Route::get('/vignettes', fn (string $package) => redirect()->to(Package::find($package)->url('examples'), 301));
        Route::get('/vignettes/{slug}', 'vignette')->where('slug', '[a-z0-9-]+')->name('vignette');
        // Benchmarks are one page, with each workload at /benchmarks#{id};
        // /benchmarks/{id} redirects there. Verification has a page per
        // topic, named by the record's file, and its section page sends an
        // old /verification#{id} link on to the topic.
        Route::get('/benchmarks', 'benchmarks')->name('benchmarks');
        Route::get('/benchmarks/{id}', 'benchmark')->where('id', '[a-z0-9-]+')->name('benchmark');
        Route::get('/verification', 'verification')->name('verification');
        Route::get('/verification/{id}', 'verificationTopic')->where('id', '[a-z0-9-]+')->name('verification.topic');
        Route::get('/verifications/{id?}', fn (string $package, ?string $id = null) => redirect()->to(Package::find($package)->url($id === null ? 'verification' : 'verification.topic', $id === null ? [] : ['id' => $id]), 301))->where('id', '[a-z0-9-]+');
    });

Route::domain($paths ? null : config('phpstats.root_host'))->group(function () use ($paths) {
    Route::view('/', 'home')->name('home');

    // Before the {page} catch-all below, so the sitemap is never looked for
    // among the Markdown pages.
    Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

    // Paths from the single-host site.
    $renamed = ['php-stats' => 'phit', 'php-stats-polyfill' => 'phit', 'school-effects' => 'school-shrinkage'];
    Route::get('/{project}', fn (string $project) => redirect()->away(
        Package::find($renamed[$project] ?? $project)->url(), 301,
    ))->whereIn('project', $paths ? ['php-stats', 'php-stats-polyfill'] : [...Package::keys(), 'php-stats', 'php-stats-polyfill']);

    // The index of every example on the site, before the {page} catch-all,
    // which would otherwise look for a Markdown page named examples. It was
    // /vignettes until the 2026-10-31 curation.
    Route::get('/examples', ExampleIndexController::class)->name('examples');
    Route::get('/vignettes', fn () => redirect()->to(route('examples'), 301));
    Route::get('/vignettes/{slug}', function (string $slug) use ($renamed) {
        $slug = $renamed[$slug] ?? $slug;
        if ($owner = config("phpstats.vignettes.{$slug}.packages.0")) {
            return redirect()->away(Package::find($owner)->url('vignette', ['slug' => $slug]), 301);
        }

        return redirect()->away(Package::exampleRedirect($slug) ?? abort(404), 301);
    });
    Route::get('/benchmarks', fn () => redirect()->away(Package::find('phit')->url('benchmarks'), 301));
    Route::get('/get-started', fn () => redirect()->to(route('page', 'installation'), 301));

    // Markdown pages in resources/pages, such as /about from about.md.
    Route::get('/{page}', function (string $page) {
        $file = resource_path("pages/{$page}.md");
        abort_unless(is_file($file), 404);

        return view('page', Markdown::page($file));
    })->where('page', '[a-z0-9-]+')->name('page');
});
