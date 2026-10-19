---
title: Scale up with Polars
icon: bolt
---

phpolars brings the Polars engine to PHP as an extension. A phplyr pipeline runs on a Polars table unchanged, and grouping and joining a million rows takes milliseconds.

<x-example name="orders/polars" :requires="['phplyr', 'phpolars']" />
