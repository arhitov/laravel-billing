<?php

namespace Arhitov\LaravelBilling\Tests\Feature;

use Arhitov\LaravelBilling\Events;
use Arhitov\LaravelBilling\Tests\FeatureTestCase;
use Illuminate\Support\Facades\Event;

class SubscriptionEventTest extends FeatureTestCase
{
    /**
     * A simple test, performed in another file.
     * This is only there to avoid checking whether exceptions work if a simple test fails.
     * @return void
     */
    public function testCreateSubscription()
    {
        $owner = $this->createOwner();

        $this->assertFalse($owner->hasSubscription('first'), 'The owner must not have subscription.');

        $owner->getSubscriptionOrCreate('first');

        $this->assertTrue($owner->hasSubscription('first'), 'No subscription was created for the owner.');
    }

    /**
     * @depends testCreateSubscription
     */
    public function testSubscriptionCreatedEvent()
    {
        Event::fake();

        $this->createOwner()->createSubscription('first');

        Event::assertDispatched(Events\SubscriptionCreatedEvent::class);
    }

    /**
     * @depends testCreateSubscription
     */
    public function testSubscriptionCreatedEvent2()
    {
        Event::fake();

        $this->createOwner()->getSubscriptionOrCreate('first');

        Event::assertDispatched(Events\SubscriptionCreatedEvent::class);
    }

    /**
     * @depends testCreateSubscription
     */
    public function testSubscriptionCreatedEvent3()
    {
        Event::fake();

        $this->createOwner()->subscription()->create([
            'uuid' => '123e4567-e89b-12d3-a456-426655440000',
            'key'  => 'first',
        ]);

        Event::assertNotDispatched(Events\SubscriptionCreatedEvent::class);
    }
}
