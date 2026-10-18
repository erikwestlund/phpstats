{{-- Scores against study hours for four schools from database/data/schools.csv, with a least-squares line for each. --}}
@php
    $rows = array_values(array_filter(
        \Phibble\DataFrame::fromCsv(database_path('data/schools.csv'))->toRows(),
        fn (array $row) => in_array($row['school'], ['school-01', 'school-04', 'school-07', 'school-10'], true),
    ));
    $plot = \Phiz\Plot::of($rows)
        ->aes(x: 'study_hours', y: 'score', color: 'school')
        ->point(alpha: 0.7, size: 40)
        ->smooth(method: 'lm', se: false)
        ->labs(x: 'Study hours', y: 'Score', color: null)
        ->width('container')
        ->height(260);
@endphp
<div class="not-prose rounded-xl bg-white p-4 ring-1 ring-zinc-200 dark:bg-white/[0.03] dark:ring-white/10">
    <x-phiz :plot="$plot" class="w-full" />
</div>
