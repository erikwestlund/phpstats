<x-layout title="Bayesian school effects">
    <article class="max-w-3xl">
        <h1 class="text-3xl font-semibold tracking-tight">Bayesian school effects</h1>
        <p class="mt-4 text-zinc-700 dark:text-zinc-300">
            The school effects model, score ~ study_hours + (1 | school), fitted as a Bayesian model in Stan and compared with the Phit\fit_lmer() estimates.
            CmdStanPHP builds the Stan data from the same formula through phit and runs CmdStan.
        </p>

        <h2 class="mt-8 text-sm font-medium uppercase tracking-wide text-zinc-500">Code</h2>
        <pre class="mt-3 overflow-x-auto rounded-lg bg-zinc-100 p-4 text-sm dark:bg-zinc-900"><code>$df = \Phibble\DataFrame::fromQuery(StudentScore::query());
$data = StanData::fromFormula('{{ \App\Data\SchoolPosterior::FORMULA }}', $df);

$model = cmdstan_model('school_intercepts.stan');
$fit = $model->sample(data: $data, seed: {{ \App\Data\SchoolPosterior::SEED }}, chains: 4, thin: 4);
$draws = $fit->drawsColumns(['beta', 'sigma', 'tau', 'school_intercept']);</code></pre>

        <details class="mt-3 text-sm">
            <summary class="cursor-pointer text-zinc-600 dark:text-zinc-400">Stan program</summary>
            <pre class="mt-2 overflow-x-auto rounded-lg bg-zinc-100 p-4 dark:bg-zinc-900"><code>{{ file_get_contents(resource_path('stan/school_intercepts.stan')) }}</code></pre>
        </details>
        <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">
            StanData::fromFormula() gives N, K, the design matrix X with its intercept column, y, J, and school_id.
            The priors are weakly informative: normal(50, 25) on the intercept, normal(0, 10) on the slope, and half-Student-t(3, 0, 10) on σ and τ.
            The random intercepts are non-centered: each school's deviation is τ times a standard normal z, so the sampler explores z and τ separately instead of the funnel between the deviations and τ.
        </p>
    </article>

    <div class="mt-10">
        <livewire:bayesian-schools />
    </div>
</x-layout>
