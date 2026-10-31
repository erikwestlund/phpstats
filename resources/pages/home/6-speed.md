---
title: Scale up with Polars
icon: bolt
---

phpolars brings the Polars engine to PHP as an extension. The same phplyr pipeline runs on phibble's `PolarsTable`. In phplyr's backend benchmark, filtering, joining, and summarizing a million rows took 51 ms on an Apple M5 Ultra.

<x-example name="orders/polars" :requires="['phplyr', 'phpolars']" />
