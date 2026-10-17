<?php

namespace App\Livewire;

use Livewire\Component;
use Phibble\DataFrame;
use Phibble\Io\NativeCsv;
use Throwable;
use ValueError;
use function Phit\read_csv;

/**
 * Vignette: phit's native CSV reader on editable text, with phibble's
 * PHP reader run on the same file to show that the two agree.
 */
class CsvReader extends Component
{
    public const MAX_BYTES = 20_000;

    public const DELIMITERS = [',' => 'Comma', ';' => 'Semicolon', "\t" => 'Tab', '|' => 'Pipe'];

    public const SAMPLE = <<<'CSV'
        id,zip,signup,last_seen,plan,active,monthly_spend,visits
        1,02139,2026-01-15,2026-10-01T08:30:00Z,basic,TRUE,"1,250.50",12
        2,10001,2026-02-03,2026-10-02 14:05:00,pro,FALSE,980.00,NA
        3,94110,2026-02-30,2026-10-03T09:00:00+02:00,basic,T,"2,001.75",7
        4,60601,2026-03-12,,team,F,410.25,3
        5,73301,2026-04-01,2026-10-04T23:59:59Z,pro,true,,15
        6,30301,2026-05-20,2026-10-05T12:00:00Z,basic
        CSV;

    public string $csv = self::SAMPLE;

    public string $delimiter = ',';

    public function resetSample(): void
    {
        $this->csv = self::SAMPLE;
        $this->delimiter = ',';
    }

    public function render()
    {
        return view('livewire.csv-reader', [
            'extensionLoaded' => function_exists('Phit\\read_csv'),
            ...$this->read(),
        ]);
    }

    private function read(): array
    {
        if (! function_exists('Phit\\read_csv')) {
            return [];
        }
        if (strlen($this->csv) > self::MAX_BYTES) {
            return ['error' => 'The text is longer than '.number_format(self::MAX_BYTES).' bytes.'];
        }

        $delimiter = array_key_exists($this->delimiter, self::DELIMITERS) ? $this->delimiter : ',';
        $path = tempnam(sys_get_temp_dir(), 'csv');

        try {
            file_put_contents($path, $this->csv);

            try {
                $native = read_csv($path, ['delimiter' => $delimiter]);
            } catch (ValueError $e) {
                return ['error' => $e->getMessage()];
            }

            return [
                'native' => $native,
                'preview' => $this->preview($native),
                'parity' => $this->parity($path, $delimiter),
            ];
        } finally {
            unlink($path);
        }
    }

    /**
     * Up to 10 rows of the native result, with dates and datetimes, which
     * arrive as day and microsecond counts, formatted for display.
     */
    private function preview(array $native): array
    {
        $rows = [];
        $count = count($native['columns'][0]['values'] ?? []);
        for ($i = 0; $i < min(10, $count); $i++) {
            $row = [];
            foreach ($native['columns'] as $column) {
                $value = $column['values'][$i];
                $row[] = match (true) {
                    $value === null => 'NA',
                    $column['type'] === 'bool' => $value ? 'TRUE' : 'FALSE',
                    $column['type'] === 'date' => gmdate('Y-m-d', $value * 86400),
                    $column['type'] === 'datetime' => gmdate('Y-m-d H:i:s', intdiv($value, 1_000_000)).' UTC',
                    default => (string) $value,
                };
            }
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Read the file through DataFrame::fromCsv() with the native reader and with
     * phibble's PHP reader, and compare types, values, and warnings.
     */
    private function parity(string $path, string $delimiter): array
    {
        $read = function () use ($path, $delimiter): array {
            $warnings = [];
            set_error_handler(function (int $level, string $message) use (&$warnings) {
                $warnings[] = $message;

                return true;
            }, E_USER_WARNING);
            try {
                $frame = DataFrame::fromCsv($path, delimiter: $delimiter);

                return ['types' => $frame->types(), 'columns' => $frame->toColumns(), 'warnings' => $warnings];
            } catch (Throwable $e) {
                return ['error' => $e->getMessage(), 'warnings' => $warnings];
            } finally {
                restore_error_handler();
            }
        };

        $native = $read();
        // NativeCsv is marked internal in phibble; enable(false) is the switch
        // its docs give for forcing the PHP reader.
        NativeCsv::enable(false);
        try {
            $php = $read();
        } finally {
            NativeCsv::enable(true);
        }

        return [
            'types' => $native['types'] ?? null,
            'warnings' => $native['warnings'],
            'same_types' => ($native['types'] ?? null) === ($php['types'] ?? null),
            'same_values' => ($native['columns'] ?? null) == ($php['columns'] ?? null),
            'same_warnings' => $native['warnings'] === $php['warnings'],
            'error' => $native['error'] ?? $php['error'] ?? null,
        ];
    }
}
