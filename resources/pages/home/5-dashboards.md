---
title: Build dashboards in Livewire
icon: cursor-arrow-rays
packages: [phiz, phplyr, phit]
---

With Laravel and Livewire, the analysis code also drives interactive pages, which makes PHPstats an alternative to Shiny for PHP teams. Inputs are component properties. When one changes, the server reruns the pipeline and refits the model, and `<x-phiz>` redraws the charts. These vignettes run live on this server.

<x-explore.vignettes :items="['phplyr/rent-dashboard', 'phit/school-shrinkage', 'phit/glm-models', 'phpolars/lazy-queries']" />
