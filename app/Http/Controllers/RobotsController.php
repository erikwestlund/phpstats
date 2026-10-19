<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * robots.txt, built for the host that asked rather than served as one file
 * for all of them. Crawlers are invited only where the request host is one
 * the site is published on and config('phpstats.indexable') is on, which it
 * is only in production. Staging, tunnels, and previews answer Disallow: /,
 * because their pages each declare a canonical URL of their own and would
 * otherwise compete with production in search results.
 */
class RobotsController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow:'.(config('phpstats.indexable') && $this->published($request->getHost()) ? '' : ' /'),
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /**
     * The hosts of config/phpstats.php: the landing host and, unless path
     * mode puts every package on that one host, a host per package.
     * Companions such as phit-polyfill are included, because their hosts
     * redirect to their parent instead of answering 404.
     */
    private function published(string $host): bool
    {
        $hosts = [config('phpstats.root_host')];

        if (! config('phpstats.paths')) {
            foreach (array_keys(config('projects')) as $key) {
                $hosts[] = str_replace('{package}', $key, config('phpstats.package_host'));
            }
        }

        return in_array(mb_strtolower($host), array_map(mb_strtolower(...), $hosts), true);
    }
}
