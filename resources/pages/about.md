---
title: Why PHPstats
lead: PHPstats is a family of libraries for data analysis in native PHP. It is inspired by the Tidyverse in R.
---

## Analysis where the data already is

Many teams keep their data in a PHP application. To analyze it, they export it to R or Python, or they run a separate service next to the app. PHPstats fits the models in the application itself. It reads tables from the application database through Eloquent, Doctrine, or PDO, and it fits regression and mixed models whose results are checked against R.

## One job per package

Each package does one job, and the packages share one data frame type. They compose with method chains or with the PHP 8.5 pipe operator.

<x-family-table />

## An alternative to Shiny for Laravel

With Laravel and Livewire, the same code drives interactive dashboards and reports. Inputs are component properties, the server refits the model when one changes, and phiz draws the charts. The page needs no separate R or Python server and no hand-written JavaScript. Every vignette on this site is built this way.

## Checked against R

The verification reports compare {{ number_format($stats['verified']) }} results with R, SciPy, statsmodels, mpmath, and py-polars. Each comparison is published with its tolerance. The benchmark reports give the machine, the PHP version, and the date of each run.

## Editing this site

The pages written for this site, including this one, are Markdown files in `resources/pages`. They can call PHP through Blade components and echoes, so a page can show live numbers and charts. Package documentation comes from each repository's Quarto site at a pinned commit.
