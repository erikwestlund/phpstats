<?php

namespace App\Content;

/**
 * A runnable example from resources/examples: {name}.php uses the pipe
 * operator and {name}.chain.php, when present, the method chain. Each file
 * returns its result. The code shown on the page leaves out the opening tag,
 * any setup above a "// ---" line, and the final return statement. Imports are left
 * out too, except those placed below the "// ---" line, which are shown.
 */
class Example
{
    /** @var array<string, mixed> results computed in this request, by name */
    private static array $results = [];

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

        return self::$results[$path] ??= (static fn () => require $path)();
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
