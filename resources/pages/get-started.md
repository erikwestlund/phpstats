---
title: Get started
description: Installing the PHPstats packages with Composer and PIE, and the requirements of each package.
---

## Requirements

- All packages support PHP 8.3 and later. CmdStanPHP also supports PHP 8.2.
- Composer installs the PHP libraries.
- [PIE](https://github.com/php/pie) installs the phit and phpolars extensions.
  Building either extension from source requires a stable Rust toolchain and
  libclang.

### The pipe operator

Examples on this site use the pipe operator `|>`, a common idiom in R data
workflows. The pipe operator requires PHP 8.5. Each example also shows the
same code as a method chain for PHP 8.3 and 8.4.

## Installation

All packages are published on Packagist.

### Packages

<x-install package="phibble" command="composer require phpstats/phibble" />

<x-install package="phplyr" command="composer require phpstats/phplyr" />

<x-install package="phiz" command="composer require phpstats/phiz" />

<x-install package="cmdstanphp" command="composer require phpstats/cmdstanphp" />

<x-install package="juphyter" command="composer global require phpstats/juphyter" />

### Extensions

phit and phpolars are PHP extensions written in Rust. PIE installs them and
enables each one in `php.ini`. On Linux, PIE installs a prebuilt phpolars
binary for PHP 8.3, 8.4, and 8.5 on x86_64 and arm64. PIE builds phit, and
phpolars on macOS, from source.

<x-install package="phit" command="pie install phpstats/phit" />

<x-install package="phpolars" command="pie install phpstats/phpolars" />
