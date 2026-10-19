---
title: Read data from your app
icon: circle-stack
---

phibble reads tables from Eloquent and Doctrine queries, PDO statements, and CSV files into immutable data frames. Each column gets a type, and missing values are PHP `null`. The examples on this page use an `orders` table of 2,400 simulated orders from an online store.

<x-example name="orders/read" requires="phibble" />
