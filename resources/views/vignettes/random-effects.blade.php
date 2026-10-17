<x-layout title="Random-effect intervals">
    <article class="max-w-3xl">
        <h1 class="text-3xl font-semibold tracking-tight">Random-effect intervals</h1>
        <p class="mt-4 text-zinc-700 dark:text-zinc-300">
            A mixed model estimates each school's random effect as a conditional mode, the most likely value of the effect given the data and the estimated variance components.
            Phit\fit_lmer() also returns the conditional variance of each mode, the quantity that lme4 reports with ranef(condVar = TRUE).
            Those variances give each school an interval.
        </p>
        <p class="mt-4 text-zinc-700 dark:text-zinc-300">
            This vignette fits the school scores data with random intercepts, then with random study-hours slopes.
            phiz's random_effects_plot() draws the caterpillar plot from the fit alone, using the reported variances.
            The intervals treat the fixed effects and variance components as known, as lme4's do.
        </p>
    </article>

    <div class="mt-10">
        <livewire:random-effects />
    </div>
</x-layout>
