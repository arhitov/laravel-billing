<?php

namespace Arhitov\LaravelBilling\Tests\Feature;

use Arhitov\LaravelBilling\Exceptions;
use Arhitov\LaravelBilling\Models\Subscription;
use Arhitov\LaravelBilling\Tests\FeatureTestCase;

class SubscriptionExceptionTest extends FeatureTestCase
{
    /**
     * A simple test, performed in another file.
     * This is only there to avoid checking whether exceptions work if a simple test fails.
     * @return void
     */
    public function testCreateSubscription()
    {
        $owner = $this->createOwner();

        $this->assertFalse($owner->hasSubscription('test'), 'The owner must not have any balance.');

        $owner->getSubscriptionOrCreate('test');

        $this->assertTrue($owner->hasSubscription('test'), 'No balance was created for the owner.');
    }

    /**
     * @depends testCreateSubscription
     * @return void
     */
    public function testGetSubscriptionOrFail()
    {
        $this->expectException(Exceptions\SubscriptionNotFoundException::class);
        $this->createOwner()->getSubscriptionOrFail();
    }

    /**
     * @depends testGetSubscriptionOrFail
     * @return void
     * @throws Exceptions\SubscriptionNotFoundException
     */
    public function testGetSubscriptionOrFail2()
    {
        $owner = $this->createOwner();
        $owner->getSubscriptionOrCreate();

        $balance = $owner->getSubscriptionOrFail();
        $this->assertInstanceOf(Subscription::class, $balance, 'Created balance is not ' . Subscription::class);
    }
}
