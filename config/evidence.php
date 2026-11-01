<?php

/*
 * Benchmark and verification records that were renamed or merged, by
 * package and section: the old record id => the id of the record that
 * replaced it. /benchmarks/{old} and /verification/{old}, and the
 * /benchmarks#{old} links from before topics had their own pages, go to the
 * replacement. A package can also list them in site.json's
 * evidence_redirects, in the same shape.
 */
return [
    'renamed' => [
        'phpolars' => [
            // Merged into one run on 2026-11-01.
            'benchmarks' => ['py-polars' => 'read-group-join', 'php-arrays' => 'read-group-join'],
        ],
    ],

    /*
     * Docs pages that left a package's docs for its evidence sections, by
     * package: the docs path => 'benchmarks' or 'verification'. Pages under
     * benchmarks/, verifications/, and notebooks/*-verification, and a page
     * named verification, are matched without an entry here.
     */
    'docs' => [
        'cmdstanphp' => ['testing' => 'verification'],
    ],

    /*
     * Sections of docs pages that moved to the evidence pages, by package
     * and docs path: the old #fragment => [package, 'benchmark' or
     * 'verification.topic', record id]. Fragments never reach the server, so
     * the docs page sends the reader on in the browser, and only when the
     * page no longer has an element with that id.
     */
    'fragments' => [
        'phplyr' => [
            'performance' => [
                'methods' => ['phplyr', 'benchmark', 'workloads'],
                'phplyrs-overhead' => ['phplyr', 'benchmark', 'workloads'],
                'phibble-workloads-at-1000000-rows' => ['phplyr', 'benchmark', 'workloads'],
                'backend-pipelines' => ['phplyr', 'benchmark', 'backends'],
            ],
        ],
        'phpolars' => [
            'performance' => [
                'a-csv-pipeline' => ['phpolars', 'benchmark', 'csv-pipeline'],
                'phplyr-pipelines-on-three-backends' => ['phplyr', 'benchmark', 'backends'],
            ],
        ],
    ],
];
