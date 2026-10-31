<x-layout title="Bayesian school effects">
    <article class="max-w-3xl">
        <h1 class="text-3xl font-semibold tracking-tight">Bayesian school effects</h1>
        <p class="mt-4 text-zinc-700 dark:text-zinc-300">
            This example fits the school effects model, score ~ study_hours + (1 | school), as a Bayesian model in Stan and compares the posterior with the Phit\fit_lmer() estimates.
            CmdStanPHP builds the Stan data from the same formula through phit, then runs CmdStan.
        </p>
        <p class="mt-4 text-zinc-700 dark:text-zinc-300">
            Compiling and sampling a Stan model takes far longer than a page request, so the page does not run CmdStan.
            It reads draws from a seeded fit that is stored in the repository.
            The command php artisan stan:fit-school-posterior regenerates them, and only that command needs CmdStan.
        </p>

        <h2 class="mt-8 text-sm font-medium uppercase tracking-wide text-zinc-500">Code</h2>
        <pre class="mt-3 overflow-x-auto rounded-lg bg-zinc-100 p-4 text-sm dark:bg-zinc-900"><code>$df = DataFrame::fromQuery(StudentScore::query());
$data = StanData::fromFormula('{{ \App\Data\SchoolPosterior::FORMULA }}', $df);
// N, K, X (with the intercept column), y, J, and school_id

$model = cmdstan_model('school_intercepts.stan');
$fit = $model->sample(data: $data, seed: {{ \App\Data\SchoolPosterior::SEED }}, chains: 4, thin: 4);
$draws = $fit->drawsColumns(['beta', 'sigma', 'tau', 'school_intercept']);</code></pre>

        <details class="mt-3 text-sm">
            <summary class="cursor-pointer text-zinc-600 dark:text-zinc-400">Stan program</summary>
            <pre class="mt-2 overflow-x-auto rounded-lg bg-zinc-100 p-4 dark:bg-zinc-900"><code>{{ file_get_contents(resource_path('stan/school_intercepts.stan')) }}</code></pre>
        </details>
        <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">
            The priors are weakly informative: normal(50, 25) on the intercept, normal(0, 10) on the slope, and half-Student-t(3, 0, 10) on σ and τ.
            The random intercepts are non-centered.
        </p>
    </article>

    <div class="mt-10">
        <livewire:bayesian-schools />
    </div>
</x-layout>
