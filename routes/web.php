<?php

use App\Content\Package;
use App\Http\Controllers\PackageController;
use Illuminate\Support\Facades\Route;

$packages = implode('|', array_map(fn (string $key) => preg_quote($key, '#'), array_keys(config('projects'))));

// One host per package: {package}.phpstats.org, or phpstats-{package}.pequod.sh on staging.
Route::domain(config('phpstats.package_host'))
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

Route::domain(config('phpstats.root_host'))->group(function () {
    Route::view('/', 'home')->name('home');

    // Paths from the single-host site.
    $renamed = ['php-stats' => 'phit', 'php-stats-polyfill' => 'phit-polyfill', 'school-effects' => 'school-shrinkage'];
    Route::get('/{project}', fn (string $project) => redirect()->away(
        Package::find($renamed[$project] ?? $project)->url(), 301,
    ))->whereIn('project', [...array_keys(config('projects')), 'php-stats', 'php-stats-polyfill']);
    Route::get('/vignettes/{slug}', function (string $slug) use ($renamed) {
        $slug = $renamed[$slug] ?? $slug;
        $owner = config("phpstats.vignettes.{$slug}.packages.0") ?? abort(404);

        return redirect()->away(Package::find($owner)->url('vignette', ['slug' => $slug]), 301);
    });
    Route::get('/benchmarks', fn () => redirect()->away(Package::find('phit')->url('benchmarks'), 301));
});
