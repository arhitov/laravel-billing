<?php
/**
 * Billing module for laravel projects
 *
 * @link      https://github.com/arhitov/laravel-billing
 * @package   arhitov/laravel-billing
 * @license   MIT
 * @copyright Copyright (c) 2024, Alexander Arhitov, clgsru@gmail.com
 */

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
        $this->assertContains('billing:create-payment', $artisanCommandList);
        $this->assertContains('billing:get-payment-information', $artisanCommandList);
        $this->assertContains('billing:get-omnipay-payment-information', $artisanCommandList);
        $this->assertContains('billing:check-omnipay-payment-state', $artisanCommandList);
        $this->assertContains('billing:capture-omnipay-payment', $artisanCommandList);
        $this->assertContains('billing:fiscal-receipt-check-state', $artisanCommandList);
    }
}
