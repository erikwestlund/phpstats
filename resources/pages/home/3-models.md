---
title: Fit models, checked against R
icon: variable
packages: [phit]
---

phit fits linear, logistic, probit, and Poisson regression, linear mixed models, and mixed logistic regression from R formulas. It also provides probability distributions and descriptive statistics. It is a Rust extension, and phit-polyfill implements its functions in plain PHP for hosts that cannot install extensions.

Each verification report compares phit's results with R and other references at a stated tolerance.

<x-explore.verification package="phit" />
