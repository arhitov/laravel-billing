<?php

namespace Arhitov\LaravelBilling\Tests\Feature;

use Arhitov\LaravelBilling\Events;
use Arhitov\LaravelBilling\Increase;
use Arhitov\LaravelBilling\Listeners\BalanceChangedListener;
use Arhitov\LaravelBilling\Tests\FeatureTestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

class BalanceListenersTest extends FeatureTestCase
{
    /**
     * A simple test, performed in another file.
     * This is only there to avoid checking whether exceptions work if a simple test fails.
     * @return void
     */
    public function testCreateBalance()
    {
        $owner = $this->createOwner();

        $this->assertFalse($owner->hasBalance('test'), 'The owner must not have any balance.');

        $owner->getBalanceOrCreate('test');

        $this->assertTrue($owner->hasBalance('test'), 'No balance was created for the owner.');
    }

    /**
     * @depends testCreateBalance
     */
    public function testBalanceChangedListener()
    {
        Event::fake();

        $balanceAmount = 100;
        $owner = $this->createOwner();
        $balance = $owner->getBalanceOrCreate();

        $balanceCacheKeySetting = $balance->getSettingCacheAmount();
        $this->assertIsArray($balanceCacheKeySetting);

        (new Increase(
            $balance,
            $balanceAmount,
        ))->execute();

        $this->assertEquals($balanceAmount, $owner->getBalanceCacheAmount(), 'Balance contains incorrect value.');
        $this->assertTrue(Cache::has($balanceCacheKeySetting['key']), 'Cache key not found.');
        $this->assertEquals($balanceAmount, Cache::get($balanceCacheKeySetting['key']), 'Balance in cache contains incorrect value.');

        Event::assertNotDispatched(Events\BalanceChangedEvent::class);

        (new BalanceChangedListener())->handle(
            new Events\BalanceChangedEvent($balance)
        );

        $this->assertFalse(Cache::has($balanceCacheKeySetting['key']), 'Cache key found.');
        $this->assertEquals($balanceAmount, $owner->getBalanceCacheAmount(), 'Balance contains incorrect value.');
    }
}
