---
title: Transform with familiar verbs
icon: adjustments-horizontal
packages: [phplyr]
---

phplyr covers most of dplyr and tidyr: filtering, mutating, grouping and summarising, joins, window functions, and reshaping. A pipeline is written once and can run in three places.

| Backend | Where it runs | Suited to |
| --- | --- | --- |
| phibble frame | In PHP memory | Small and medium tables |
| SQL | In the database, as one query | Tables that stay in the database |
| phpolars | In the Polars engine | Large tables and heavy grouping |

The verbs keep their R names, so `group_by()`, `summarise()`, `pivot_longer()`, and `across()` work as they do in dplyr and tidyr.
