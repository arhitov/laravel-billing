<?php

namespace Arhitov\LaravelBilling\Tests\Feature;

use Arhitov\LaravelBilling\Providers\PackageServiceProvider;
use Arhitov\LaravelBilling\Tests\FeatureTestCase;
use Illuminate\Support\Facades\Artisan;

class PackageServiceProviderTest extends FeatureTestCase
{
    public function testPackageServiceProvider()
    {
        (new PackageServiceProvider($this->app))->boot();

        $artisanCommandList = array_filter(
            array_keys(Artisan::all()),
            fn($key) => str_starts_with($key, 'billing:')
        );

        $this->assertContains('billing:get-balance', $artisanCommandList);
        $this->assertContains('billing:decrease-balance', $artisanCommandList);
        $this->assertContains('billing:increase-balance', $artisanCommandList);
    }
}