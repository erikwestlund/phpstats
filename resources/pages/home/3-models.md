---
title: Fit regression models
icon: variable
packages: [phit]
---

phit fits linear, logistic, probit, and Poisson regression and mixed models from R formulas. Factors such as `channel` become indicator columns, with the first level as the reference.

<x-example name="orders/model" :requires="['phplyr', 'phit']" />
