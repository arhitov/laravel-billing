<?php

namespace Arhitov\LaravelBilling\Tests;

use Arhitov\LaravelBilling\Contracts\BillableInterface;
use Arhitov\LaravelBilling\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class FeatureTestCase extends TestCase
{
    use RefreshDatabase;

    /**
     * Define database migrations.
     *
     * @return void
     */
    protected function defineDatabaseMigrations(): void
    {
        $this->loadLaravelMigrations();
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /**
     * @param array $options
     * @return Model|BillableInterface
     */
    protected function createOwner(array $options = []): Model|BillableInterface
    {
        $fake = fake();
        return User::create(array_merge([
            'email' => $fake->unique()->safeEmail(),
            'name' => $fake->name(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        ], $options));
    }

    /**
     * Helper method used by gateway test classes to generate a valid test credit card
     *
     * @param string $key
     * @return array
     */
    protected function getDataValidCard(string $key = 'omnipay_dummy_success'): array
    {
        // Где????
        // 4929000000006 - Success
        // 4444333322221111 - Failure
        $list = [
            'omnipay_dummy_success' => [
                'number' => '4242424242424242',
            ],
            'omnipay_dummy_failure' => [
                'number' => '4111111111111111',
            ],
        ];

        return array_merge([
            'firstName' => 'Example',
            'lastName' => 'User',
            'number' => '4242424242424242',
            'expiryMonth' => rand(1, 12),
            'expiryYear' => (int)gmdate('Y') + rand(1, 5),
            'cvv' => rand(100, 999),
//            'billingAddress1' => '123 Billing St',
//            'billingAddress2' => 'Billsville',
//            'billingCity' => 'Billstown',
//            'billingPostcode' => '12345',
//            'billingState' => 'CA',
//            'billingCountry' => 'US',
//            'billingPhone' => '(555) 123-4567',
//            'shippingAddress1' => '123 Shipping St',
//            'shippingAddress2' => 'Shipsville',
//            'shippingCity' => 'Shipstown',
//            'shippingPostcode' => '54321',
//            'shippingState' => 'NY',
//            'shippingCountry' => 'US',
//            'shippingPhone' => '(555) 987-6543',
        ], $list[$key]);
    }

    protected function getConfigOmnipayGatewayDummy(): array
    {
        return [
            'omnipay_class' => 'Dummy',
            'capture' => true,
        ];
    }

    protected function getConfigOmnipayGatewayYooKassa(): array
    {
        return [
            'omnipay_class' => 'YooKassa',
            'omnipay_initialize' => [
                'shop_id' => 54401,
                'secret' => 'test_Fh8hUAVVBGUGbjmlzba6TB0iyUbos_lueTHE-axOwM0',
            ],
            'returnUrl' => 'https://www.example.com/pay',
            'capture' => true,
        ];
    }
}
