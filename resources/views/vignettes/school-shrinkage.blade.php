<x-layout title="School effects">
    <article class="max-w-3xl">
        <h1 class="text-3xl font-semibold tracking-tight">School effects</h1>
        <p class="mt-4 text-zinc-700 dark:text-zinc-300">
            This vignette fits a linear mixed model to the 250 students from 12 schools in the school scores data.
            Test score depends on study hours, and each school has its own random intercept.
            The model estimates how much schools differ and how much of each school's apparent effect is noise.
        </p>
        <p class="mt-4 text-zinc-700 dark:text-zinc-300">
            The page compares the mixed model with a no-pooling model that gives every school a separate fixed intercept.
            The mixed model pulls each school's intercept toward the overall intercept, and it pulls schools with fewer students further.
        </p>

        <h2 class="mt-8 text-sm font-medium uppercase tracking-wide text-zinc-500">Code</h2>
        <x-code-tabs class="mt-3">
            <x-slot:chain>use function Phit\fit_lmer;

$scores = DataFrame::fromQuery(StudentScore::query());

$mixed = fit_lmer('{{ \App\Livewire\SchoolEffects::FORMULA }}', $scores, reml: true);
$noPooling = phplyr($scores)->lm('{{ \App\Livewire\SchoolEffects::NO_POOLING_FORMULA }}');

$mixed['random'][0]['covariance'];  // school variance
$mixed['random'][0]['effects'];     // one random intercept per school</x-slot:chain>
            <x-slot:pipe>use function Phit\fit_lmer;

$scores = DataFrame::fromQuery(StudentScore::query());

$mixed = fit_lmer('{{ \App\Livewire\SchoolEffects::FORMULA }}', $scores, reml: true);
$noPooling = $scores |> lm('{{ \App\Livewire\SchoolEffects::NO_POOLING_FORMULA }}');

$mixed['random'][0]['covariance'];  // school variance
$mixed['random'][0]['effects'];     // one random intercept per school</x-slot:pipe>
        </x-code-tabs>
        <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">
            Phit\fit_lmer() takes the phibble DataFrame directly. Its results are verified against R's lme4.
        </p>
    </article>

    <div class="mt-10">
        <livewire:school-effects />
    </div>
</x-layout>
