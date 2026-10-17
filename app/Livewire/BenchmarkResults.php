<?php

namespace App\Livewire;

use App\Data\Benchmarks;
use Livewire\Component;
use Phiz\Plot;

class BenchmarkResults extends Component
{
    public string $model = 'ols';

    public function render()
    {
        if (! Benchmarks::exists()) {
            return view('livewire.benchmark-results', ['data' => null]);
        }

        $data = Benchmarks::load();
        $key = array_key_exists($this->model, $data['models']) ? $this->model : 'ols';
        $model = $data['models'][$key];

        $implementations = array_keys($model['cases'][0]['implementations']);
        $rows = [];
        $labels = [];
        foreach ($model['cases'] as $case) {
            $labels[] = $label = number_format($case['n']).' rows, '.$case['p'].' coefficients'.($case['groups'] ? ', '.number_format($case['groups']).' groups' : '');
            foreach ($case['implementations'] as $name => $impl) {
                $rows[] = ['case' => $label, 'implementation' => self::name($name), 'seconds' => $impl['median_fit_seconds']];
            }
        }

        return view('livewire.benchmark-results', [
            'data' => $data,
            'report' => $model,
            'note' => Benchmarks::NOTES[$key] ?? null,
            'implementations' => $implementations,
            'chart' => $this->chart(array_values(array_filter($rows, fn ($r) => $r['seconds'] > 0)), $labels),
        ]);
    }

    /** "R version 4.6.1 (2026-06-24)" becomes "4.6.1 (2026-06-24)". */
    public static function version(string $name, string $version): string
    {
        return $name === 'PHP' ? "on PHP {$version}" : preg_replace('/^R version /', '', $version);
    }

    public static function name(string $implementation): string
    {
        return $implementation === 'PHP' ? 'phit' : $implementation;
    }

    private function chart(array $rows, array $cases): Plot
    {
        return Plot::of($rows)
            ->aes(x: 'seconds', y: 'case', color: 'implementation')
            ->point(size: 90, alpha: 0.9)
            ->scaleX(type: 'log')
            ->scaleY(domain: $cases)
            ->labs(x: 'Median fit time (seconds, log scale)', y: null, color: null)
            ->theme(legendPosition: 'bottom')
            ->tooltip()
            ->size('container', 220);
    }
}
