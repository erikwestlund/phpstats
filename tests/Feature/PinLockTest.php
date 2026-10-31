<?php

namespace Tests\Feature;

use Tests\TestCase;

class PinLockTest extends TestCase
{
    /**
     * A package is pinned twice, by tasks that never run together:
     * config('phpstats.content') names the commit its synced docs and vignette
     * scripts are read from, and composer.lock names the commit its installed
     * code comes from. When a dependency follows main, those have to be the
     * same commit, or the site calls an API the docs do not describe -- and
     * CmdStanPHP did exactly that, with the lock left at a commit still
     * declaring summary_table() while FitSchoolPosterior called summaryTable().
     *
     * The rule is narrower than equality. A dependency installed from a tag is
     * meant to differ, because the tag is the released code while the docs
     * follow main, and an extension is no Composer dependency at all.
     */
    public function test_a_dev_main_dependency_is_locked_to_the_commit_its_content_pin_names(): void
    {
        $locked = $this->lockedPackages();

        $kinds = [];
        $drift = [];
        foreach (config('phpstats.content') as $key => $pin) {
            $package = $locked[strtolower($pin['repo'])] ?? null;

            if ($package === null) {
                // phit and phpolars are PHP extensions, built and loaded on
                // their own, so no lock names a commit to compare.
                $kinds[$key] = 'no Composer dependency';

                continue;
            }
            if (! str_starts_with($package['version'], 'dev-')) {
                $kinds[$key] = 'tagged';

                continue;
            }

            $kinds[$key] = $package['version'];
            if ($package['reference'] !== $pin['ref']) {
                $drift[] = sprintf('%s: the content pin names %s, and composer.lock has %s at %s.',
                    $key, $pin['ref'], $package['version'], $package['reference']);
            }
        }

        $this->assertSame([], $drift, 'A dependency that follows main has its code and its synced docs at '
            .'different commits. Run composer update for it, or put its content pin back where the lock is.');

        ksort($kinds);
        // Spelled out rather than counted, so a package that quietly leaves the
        // comparison -- a dev-main require cut back to a tag, say -- fails here
        // instead of letting the check above pass over nothing.
        $this->assertSame([
            'cmdstanphp' => 'tagged',
            'juphyter' => 'no Composer dependency',
            'phibble' => 'tagged',
            'phit' => 'no Composer dependency',
            'phiz' => 'tagged',
            'phplyr' => 'tagged',
            'phpolars' => 'no Composer dependency',
        ], $kinds);
    }

    /**
     * Every locked package by its name in lower case, since a content pin
     * spells the repository as GitHub shows it and Composer does not.
     *
     * @return array<string, array{version: string, reference: string}>
     */
    private function lockedPackages(): array
    {
        $lock = json_decode(file_get_contents(base_path('composer.lock')), true, flags: JSON_THROW_ON_ERROR);

        $packages = [];
        foreach ([...$lock['packages'] ?? [], ...$lock['packages-dev'] ?? []] as $package) {
            $packages[strtolower($package['name'])] = [
                'version' => $package['version'],
                'reference' => $package['source']['reference'] ?? '',
            ];
        }

        return $packages;
    }
}
