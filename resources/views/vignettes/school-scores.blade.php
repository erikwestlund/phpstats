<x-layout title="School scores">
    <article class="max-w-3xl">
        <h1 class="text-3xl font-semibold tracking-tight">School scores</h1>
        <p class="mt-4 text-zinc-700 dark:text-zinc-300">
            This vignette fits a linear regression of test score on study hours and prior score.
            The data has 250 students from 12 schools and is stored in this app's database.
            Each change to the predictors refits the model on the server with phit.
        </p>

        <h2 class="mt-8 text-sm font-medium uppercase tracking-wide text-zinc-500">Code</h2>
        <x-code-tabs class="mt-3">
            <x-slot:chain>$fit = phplyr(DataFrame::fromQuery(StudentScore::query()))
    ->lm('score ~ study_hours + prior_score');</x-slot:chain>
            <x-slot:pipe>$fit = DataFrame::fromQuery(StudentScore::query())
    |> lm('score ~ study_hours + prior_score');</x-slot:pipe>
        </x-code-tabs>
    </article>

    <div class="mt-10">
        <livewire:school-scores />
    </div>
</x-layout>
