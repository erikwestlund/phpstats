<?php

namespace App\Http\Controllers;

use App\Content\Package;
use Illuminate\Contracts\View\View;

/**
 * The landing host's index of the curated examples, grouped by the package
 * that owns each one. Every entry links to its page in that package's docs.
 */
class ExampleIndexController extends Controller
{
    public function __invoke(): View
    {
        $groups = [];
        foreach (Package::all() as $package) {
            if ($examples = $package->examples()) {
                $groups[] = ['package' => $package, 'examples' => $examples];
            }
        }

        return view('examples', ['groups' => $groups]);
    }
}
