<?php

namespace Arhitov\LaravelBilling\Tests\Feature;

use Arhitov\LaravelBilling\Enums\SubscriptionStateEnum;
use Arhitov\LaravelBilling\Models\Subscription;
use Arhitov\LaravelBilling\Models\SubscriptionSetting;
use Arhitov\LaravelBilling\Tests\FeatureTestCase;
use ErrorException;
use Throwable;

class SubscriptionTest extends FeatureTestCase
{
    /**
     * @return void
     */
    public function testMakeBaseSubscription()
    {
        $owner = $this->createOwner();

        $subscription = $owner->makeSubscription(
            'first',
        );

        $this->assertInstanceOf(Subscription::class, $subscription, 'Built subscription is not ' . Subscription::class);
        $this->assertTrue($subscription->isValid(), 'The built subscription is not valid.');
    }

    /**
     * @depends testMakeBaseSubscription
     * @return void
     */
    public function testMakeFullSubscription()
    {
        $owner = $this->createOwner();
        $balance = $owner->getBalance();

        $subscription = $owner->makeSubscription(
            'first',
            amount: 123.23,
        );

        $this->assertInstanceOf(Subscription::class, $subscription, 'Built subscription is not ' . Subscription::class);
        $this->assertFalse($subscription->isValid(), 'The built subscription is not valid.');

        $errorsList = $subscription->getErrors()->toArray();
        $this->assertContains('The balance id field is required when amount is present.', $errorsList['balance_id'] ?? [], 'The "balance_id" not validating.');

        $subscription->setBalance($balance);
        $this->assertTrue($subscription->isValid(), 'The built subscription is valid.');

        $this->assertEquals(0, $owner->subscription()->count(), 'The owner must not have subscription.');
    }

    /**
     * @depends testMakeFullSubscription
     * @return void
     */
    public function testCreateSubscription()
    {
        $owner = $this->createOwner();

        $this->assertEquals(0, $owner->subscription()->count(), 'The owner must not have subscription.');

        $subscription = $owner->createSubscription('first');
        $this->assertEquals(1, $owner->subscription()->count(), 'No subscription was created for the owner.');

        /** @var Subscription $subscriptionTest */
        $subscriptionTest = Subscription::findOrFail($subscription->id);
        $this->assertEmpty($subscriptionTest->balance_id, 'Default value for "balance_id" uncorrected.');
        $this->assertEmpty($subscriptionTest->currency, 'Default value for "currency" uncorrected.');
    }

    /**
     * @depends testCreateSubscription
     * @return void
     */
    public function testCreateSubscriptionUseAmount()
    {
        $owner = $this->createOwner();
        $balance = $owner->getBalance();

        $this->assertEquals(0, $owner->subscription()->count(), 'The owner must not have subscription.');

        $subscription = $owner->createSubscription('first', $balance, 123.23);
        $this->assertEquals(1, $owner->subscription()->count(), 'No subscription was created for the owner.');

        /** @var Subscription $subscriptionTest */
        $subscriptionTest = Subscription::findOrFail($subscription->id);
        $this->assertEquals($subscription->balance_id, $subscriptionTest->balance_id, 'Method setBalance it works incorrectly.');
        $this->assertEquals($subscription->currency, $subscriptionTest->currency, 'Method setBalance it works incorrectly.');
    }

    /**
     * @depends testCreateSubscription
     * @return void
     * @throws ErrorException
     * @throws Throwable
     */
    public function testGetSubscription()
    {
        $owner = $this->createOwner();

        $this->assertFalse($owner->hasSubscription('first'), 'The owner must not have subscription.');

        $subscription = $owner->getSubscription('first');

        $this->assertTrue($owner->hasSubscription('first'), 'No subscription was created for the owner.');
        $this->assertFalse($owner->hasSubscriptionActive('first'), 'The owner must not have active subscription.');

        $this->assertNull($subscription->state_active_at, 'Datetime "state_active_at" should not be installed yet.');
        $subscription->setState(SubscriptionStateEnum::Active);
        $this->assertNotNull($subscription->state_active_at, 'The subscription status has not been changed.');

        $this->assertFalse($owner->hasSubscriptionActive('first'), 'The owner does not have an active subscription.');
        $subscription->saveOrFail();
        $this->assertTrue($owner->hasSubscriptionActive('first'), 'The owner does not have an active subscription.');
    }
}
