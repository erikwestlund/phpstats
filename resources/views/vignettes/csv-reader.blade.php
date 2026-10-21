<x-layout title="Reading CSV files">
    <article class="max-w-3xl">
        <h1 class="text-3xl font-semibold tracking-tight">Reading CSV files</h1>
        <p class="mt-4 text-zinc-700 dark:text-zinc-300">
            phit includes a native CSV reader, Phit\read_csv(), that tokenizes a file, guesses each column's type, and parses the values in Rust.
            Its rules follow readr 2 and match phibble's PHP reader.
            When the extension is loaded, phibble's DataFrame::fromCsv() uses it.
        </p>
        <p class="mt-4 text-zinc-700 dark:text-zinc-300">
            Edit the text below and the page reads it again.
            The sample shows several of the rules.
            A ZIP code with a leading zero stays text, quoted numbers with grouping marks read as doubles, and true and false spellings read as booleans.
            The invalid date 2026-02-30 fails to parse, and the short last row is padded with NA and reported as a problem.
        </p>

        <h2 class="mt-8 text-sm font-medium uppercase tracking-wide text-zinc-500">Code</h2>
        <pre class="mt-3 overflow-x-auto rounded-lg bg-zinc-100 p-4 text-sm dark:bg-zinc-900"><code>use function Phit\read_csv;

$result = read_csv($path, ['delimiter' => ',']);

$result['names'];     // column names
$result['columns'];   // per column: type, values, and the count that failed to parse
$result['problems'];  // ragged rows and unterminated quotes

$df = DataFrame::fromCsv($path);  // phibble, through the same reader</code></pre>
        <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">
            The phit documentation lists every option and guessing rule.
            In phibble's 1,000,000-row benchmark on an Apple Silicon Mac, DataFrame::fromCsv() takes 0.12 seconds with the extension and 1.0 seconds with the PHP reader.
        </p>
    </article>

    <div class="mt-10">
        <livewire:csv-reader />
    </div>
</x-layout>
