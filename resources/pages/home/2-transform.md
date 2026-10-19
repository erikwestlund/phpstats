---
title: Transform data with the pipe
icon: adjustments-horizontal
packages: [phplyr]
---

phplyr ports dplyr and tidyr to PHP, with the same function names. On PHP 8.5, a pipeline is written with the pipe operator `|>`, as in R. PHP 8.3 and 8.4 use a method chain. The same pipeline runs on a phibble frame, in the database as SQL, or in Polars.

<x-example name="orders/transform" requires="phplyr" />
