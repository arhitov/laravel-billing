<?php

namespace Arhitov\LaravelBilling\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        config(['billing' => require __DIR__ . '/../config/billing.php']);
    }
}
