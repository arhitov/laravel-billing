<?php

namespace Arhitov\LaravelBilling\Tests\Feature;

use Arhitov\LaravelBilling\Enums\SavedPaymentStateEnum;
use Arhitov\LaravelBilling\Models\SavedPayment;
use Arhitov\LaravelBilling\Tests\FeatureTestCase;

class ModelSavedPaymentTest extends FeatureTestCase
{

    /**
     * @return void
     */
    public function testSaveForBalance()
    {
        $owner = $this->createOwner();
        $balance = $owner->getBalance();

        $this->assertEquals(0, $balance->savedPayment()->count(), 'There should be no saved methods at this stage.');
        $this->assertEquals(0, $owner->getPaymentMethodList()->count(), 'There should be no saved methods at this stage.');

        $titleTest = 'My Card 123';
        $savedPayment = $balance->addPaymentMethodsOrFail([
            'title' => $titleTest,
            'rebill_id' => 'qwe-qwe-qwe-qwe',
            'gateway' => 'Yandex',
        ]);
        $this->assertInstanceOf(SavedPayment::class, $savedPayment, 'Saved payment class is not ' . SavedPayment::class);
        $this->assertEquals($balance->id, $savedPayment->owner_balance_id, 'The "owner_balance_id" field has an incorrect value.');
        $this->assertEquals(SavedPaymentStateEnum::Created, $savedPayment->state, 'Saved payment has a state of not "Created"');
        $this->assertEquals($titleTest, $savedPayment->title, 'Title has the wrong meaning.');
        $savedPayment->state = SavedPaymentStateEnum::Active;
        $savedPayment->save();
        $this->assertEquals(SavedPaymentStateEnum::Active, $savedPayment->state, 'Saved payment has a state of not "Active"');

        $this->assertEquals(1, $balance->savedPayment()->count(), 'The number of saved methods does not match the value being checked.');
        $this->assertEquals(1, $owner->getPaymentMethodList()->count(), 'The number of saved methods does not match the value being checked.');
    }

    /**
     * @depends testSaveForBalance
     * @return void
     */
    public function testSaveForBalance2()
    {
        $savedPayment = $this->createOwner()->getBalance()->addPaymentMethodsOrFail([
            'rebill_id' => 'qwe-qwe-qwe-qwe',
            'gateway' => 'Yandex',
            'state' => SavedPaymentStateEnum::Active,
        ]);
        $this->assertInstanceOf(SavedPayment::class, $savedPayment, 'Saved payment class is not ' . SavedPayment::class);
        $this->assertEquals(SavedPaymentStateEnum::Active, $savedPayment->state, 'Saved payment has a state of not "Active"');
    }
}
