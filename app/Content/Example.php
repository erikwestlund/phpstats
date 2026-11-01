<?php

namespace App\Content;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * A runnable example from resources/examples: {name}.php uses the pipe
 * operator and {name}.chain.php, when present, the method chain. A file
 * either returns its result or echoes it, as the package explorer's do, and
 * then what it printed is the result. The code shown on the page leaves out
 * the opening tag, any setup above a "// ---" line, and a final return
 * statement. Imports are left out too, except those placed below the
 * "// ---" line, which are shown.
 *
 * The landing page shows eleven examples, among them a Polars aggregation, a
 * model fit, and two chart specifications. Their results are not serializable,
 * so what `output()` caches is the rendered HTML, keyed by the modification
 * times of the files it is computed from: the example's own, and the ones the
 * caller names, such as a stored fixture a result is read from. Clear it on
 * deploy and after `site:sync`, with `php artisan examples:clear` or
 * `Example::flush()`.
 */
class Example
{
    /** The generation token mixed into every key, so flushing is one forget. */
    private const GENERATION = 'examples:generation';

    /** Orphans left behind by an edit or a flush fall out on their own. */
    private const TTL_DAYS = 7;

    /** @var array<string, mixed> results computed in this request, by name */
    private static array $results = [];

    /** @var array<string, int> how many times each example file has run, for tests */
    private static array $runs = [];

    private static ?string $generation = null;

    public static function path(string $name, string $syntax = 'pipe'): string
    {
        return resource_path('examples/'.$name.($syntax === 'chain' ? '.chain' : '').'.php');
    }

    /** @return array{pipe: string, chain: ?string} */
    public static function code(string $name): array
    {
        $chain = self::path($name, 'chain');

        return [
            'pipe' => self::display(file_get_contents(self::path($name))),
            'chain' => is_file($chain) ? self::display(file_get_contents($chain)) : null,
        ];
    }

    /** The result of running the example, computed once per request. */
    public static function run(string $name, string $syntax = 'pipe'): mixed
    {
        $path = self::path($name, $syntax);
        if (array_key_exists($path, self::$results)) {
            return self::$results[$path];
        }
        self::$runs[$path] = (self::$runs[$path] ?? 0) + 1;

        // An example may change directory to read its data by a short name.
        $cwd = getcwd();
        ob_start();
        try {
            $result = (static fn () => require $path)();
        } finally {
            $printed = ob_get_clean();
            chdir($cwd);
        }

        // A file without a return statement gives 1, and its result is what it printed.
        return self::$results[$path] = $result === 1 ? $printed : $result;
    }

    /**
     * The example's result as rendered HTML, computed once and then cached
     * until the example file changes. $render receives the result; $result
     * computes it, for the examples whose result is stored rather than run.
     * $files are the other files the result is computed from, such as a
     * stored fixture or a document rendered in place of a script: they are
     * in the key as well, so re-capturing one is enough to serve it.
     * A throw is not cached, so a missing extension or a failure is shown by
     * the caller and tried again on the next request.
     *
     * @param  list<string>  $files
     */
    public static function output(string $name, string $variant, callable $render, ?callable $result = null, array $files = []): string
    {
        $result ??= static fn () => self::run($name);

        if (config('app.debug')) {
            return $render($result());
        }

        $key = self::key($name, $variant, $files);
        $cached = Cache::get($key);
        // A fragment is the rendered HTML and nothing else. The layout emits
        // phiz's runtime for every page, so a cached chart no longer has to
        // record whether it was the fragment that carried it.
        if (is_string($cached)) {
            return $cached;
        }

        $html = $render($result());
        Cache::put($key, $html, now()->addDays(self::TTL_DAYS));

        return $html;
    }

    /** Drop every cached fragment, for deploys and after `site:sync`. */
    public static function flush(): void
    {
        Cache::forget(self::GENERATION);
        self::$generation = null;
    }

    /** Forget this request's results and run counts. */
    public static function reset(): void
    {
        self::$results = [];
        self::$runs = [];
        self::$generation = null;
    }

    /** @return array<string, int> how many times each example file has run */
    public static function runs(): array
    {
        return self::$runs;
    }

    /**
     * An example's key: its name, what is rendered from it, and the
     * modification times of the files it is computed from, in the order
     * given. A file that is not there contributes nothing, and appears in
     * the key once it does, which moves the key as an edit would.
     *
     * @param  list<string>  $files
     */
    private static function key(string $name, string $variant, array $files = []): string
    {
        $paths = [self::path($name), self::path($name, 'chain'), ...$files];
        $mtimes = implode('-', array_filter(array_map(
            fn (string $path) => is_file($path) ? filemtime($path) : null,
            $paths,
        )));

        return 'examples:'.self::generation().':'.md5($name.'|'.$variant).':'.$mtimes;
    }

    /** Random, not time-based, so two flushes can never land on one token. */
    private static function generation(): string
    {
        return self::$generation ??= Cache::rememberForever(self::GENERATION, fn () => Str::random(12));
    }

    /**
     * The code of an example shown in steps: the parts between its "// ---"
     * lines, after the hidden setup above the first one, each without a
     * final return statement.
     *
     * @return list<string>
     */
    public static function steps(string $name): array
    {
        $parts = preg_split('/^\/\/ ---\n/m', file_get_contents(self::path($name)));
        array_shift($parts);

        return array_map(fn (string $part) => trim(preg_replace('/\n*^return [^;]*;\s*\z/m', '', $part))."\n", $parts);
    }

    public static function display(string $source): string
    {
        $source = preg_replace('/^<\?php\s*/', '', $source);
        if (($marker = strpos($source, "// ---\n")) !== false) {
            $source = substr($source, $marker + strlen("// ---\n"));
        } else {
            $lines = array_filter(explode("\n", $source), fn (string $line) => ! preg_match('/^use (function )?[A-Z]/', $line));
            $source = implode("\n", $lines);
        }
        $source = preg_replace('/\n*^return [^;]*;\s*\z/m', '', $source);

        return trim($source)."\n";
    }
}
