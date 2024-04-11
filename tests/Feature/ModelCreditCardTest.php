<?php

namespace Arhitov\LaravelBilling\Tests\Feature;

use Arhitov\LaravelBilling\Enums\CreditCardStateEnum;
use Arhitov\LaravelBilling\Models\CreditCard;
use Arhitov\LaravelBilling\Tests\FeatureTestCase;

class ModelCreditCardTest extends FeatureTestCase
{

    /**
     * @return void
     */
    public function testSaveForBalance()
    {
        $owner = $this->createOwner();
        $balance = $owner->getBalanceOrCreate();

        $this->assertEquals(0, $balance->creditCard()->count(), 'There should be no saved credit сard at this stage.');
        $this->assertEquals(0, $owner->listCreditCard()->count(), 'There should be no saved credit сard at this stage.');

        $titleTest = 'My Card 123';
        $creditCard = $balance->addCreditCardOrFail([
            'title' => $titleTest,
            'rebill_id' => 'qwe-qwe-qwe-qwe',
            'gateway' => 'Yandex',
        ]);
        $this->assertInstanceOf(CreditCard::class, $creditCard, 'Saved payment class is not ' . CreditCard::class);
        $this->assertEquals($balance->id, $creditCard->owner_balance_id, 'The "owner_balance_id" field has an incorrect value.');
        $this->assertEquals(CreditCardStateEnum::Created, $creditCard->state, 'Saved payment has a state of not "Created"');
        $this->assertEquals($titleTest, $creditCard->title, 'Title has the wrong meaning.');
        $creditCard->state = CreditCardStateEnum::Active;
        $creditCard->save();
        $this->assertEquals(CreditCardStateEnum::Active, $creditCard->state, 'Saved payment has a state of not "Active"');

        $this->assertEquals(1, $balance->creditCard()->count(), 'The number of saved credit сard does not match the value being checked.');
        $this->assertEquals(1, $owner->listCreditCard()->count(), 'The number of saved credit сard does not match the value being checked.');
    }

    /**
     * @depends testSaveForBalance
     * @return void
     */
    public function testSaveForBalance2()
    {
        $creditCard = $this->createOwner()->getBalanceOrCreate()->addCreditCardOrFail([
            'rebill_id' => 'qwe-qwe-qwe-qwe',
            'gateway' => 'Yandex',
            'state' => CreditCardStateEnum::Active,
        ]);
        $this->assertInstanceOf(CreditCard::class, $creditCard, 'Saved payment class is not ' . CreditCard::class);
        $this->assertEquals(CreditCardStateEnum::Active, $creditCard->state, 'Saved payment has a state of not "Active"');
    }
}
