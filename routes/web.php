<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'home', ['projects' => config('projects')])->name('home');

Route::view('/vignettes/school-scores', 'vignettes.school-scores')->name('vignettes.school-scores');
Route::view('/vignettes/simulated-rents', 'vignettes.simulated-rents')->name('vignettes.simulated-rents');

Route::get('/{project}', function (string $project) {
    return view('project', ['project' => config("projects.{$project}")]);
})->whereIn('project', array_keys(config('projects')))->name('project');
