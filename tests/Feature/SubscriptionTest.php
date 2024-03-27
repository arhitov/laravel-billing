<?php

namespace Arhitov\LaravelBilling\Tests\Feature;

use Arhitov\LaravelBilling\Enums\OperationStateEnum;
use Arhitov\LaravelBilling\Enums\SubscriptionStateEnum;
use Arhitov\LaravelBilling\Exceptions;
use Arhitov\LaravelBilling\Models;
use Arhitov\LaravelBilling\Subscription;
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

        $this->assertInstanceOf(Models\Subscription::class, $subscription, 'Built subscription is not ' . Models\Subscription::class);
        $this->assertTrue($subscription->isValid(), 'The built subscription is not valid.');
    }

    /**
     * @depends testMakeBaseSubscription
     * @return void
     */
    public function testMakeFullSubscription()
    {
        $owner = $this->createOwner();
        $balance = $owner->getBalanceOrCreate();

        $subscription = $owner->makeSubscription(
            'first',
            amount: 123.23,
        );

        $this->assertInstanceOf(Models\Subscription::class, $subscription, 'Built subscription is not ' . Models\Subscription::class);
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

        /** @var Models\Subscription $subscriptionTest */
        $subscriptionTest = Models\Subscription::findOrFail($subscription->id);
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
        $balance = $owner->getBalanceOrCreate();

        $this->assertEquals(0, $owner->subscription()->count(), 'The owner must not have subscription.');

        $subscription = $owner->createSubscription('first', $balance, 123.23);
        $this->assertEquals(1, $owner->subscription()->count(), 'No subscription was created for the owner.');

        /** @var Models\Subscription $subscriptionTest */
        $subscriptionTest = Models\Subscription::findOrFail($subscription->id);
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

        $subscription = $owner->getSubscription('first');
        $this->assertNull($subscription, 'Subscription must not exist.');

        $this->assertFalse($owner->hasSubscription('first'), 'The owner must not have subscription.');

        $subscription = $owner->getSubscriptionOrCreate('first');

        $this->assertTrue($owner->hasSubscription('first'), 'No subscription was created for the owner.');
        $this->assertFalse($owner->hasSubscriptionActive('first'), 'The owner must not have active subscription.');

        $this->assertNull($subscription->state_active_at, 'Datetime "state_active_at" should not be installed yet.');
        $subscription->setState(SubscriptionStateEnum::Active);
        $this->assertNotNull($subscription->state_active_at, 'The subscription status has not been changed.');

        $this->assertFalse($owner->hasSubscriptionActive('first'), 'The owner does not have an active subscription.');
        $subscription->saveOrFail();
        $this->assertTrue($owner->hasSubscriptionActive('first'), 'The owner does not have an active subscription.');
    }

    /**
     * @depends testCreateSubscription
     * @return void
     * @throws ErrorException
     * @throws Throwable
     * @throws Exceptions\SubscriptionSettingExpiryException
     * @throws Exceptions\BalanceException
     * @throws Exceptions\OperationException
     * @throws Exceptions\TransferUsageException
     */
    public function testSubscriptionBuy()
    {
        $subscriptionDescription = 'Purchasing a subscription "test_subscription".';
        $subscriptionKey = 'test_subscription';
        $subscriptionAmount = 123.23;

        $owner = $this->createOwner();
        $balance = $owner->getBalanceOrCreate();
        $balance->limit = null;

        $subscriptionSetting = new Models\SubscriptionSetting(
            $subscriptionKey,
            $subscriptionAmount,
            '1 month',
        );

        $subscription = new Subscription(
            $balance,
            $subscriptionSetting,
            $subscriptionDescription,
        );

        $this->assertEquals(0, $owner->subscription()->count(), 'The owner must not have subscription.');
        $this->assertEquals(0, Models\Operation::count(), 'There should be no surgery at this stage.');
        $this->assertFalse($owner->hasSubscription($subscriptionKey), 'The owner must not have subscription.');

        $subscription->creatOrFail();

        $this->assertTrue($owner->hasSubscription($subscriptionKey), 'No subscription was created for the owner.');
        $this->assertFalse($owner->hasSubscriptionActive($subscriptionKey), 'The owner must not have active subscription.');

        /** @var Models\Subscription $subscriptionCreated */
        $subscriptionCreated = $owner->subscription()->where('key', '=', $subscriptionKey)->firstOrFail();
        $this->assertEquals(SubscriptionStateEnum::Pending, $subscriptionCreated->state, 'The subscription status should be "Pending".');

        $subscription->buyOrFail();
        $this->assertEquals(1, Models\Operation::count(), 'Operation not created.');

        /** @var Models\Subscription $subscriptionCreated */
        $subscriptionCreated = $owner->subscription()->where('key', '=', $subscriptionKey)->firstOrFail();
        /** @var Models\Operation $operationCreated */
        $operationCreated = Models\Operation::firstOrFail();

        $this->assertEquals('subscription', $operationCreated->operation_identifier, 'Operation identifier doesn\'t match');
        $this->assertEquals($subscriptionCreated->uuid, $operationCreated->operation_uuid, 'Operation UUID doesn\'t match');
        $this->assertEquals($subscriptionDescription, $operationCreated->description, 'Operation description doesn\'t match');
        $this->assertEquals($subscriptionAmount, $operationCreated->amount, 'Operation amount doesn\'t match');
        $this->assertEquals(OperationStateEnum::Succeeded, $operationCreated->state, 'Operation state doesn\'t match');

        $this->assertEquals(SubscriptionStateEnum::Active, $subscriptionCreated->state, 'The subscription status should be "Active".');
    }
}
