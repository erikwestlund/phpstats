<?php

return [
    /*
     * Where the browser loads vega, vega-lite and vega-embed from:
     * 'cdn' (pinned jsDelivr copies with subresource integrity), a URL prefix serving vega.min.js,
     * vega-lite.min.js and vega-embed.min.js, or 'none' when your bundle already sets window.vegaEmbed.
     */
    'assets' => env('PHIZ_ASSETS', 'none'),

    /*
     * Whether <x-phiz> adds the script tags itself, once per page. Turn this off and put @phizScripts in
     * your layout to control where they go.
     */
    'inject_scripts' => true,

    /*
     * Default vega-embed options for every chart. 'theme' => 'auto' follows the visitor's light or dark
     * mode preference.
     */
    'embed_options' => [
        'actions' => false,
        'theme' => 'auto',
        // The site's palette: PHP purple first, then violet, pink, indigo, and contrasting accents.
        'config' => [
            'background' => 'transparent',
            'font' => 'Inter, ui-sans-serif, system-ui, sans-serif',
            'range' => [
                'category' => ['#777bb4', '#a855f7', '#ec4899', '#6366f1', '#f59e0b', '#14b8a6', '#0ea5e9', '#84cc16'],
                // Ordered and continuous scales run from light to dark PHP purple.
                'ordinal' => ['#c7c9e6', '#9397cb', '#777bb4', '#5a5e9a', '#3f4275'],
                'ramp' => ['#e4e5f3', '#3f4275'],
            ],
        ],
    ],
];
