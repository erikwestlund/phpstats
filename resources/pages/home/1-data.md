---
title: Read data from your app
icon: circle-stack
packages: [phibble, phpolars]
---

phibble reads tables from CSV files, PDO statements, and Eloquent or Doctrine queries, so an analysis can start from the application database. It infers a type for each column, and missing values are PHP `null`. Every operation returns a new frame.

<x-explore.csv-preview file="schools.csv" :rows="5" />

For larger data, phpolars provides the same table contract on top of the Polars engine.
