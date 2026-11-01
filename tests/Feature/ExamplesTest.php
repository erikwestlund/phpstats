<?php

namespace Tests\Feature;

use App\Content\Example;
use App\Livewire\ScriptVignette;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Phiz\Plot;
use Tests\TestCase;

class ExamplesTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_examples_give_the_same_result_with_the_pipe_and_the_chain(): void
    {
        $requires = ['model' => 'phit', 'polars' => 'phpolars', 'phpolars' => 'phpolars'];
        $compared = 0;

        foreach (glob(resource_path('examples/*/*.chain.php')) as $chain) {
            $name = basename(dirname($chain)).'/'.basename($chain, '.chain.php');
            if (! ScriptVignette::available($requires[basename($name)] ?? 'phplyr')) {
                continue;
            }
            $normalize = function (mixed $result) use (&$normalize) {
                return match (true) {
                    $result instanceof Plot => $result->toArray(),
                    is_object($result) => [$result->types(), $result->toRows()],
                    is_array($result) => array_map($normalize, $result),
                    default => $result,
                };
            };

            $this->assertEquals($normalize(Example::run($name)), $normalize(Example::run($name, 'chain')), $name);
            // Imports are shown only when the example puts them below its "// ---" line.
            if (! preg_match('/^\/\/ ---\nuse /m', file_get_contents($chain))) {
                $this->assertStringNotContainsString('use function', Example::code($name)['chain']);
            }
            $compared++;
        }

        $this->assertGreaterThan(0, $compared);
    }
}
