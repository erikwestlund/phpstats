<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'home', ['projects' => config('projects')])->name('home');

Route::view('/vignettes/school-scores', 'vignettes.school-scores')->name('vignettes.school-scores');
Route::view('/vignettes/simulated-rents', 'vignettes.simulated-rents')->name('vignettes.simulated-rents');
Route::view('/vignettes/school-effects', 'vignettes.school-effects')->name('vignettes.school-effects');
Route::view('/vignettes/sql-pipeline', 'vignettes.sql-pipeline')->name('vignettes.sql-pipeline');
Route::view('/vignettes/bayesian-schools', 'vignettes.bayesian-schools')->name('vignettes.bayesian-schools');
Route::view('/vignettes/rent-dashboard', 'vignettes.rent-dashboard')->name('vignettes.rent-dashboard');

Route::view('/benchmarks', 'benchmarks')->name('benchmarks');

Route::get('/{project}', function (string $project) {
    return view('project', ['project' => config("projects.{$project}")]);
})->whereIn('project', array_keys(config('projects')))->name('project');
