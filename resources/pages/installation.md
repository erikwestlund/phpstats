---
title: Installation
description: The commands that install each PHPstats package, what each one requires, and how to check the install.
sidebar:
  - items:
      - { id: requirements, label: Requirements }
      - { id: phibble, label: phibble, mono: true }
      - { id: phplyr, label: phplyr, mono: true }
      - { id: phiz, label: phiz, mono: true }
      - { id: cmdstanphp, label: CmdStanPHP, mono: true }
      - { id: phit, label: phit, mono: true }
      - { id: phpolars, label: phpolars, mono: true }
      - { id: juphyter, label: juphyter, mono: true }
---

<h2 id="requirements">Requirements</h2>

- Every package supports PHP 8.3, 8.4, and 8.5. CmdStanPHP also supports PHP 8.2.
- [Composer](https://getcomposer.org) installs the PHP libraries from Packagist.
- [PIE](https://github.com/php/pie) installs the phit and phpolars extensions.

<x-install package="phibble" />

<x-install package="phplyr" />

<x-install package="phiz" />

<x-install package="cmdstanphp" />

<x-install package="phit" />

<x-install package="phpolars" />

<x-install package="juphyter" />
