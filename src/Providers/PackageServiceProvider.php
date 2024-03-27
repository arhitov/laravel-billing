<?php

namespace Arhitov\LaravelBilling\Providers;

use Arhitov\PackageHelpers\Config\PublishesConfigTrait;
use Arhitov\PackageHelpers\Migrations\PublishesMigrationsTrait;
use Illuminate\Support\ServiceProvider;

class PackageServiceProvider extends ServiceProvider
{
    use PublishesConfigTrait;
    use PublishesMigrationsTrait;

    public function boot(): void
    {
        $this->registerConfig(
            __DIR__ . '/../../config',
            'billing-config'
        );
        $this->registerMigrations(
            __DIR__ . '/../../database/migrations',
            'billing-migrations'
        );
    }

    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
    }
}
