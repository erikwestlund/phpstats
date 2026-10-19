---
title: About PHPstats
lead: PHPstats is a family of PHP libraries for data processing, analysis, visualization, and modeling.
---

## What PHPstats is

Many teams keep their data in a PHP application. To analyze it, they often export it to R or Python, or they run a separate service next to the app. PHPstats does that work in the application. It reads tables from the application database through Eloquent, Doctrine, or PDO. It transforms them, fits statistical models, and draws charts, all in the code the team already deploys.

## The packages

The packages fall into three groups: data frames, processing and visualization, and statistical modeling. They share one data frame type, so a table read by phibble can be transformed by phplyr, modeled by phit, and charted by phiz. Each package can also be used on its own.

<x-family-table />

## Design

The packages follow R's Tidyverse where it has a convention. phplyr keeps the function names and arguments of dplyr and tidyr, phiz follows the grammar of ggplot2, and phit and CmdStanPHP read R model formulas. On PHP 8.5, pipelines use the pipe operator `|>`. PHP 8.3 and 8.4 use a method chain with the same functions.

The compute-heavy parts run in Rust extensions: phit for the models and phpolars for the Polars engine.

## Dashboards with Livewire

With Laravel and Livewire, the same code drives interactive dashboards and reports. Inputs are component properties. When one changes, the server reruns the pipeline, refits the model, and redraws the charts. The page needs no separate R or Python server and no hand-written JavaScript. Every vignette on this site is built this way.

## This site

The pages written for this site, including this one, are Markdown files in `resources/pages`. They can call PHP through Blade components and echoes, so a page can show live numbers and charts. Package documentation comes from each repository's Quarto site at a pinned commit.
