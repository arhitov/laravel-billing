<?php

namespace Arhitov\LaravelBilling\Tests\Feature;

use Arhitov\LaravelBilling\Increase;
use Arhitov\LaravelBilling\Tests\FeatureTestCase;
use Illuminate\Support\Facades\Cache;

class ServiceProviderTest extends FeatureTestCase
{
    protected function getPackageProviders ($app)
    {
        return [
            'Arhitov\\LaravelBilling\\Providers\\PackageServiceProvider',
        ];
    }

    public function testEventServiceProviderBalanceChangedListener()
    {
        $balanceAmount = 100;
        $balanceAmountTest = 123;
        $owner = $this->createOwner();
        $balance = $owner->getBalanceOrCreate();

        $balanceCacheKeySetting = $balance->getSettingCache();
        $this->assertIsArray($balanceCacheKeySetting);

        (new Increase(
            $balance,
            $balanceAmount,
        ))->execute();

        $this->assertEquals($balanceAmount, $owner->getCacheBalance()?->amount, 'Balance contains incorrect value.');
        $this->assertTrue(Cache::has($balanceCacheKeySetting['key']), 'Cache key not found.');
        $this->assertEquals($balanceAmount, Cache::get($balanceCacheKeySetting['key'])['amount'], 'Balance in cache contains incorrect value.');

        $balanceCache = Cache::get($balanceCacheKeySetting['key']);
        $balanceCache['amount'] = $balanceAmountTest;
        Cache::put($balanceCacheKeySetting['key'], $balanceCache, $balanceCacheKeySetting['ttl']);

        $this->assertEquals($balanceAmountTest, Cache::get($balanceCacheKeySetting['key'])['amount'], 'Balance in cache contains incorrect value.');
        $this->assertEquals($balanceAmountTest, $owner->getCacheBalance()?->amount, 'Balance contains incorrect value.');

        (new Increase(
            $balance,
            $balanceAmount,
        ))->execute();

        $this->assertFalse(Cache::has($balanceCacheKeySetting['key']), 'Cache key found.');
        $this->assertEquals($balanceAmount * 2, $owner->getCacheBalance()?->amount, 'Balance contains incorrect value.');
    }
}
