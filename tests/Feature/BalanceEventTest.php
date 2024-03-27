<?php

namespace Arhitov\LaravelBilling\Tests\Feature;

use Arhitov\LaravelBilling\Decrease;
use Arhitov\LaravelBilling\Enums\CurrencyEnum;
use Arhitov\LaravelBilling\Events;
use Arhitov\LaravelBilling\Exceptions\TransferUsageException;
use Arhitov\LaravelBilling\Increase;
use Arhitov\LaravelBilling\Tests\FeatureTestCase;
use Illuminate\Support\Facades\Event;

class BalanceEventTest extends FeatureTestCase
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
    public function testBalanceCreatedEvent()
    {
        Event::fake();

        $this->createOwner()->createBalance(key: 'first');

        Event::assertDispatched(Events\BalanceCreatedEvent::class);
    }

    /**
     * @depends testCreateBalance
     */
    public function testBalanceCreatedEvent2()
    {
        Event::fake();

        $this->createOwner()->getBalanceOrCreate('first');

        Event::assertDispatched(Events\BalanceCreatedEvent::class);
    }

    /**
     * @depends testCreateBalance
     */
    public function testBalanceCreatedEvent3()
    {
        Event::fake();

        $this->createOwner()->balance()->create([
            'key' => 'first',
            'currency' => CurrencyEnum::RUB,
        ]);

        Event::assertNotDispatched(Events\BalanceCreatedEvent::class);
    }

    /**
     * @depends testCreateBalance
     * @return void
     * @throws TransferUsageException
     */
    public function testBalanceIncreaseEvent()
    {
        Event::fake();

        (new Increase(
            $this->createOwner()->getBalanceOrCreate(),
            100,
        ))->execute();

        Event::assertDispatched(Events\BalanceIncreaseEvent::class);
    }

    /**
     * @depends testCreateBalance
     * @return void
     * @throws TransferUsageException
     */
    public function testBalanceDecreaseEvent()
    {
        Event::fake();

        $balance = $this->createOwner()->getBalanceOrCreate();
        $balance->limit = null;

        (new Decrease(
            $balance,
            100,
        ))->execute();

        Event::assertDispatched(Events\BalanceDecreaseEvent::class);
    }

    /**
     * @depends testBalanceIncreaseEvent
     * @return void
     * @throws TransferUsageException
     */
    public function testBalanceChangedEvent()
    {
        Event::fake();

        (new Increase(
            $this->createOwner()->getBalanceOrCreate(),
            100,
        ))->execute();

        Event::assertNotDispatched(Events\BalanceChangedEvent::class);
    }

    /**
     * @depends testBalanceDecreaseEvent
     * @return void
     * @throws TransferUsageException
     */
    public function testBalanceChangedEvent2()
    {
        Event::fake();

        $balance = $this->createOwner()->getBalanceOrCreate();
        $balance->limit = null;

        (new Decrease(
            $balance,
            100,
        ))->execute();

        Event::assertNotDispatched(Events\BalanceChangedEvent::class);
    }
}
