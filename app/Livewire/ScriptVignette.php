<?php

namespace App\Livewire;

use App\Content\Package;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Throwable;

/**
 * A vignette from a package's site/vignettes/{slug}.php. The script returns
 * ['inputs' => [...], 'run' => fn (array $input, string $data): array] and
 * the blocks that run() returns are rendered in order. Inputs become this
 * component's $input, so changing one runs the script again.
 */
class ScriptVignette extends Component
{
    #[Locked]
    public string $package;

    #[Locked]
    public string $slug;

    public array $input = [];

    /** @var array<string, array> scripts loaded in this process, by path */
    private static array $scripts = [];

    public function mount(string $package, string $slug): void
    {
        $this->package = $package;
        $this->slug = $slug;
        foreach ($this->definition()['inputs'] ?? [] as $name => $spec) {
            $this->input[$name] = $spec['default'] ?? null;
        }
    }

    public function render()
    {
        $package = Package::find($this->package);
        $vignette = $package->vignette($this->slug);
        $definition = $this->definition();
        $inputs = $definition['inputs'] ?? [];
        $missing = array_values(array_filter($vignette['uses'], fn (string $use) => ! self::available($use)));
        if (array_intersect(['phibble'], $vignette['uses']) && array_intersect(['php_polars', 'phpolars'], $vignette['uses']) && ! class_exists('Phibble\\Polars\\PolarsTable')) {
            $missing[] = 'a phibble release with PolarsTable';
        }

        $blocks = [];
        $error = null;
        if ($missing === []) {
            try {
                $blocks = ($definition['run'])(self::cleanInputs($inputs, $this->input), $package->path('data'));
            } catch (Throwable $e) {
                report($e);
                $error = config('app.debug') ? $e->getMessage() : 'The vignette failed to run on this server.';
            }
        }

        return view('livewire.script-vignette', [
            'inputs' => $inputs,
            'blocks' => $blocks,
            'missing' => $missing,
            'error' => $error,
        ]);
    }

    /** Whether a package a vignette uses can run in this PHP process. */
    public static function available(string $package): bool
    {
        return match ($package) {
            'phit' => function_exists('Phit\\fit_ols'),
            'phpolars', 'php_polars' => extension_loaded('php_polars'),
            'phibble' => class_exists('Phibble\\DataFrame'),
            // Vignette scripts may use the method chain, which phplyr added in 0.3.
            'phplyr' => function_exists('Phplyr\\phplyr'),
            'phiz' => class_exists('Phiz\\Plot'),
            'cmdstanphp' => class_exists('CmdStan\\CmdStanModel'),
            default => extension_loaded($package),
        };
    }

    /**
     * Allow-list options and clamp numbers, so a request can only pass values
     * the script declared.
     */
    public static function cleanInputs(array $inputs, array $values): array
    {
        $clean = [];
        foreach ($inputs as $name => $spec) {
            $value = $values[$name] ?? $spec['default'] ?? null;
            $clean[$name] = match ($spec['type'] ?? 'text') {
                'select', 'radio' => self::option($spec, $value),
                'checkbox' => filter_var($value, FILTER_VALIDATE_BOOL),
                'number' => self::number($spec, $value),
                default => $spec['default'] ?? null,
            };
        }

        return $clean;
    }

    private static function option(array $spec, mixed $value): mixed
    {
        foreach (array_keys($spec['options'] ?? []) as $key) {
            if ((string) $key === (string) $value) {
                return $key;
            }
        }

        return $spec['default'] ?? null;
    }

    private static function number(array $spec, mixed $value): int|float
    {
        $number = is_numeric($value) ? $value + 0 : ($spec['default'] ?? 0);
        if (isset($spec['min'])) {
            $number = max($spec['min'], $number);
        }
        if (isset($spec['max'])) {
            $number = min($spec['max'], $number);
        }

        return $number;
    }

    private function definition(): array
    {
        $path = Package::find($this->package)?->vignetteScript($this->slug) ?? abort(404);

        return self::$scripts[$path] ??= (static fn (string $file) => require $file)($path);
    }
}
