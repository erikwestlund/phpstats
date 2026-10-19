<?php

namespace App\Console\Commands;

use App\Content\Example;
use Illuminate\Console\Command;

/**
 * Drop the cached output of the landing page's examples. Run it on deploy and
 * after `site:sync`, because the cache keys follow the example files'
 * modification times and not the code or the content the examples read.
 */
class ClearExamples extends Command
{
    protected $signature = 'examples:clear';

    protected $description = 'Clear the cached output of the landing page examples';

    public function handle(): int
    {
        Example::flush();

        $this->components->info('Cached example output cleared.');

        return self::SUCCESS;
    }
}
