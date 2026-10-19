---
title: Fit Bayesian models in Stan
icon: sparkles
packages: [cmdstanphp]
---

CmdStanPHP runs Stan programs through CmdStan, with an API patterned after CmdStanR. This model gives each school in an example dataset of test scores its own intercept. It was fit once, and the page reads the stored draws.

<x-source path="resources/examples/schools/stan.php" label="SchoolIntercepts.php" />

<x-result class="mt-3"><x-explore.posterior-chart /></x-result>
