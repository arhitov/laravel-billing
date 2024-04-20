<?php

namespace Arhitov\LaravelBilling\Tests;

use Arhitov\LaravelBilling\Providers\PackageServiceProvider;

class ConsoleCommandsTestCase extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        (new PackageServiceProvider($this->app))->boot();
    }
}