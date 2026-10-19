---
title: Build dashboards in Livewire
icon: cursor-arrow-rays
packages: [phplyr, phiz]
---

In a Livewire component, inputs are properties. When one changes, the server reruns the pipeline and phiz redraws the chart.

<x-source path="app/Livewire/RevenueByMonth.php" />

<x-source path="resources/views/livewire/revenue-by-month.blade.php" language="blade" class="mt-3" />

<x-result class="mt-3"><livewire:revenue-by-month /></x-result>
