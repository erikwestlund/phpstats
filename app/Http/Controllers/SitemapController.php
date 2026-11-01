<?php

namespace App\Http\Controllers;

use App\Content\Package;
use Illuminate\Http\Response;
use XMLWriter;

/**
 * sitemap.xml: every page a crawler should know about, in one file for the
 * whole site. The routes generate the addresses, so the list follows the
 * hosts in config/phpstats.php and, in path mode, the host the request came
 * in on. Companions such as phit-polyfill are not listed, because
 * Package::all() leaves them out and their hosts redirect to their parent.
 */
class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $xml = new XMLWriter;
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->setIndentString('    ');
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('urlset');
        $xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        foreach ($this->urls() as $url) {
            $xml->startElement('url');
            $xml->writeElement('loc', $url);
            $xml->endElement();
        }
        $xml->endElement();
        $xml->endDocument();

        return response($xml->outputMemory(), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /**
     * The landing page, the index of every vignette, the Markdown pages in
     * resources/pages, and each package's sections. A section is listed only
     * where it has something to show, so the sitemap holds no page that says
     * a package has no results yet, and docs are listed only for the packages
     * whose docs are synced: the others redirect to their own site.
     *
     * @return list<string>
     */
    private function urls(): array
    {
        $urls = [route('home'), route('examples')];

        foreach (glob(resource_path('pages/*.md')) as $file) {
            $urls[] = route('page', ['page' => pathinfo($file, PATHINFO_FILENAME)]);
        }

        foreach (Package::all() as $package) {
            $urls[] = $package->url();

            foreach ($package->docsPaths() as $path) {
                $urls[] = $package->url('docs', ['path' => $path === 'index' ? null : $path]);
            }

            $vignettes = $package->vignettes();
            if ($package->listedExamples() !== [] || $vignettes !== []) {
                $urls[] = $package->url('examples');
                foreach ($vignettes as $vignette) {
                    // A vignette listed by several packages belongs to its owner's host.
                    if ($vignette['owner'] === $package->key) {
                        $urls[] = $package->url('vignette', ['slug' => $vignette['slug']]);
                    }
                }
            }

            // The sections and their topic pages, where there are records:
            // a section with none only says so.
            if ($package->benchmarks() !== []) {
                $urls[] = $package->url('benchmarks');
                foreach ($package->benchmarks() as $record) {
                    $urls[] = $package->url('benchmark', ['id' => $record['id']]);
                }
            }

            if ($package->verifications() !== []) {
                $urls[] = $package->url('verification');
                foreach ($package->verifications() as $record) {
                    $urls[] = $package->url('verification.topic', ['id' => $record['id']]);
                }
            }
        }

        return $urls;
    }
}
