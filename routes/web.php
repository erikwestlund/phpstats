<?php

use App\Content\Markdown;
use App\Content\Package;
use App\Http\Controllers\PackageController;
use Illuminate\Support\Facades\Route;

$packages = implode('|', array_map(fn (string $key) => preg_quote($key, '#'), Package::keys()));
$companions = config('phpstats.companions');

// One host per package: {package}.phpstats.org, or phpstats-{package}.pequod.sh on
// staging. In path mode, for previews behind a single tunnel URL, each package
// is served under /{package} on whatever host the request came in on.
$paths = config('phpstats.paths');

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
        Route::get('/vignettes', 'vignettes')->name('vignettes');
        Route::get('/vignettes/{slug}', 'vignette')->where('slug', '[a-z0-9-]+')->name('vignette');
        Route::get('/benchmarks', 'benchmarks')->name('benchmarks');
        Route::get('/verification', 'verification')->name('verification');
    });

Route::domain($paths ? null : config('phpstats.root_host'))->group(function () use ($paths) {
    Route::view('/', 'home')->name('home');

    // Paths from the single-host site.
    $renamed = ['php-stats' => 'phit', 'php-stats-polyfill' => 'phit', 'school-effects' => 'school-shrinkage'];
    Route::get('/{project}', fn (string $project) => redirect()->away(
        Package::find($renamed[$project] ?? $project)->url(), 301,
    ))->whereIn('project', $paths ? ['php-stats', 'php-stats-polyfill'] : [...Package::keys(), 'php-stats', 'php-stats-polyfill']);
    Route::get('/vignettes/{slug}', function (string $slug) use ($renamed) {
        $slug = $renamed[$slug] ?? $slug;
        $owner = config("phpstats.vignettes.{$slug}.packages.0") ?? abort(404);

        return redirect()->away(Package::find($owner)->url('vignette', ['slug' => $slug]), 301);
    });
    Route::get('/benchmarks', fn () => redirect()->away(Package::find('phit')->url('benchmarks'), 301));

    // Markdown pages in resources/pages, such as /about from about.md.
    Route::get('/{page}', function (string $page) {
        $file = resource_path("pages/{$page}.md");
        abort_unless(is_file($file), 404);

        return view('page', Markdown::page($file));
    })->where('page', '[a-z0-9-]+')->name('page');
});
