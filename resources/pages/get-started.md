---
title: Get started
description: Which PHPstats package does what, what each one needs, and the commands that install and check it.
---

## Choose a package

<x-package-chooser />

## Requirements

- Every package supports PHP 8.3, 8.4, and 8.5. CmdStanPHP also supports
  PHP 8.2.
- Composer installs the PHP libraries and the PHP packages they depend on.
  Installing phplyr also installs phibble.
- [PIE](https://github.com/php/pie) installs the phit and phpolars
  extensions.

## Installation

All packages are published on Packagist.

### PHP libraries

<x-install package="phibble" />

<x-install package="phplyr" />

<x-install package="phiz" />

<x-install package="cmdstanphp" />

### Extensions

phit and phpolars are PHP extensions written in Rust. They run on
non-thread-safe (NTS) builds of PHP on Linux and macOS. PIE downloads or
builds the extension for the PHP binary that runs it and enables it in that
binary's `php.ini`. On Linux with glibc, on x86_64 or arm64, PIE installs a
prebuilt binary of either extension and needs `unzip` or PHP's zip extension
to unpack it. On macOS and other platforms it builds the extension from
source.

PHP-FPM can read a different `php.ini` from the command line. After
installing, enable the extension for PHP-FPM as well and restart it.

<x-install package="phit" />

<x-install package="phpolars" />

### Notebooks

<x-install package="juphyter" />

### The pipe operator

Examples on this site use the pipe operator `|>`, a common idiom in R data
workflows. The pipe operator requires PHP 8.5. Each example also shows the
same code as a method chain for PHP 8.3 and 8.4.
