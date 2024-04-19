<?php

namespace Arhitov\LaravelBilling\Providers;

use Arhitov\PackageHelpers\Config\PublishesConfigTrait;
use Arhitov\PackageHelpers\Console\Commands\RegisterCommandsTrait;
use Arhitov\PackageHelpers\Migrations\PublishesMigrationsTrait;
use Illuminate\Support\ServiceProvider;

class PackageServiceProvider extends ServiceProvider
{
    use PublishesConfigTrait;
    use PublishesMigrationsTrait;
    use RegisterCommandsTrait;

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {

            $this->registerConfig(
                __DIR__ . '/../../config',
                'billing-config'
            );
            $this->registerMigrations(
                __DIR__ . '/../../database/migrations',
                'billing-migrations'
            );
            $this->registerCommands(
                'Arhitov\\LaravelBilling\\Console\\Commands\\',
                __DIR__ . '/../Console/Commands',
            );
        }
    }

    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
    }
}
