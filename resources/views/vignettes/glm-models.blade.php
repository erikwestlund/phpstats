<x-layout title="Binary and count outcomes">
    <article class="max-w-3xl">
        <h1 class="text-3xl font-semibold tracking-tight">Binary and count outcomes</h1>
        <p class="mt-4 text-zinc-700 dark:text-zinc-300">
            This vignette fits generalized linear models from R formulas.
            phplyr's glm() passes the formula and the phibble frame to php-stats' stats_glm(), which builds R's model matrix and fits the model.
            The coefficient names, deviances, and AIC follow R's glm().
        </p>
        <p class="mt-4 text-zinc-700 dark:text-zinc-300">
            The logistic and probit models use 80 simulated students, where passed is 1 when a student passed and 0 otherwise.
            Those outcomes were drawn from a logistic model, so the probit fit shows how the coefficients change with the link.
            The Poisson model uses 120 simulated clinic records, where calls is a count that rises with visitors and falls with staff.
            Both data sets come from the php-stats documentation and are read from CSV files.
            Checking the interaction box replaces + with *, which adds the product of the two predictors.
        </p>
    </article>

    <div class="mt-10">
        <livewire:glm-models />
    </div>
</x-layout>
