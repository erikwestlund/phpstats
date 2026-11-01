<?php

namespace App\Http\Controllers;

use App\Content\Package;
use Illuminate\Contracts\View\View;

/**
 * The landing host's index of the curated examples, grouped by the package
 * that owns each one, each group followed by the docs pages that collect
 * more, from config/phpstats.php example_collections. Every entry links to
 * its page in that package's docs.
 */
class ExampleIndexController extends Controller
{
    public function __invoke(): View
    {
        $groups = [];
        // In the menus' order: the layers in config/phpstats.php, then their packages.
        foreach (array_merge(...array_column(config('phpstats.layers'), 'packages')) as $key) {
            $package = Package::find($key);
            if ($package === null) {
                continue;
            }
            // Then the docs pages that collect more examples, such as a gallery.
            $more = [];
            foreach (config("phpstats.example_collections.{$key}", []) as $page) {
                if (in_array($page['path'], $package->docsPaths(), true)) {
                    $more[] = $page + ['url' => $package->url('docs', ['path' => $page['path']]), 'owner' => $key];
                }
            }
            if ($examples = [...$package->examples(), ...$more]) {
                $groups[] = ['package' => $package, 'examples' => $examples];
            }
        }

        return view('examples', ['groups' => $groups]);
    }
}
