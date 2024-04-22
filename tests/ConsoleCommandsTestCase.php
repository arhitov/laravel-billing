<?php

namespace Arhitov\LaravelBilling\Tests;

use Arhitov\LaravelBilling\Providers\PackageServiceProvider;
use Illuminate\Support\Facades\Artisan;

class ConsoleCommandsTestCase extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        (new PackageServiceProvider($this->app))->boot();
    }

    public function callCommandAndOutput(string $command, array $parameters = []): void
    {
        echo "Call command: {$command}" . PHP_EOL;

        foreach ($parameters as $parameterKey => $parameterValue) {
            echo "    {$parameterKey}: {$parameterValue}" . PHP_EOL;
        }

        $this->artisan($command, $parameters);

        echo PHP_EOL;
        echo Artisan::output();
        echo PHP_EOL;
    }

    public static function readline(?string $prompt): string
    {
        ob_get_flush();
        ob_start();
        return readline($prompt);
    }
}