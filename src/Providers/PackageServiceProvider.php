<?php

namespace Arhitov\LaravelBilling\Providers;

use Arhitov\PackageHelpers\Config\PublishesConfigTrait;
use Arhitov\PackageHelpers\Console\Commands\RegisterCommandsTrait;
use Arhitov\PackageHelpers\Migrations\PublishesMigrationsTrait;
use Illuminate\Support\Facades\Validator;
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

        /**
         * billing_amount =>     FLOAT(18, 4) => 11111111111111.1111
         * billing_amount:6 =>   FLOAT(18, 6) => 111111111111.111111
         * billing_amount:6:2 => FLOAT(6, 2) =>  1111.11
         */
        Validator::extend('billing_amount', function ($attribute, $value, $parameters) {

            $totalDefault = config('billing.rounding.total', 18);
            $digitsDefault = config('billing.rounding.precision', 6);
            $parameter = explode(':', $parameters[0] ?? $digitsDefault);

            [
                0 => $total,
                1 => $digits,
            ] = match(count($parameter)) {
                1 => [$totalDefault, $parameter[0]],
                2 => [$parameter[0], $parameter[1]],
            };

            $total =  (int)$total;
            $digits = (int)$digits;
            $ceil =   $total - $digits;

            if ($digits >= $total) {
                throw new \ErrorException('Total should not be greater than greater than digits!');
            }

            $digitsStr = match ($digits) {
                0       => '',
                1       => '(?:\.\d{1})?',
                default => "(?:\.\d{1,{$digits}})?",
            };

            $ceilStr = match ($ceil) {
                1       => '(?:[1-9]|0)',
                2       => '(?:[1-9]\d{1}|0)',
                default => '(?:[1-9]\d{1,' . ($ceil - 1) . '}|0)',
            };

            return (bool)preg_match("/^{$ceilStr}{$digitsStr}$/", $value);
        });
    }

    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
    }
}
