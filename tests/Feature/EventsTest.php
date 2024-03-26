<?php

namespace Arhitov\LaravelBilling\Tests\Feature;

use Arhitov\LaravelBilling\Decrease;
use Arhitov\LaravelBilling\Events;
use Arhitov\LaravelBilling\Exceptions\TransferUsageException;
use Arhitov\LaravelBilling\Increase;
use Arhitov\LaravelBilling\Tests\FeatureTestCase;
use Illuminate\Support\Facades\Event;

class EventsTest extends FeatureTestCase
{
    /**
     * A simple test, performed in another file.
     * This is only there to avoid checking whether exceptions work if a simple test fails.
     * @return void
     * @throws TransferUsageException
     */
    public function testSimple()
    {
        $owner = $this->createOwner();
        $ownerBalance = $owner->getBalance();

        $increase = new Increase(
            $ownerBalance,
            100,
        );

        $this->assertEquals(0, $owner->getBalance()->amount, 'The owner has an incorrect balance.');
        $this->assertTrue($increase->execute(), 'Operation increase failed');
        $this->assertEquals(100, $owner->getBalance()->amount, 'The owner has an incorrect balance.');
    }

    /**
     * @depends testSimple
     * @return void
     * @throws TransferUsageException
     */
    public function testBalanceIncreaseEvent()
    {
        Event::fake();

        $balance = $this->createOwner()->getBalance();

        (new Increase(
            $balance,
            100,
        ))->execute();

        Event::assertDispatched(Events\BalanceIncreaseEvent::class);
    }

    /**
     * @depends testSimple
     * @return void
     * @throws TransferUsageException
     */
    public function testBalanceDecreaseEvent()
    {
        Event::fake();

        $balance = $this->createOwner()->getBalance();
        $balance->limit = null;

        (new Decrease(
            $balance,
            100,
        ))->execute();

        Event::assertDispatched(Events\BalanceDecreaseEvent::class);
    }
}
