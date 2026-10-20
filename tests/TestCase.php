<?php

namespace Tests;

use App\Content\Package;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Packages are cached statically, so a test that points the content
        // path at a fixture must not leave its packages to the next one.
        Package::flush();
    }
}
