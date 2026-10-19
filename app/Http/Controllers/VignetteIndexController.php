<?php

namespace App\Http\Controllers;

use App\Content\Package;
use Illuminate\Contracts\View\View;

/**
 * The landing host's index of every vignette on the site. Each package host
 * lists its own, and this is the one page that shows all of them together,
 * grouped by the package whose host runs each one.
 */
class VignetteIndexController extends Controller
{
    public function __invoke(): View
    {
        $groups = [];

        foreach (Package::all() as $key => $package) {
            // A vignette is listed by every package it uses, so take it from its owner.
            $own = array_values(array_filter($package->vignettes(), fn (array $vignette) => $vignette['owner'] === $key));

            if ($own !== []) {
                $groups[] = ['package' => $package, 'vignettes' => $own];
            }
        }

        return view('vignette-index', [
            'groups' => $groups,
            'total' => array_sum(array_map(fn (array $group) => count($group['vignettes']), $groups)),
        ]);
    }
}
