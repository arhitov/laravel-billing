<?php

namespace Arhitov\LaravelBilling\Tests\Feature;

use Arhitov\LaravelBilling\Decrease;
use Arhitov\LaravelBilling\Enums\CurrencyEnum;
use Arhitov\LaravelBilling\Enums\OperationStateEnum;
use Arhitov\LaravelBilling\Increase;
use Arhitov\LaravelBilling\Models\Balance;
use Arhitov\LaravelBilling\Models\Operation;
use Arhitov\LaravelBilling\Tests\FeatureTestCase;
use Arhitov\LaravelBilling\Transfer;

class BalanceTest extends FeatureTestCase
{
    public function testCreateBalance()
    {
        $owner = $this->createOwner();
        /** @var Balance $balance */
        $balance = $owner->balance()->create([
            'amount' => 123.23,
            'currency' => CurrencyEnum::RUB,
        ]);
        $this->assertEquals(
            Balance::class,
            get_class($balance),
            'Created balance is not ' . Balance::class,
        );
        $this->assertEquals(
            get_class($owner),
            get_class($balance->owner),
            'Created balance is not owner class',
        );
        $this->assertEquals(
            get_class($owner),
            get_class(Balance::find($balance->id)->owner),
            'Created balance is not owner class.',
        );

        $owner2 = $this->createOwner();
        $this->assertEquals(
            0,
            $owner2->balance()->count(),
            'For owner2 there is already a balance.',
        );
        $this->assertFalse($owner2->hasBalance('test'), 'For owner2 there is already a balance "test".');
        $balance2 = $owner2->getBalance('test');

        $this->assertInstanceOf(Balance::class, $balance2, 'Created balance is not ' . Balance::class);
        $this->assertTrue($owner2->hasBalance('test'), 'For owner2 there is not exist balance "test".');
        $this->assertEquals(
            'test',
            $balance2->key,
            'Key balance incorrect.',
        );
    }

    /**
     * @depends testCreateBalance
     */
    public function testIncreaseBalance()
    {
        $owner = $this->createOwner();
        $increase = new Increase(
            $owner->getBalance(),
            100,
        );
        $operation = $increase->getOperation();

        $this->assertEquals(0, $owner->getBalance()->amount, 'The owner has an incorrect balance.');

        $this->assertTrue($increase->isAllow(), 'Not allow increase');
        $this->assertNull($operation->succeeded_at, 'Temporary meta "succeeded_at" is not null.');
        $this->assertEquals(OperationStateEnum::Created, $operation->state, 'Status operation incorrect.');
        $this->assertInstanceOf(Operation::class, $operation, 'Operation class is not ' . Operation::class);
        $this->assertTrue($increase->execute(), 'Operation increase failed');
        $this->assertNotNull($operation->succeeded_at, 'Temporary meta "succeeded_at" is null.');
        $this->assertEquals(OperationStateEnum::Succeeded, $operation->state, 'Status operation incorrect.');

        $this->assertEquals(100, $owner->getBalance()->amount, 'The owner has an incorrect balance.');
    }

    /**
     * @depends testCreateBalance
     */
    public function testIncreaseDescriptionBalance()
    {
        $descriptionTest = fake()->text(1000);
        $descriptionTest2 = fake()->text(1000);
        $this->assertNotEquals($descriptionTest, $descriptionTest2, 'Faker created identical texts.');

        $owner = $this->createOwner();
        $increase = new Increase(
            $owner->getBalance(),
            100,
            description: $descriptionTest
        );
        $operation = $increase->getOperation();

        $this->assertEquals($descriptionTest, $operation->description, 'The operation description contains an incorrect value.');

        $increase->setDescription($descriptionTest2);
        $this->assertNotEquals($descriptionTest, $operation->description, 'The operation description contains the old value.');
        $this->assertEquals($descriptionTest2, $operation->description, 'The operation description contains an incorrect value.');

    }

    /**
     * @depends testCreateBalance
     */
    public function testDecreaseBalance()
    {
        $owner = $this->createOwner();
        $ownerBalance = $owner->getBalance();
        $ownerBalance->limit = null;

        $decrease = new Decrease(
            $ownerBalance,
            100,
        );
        $operation = $decrease->getOperation();

        $this->assertEquals(0, $owner->getBalance()->amount, 'The owner has an incorrect balance.');

        $this->assertTrue($decrease->isAllow(), 'Not allow increase');
        $this->assertNull($operation->succeeded_at, 'Temporary meta "succeeded_at" is not null.');
        $this->assertEquals(OperationStateEnum::Created, $operation->state, 'Status operation incorrect.');
        $this->assertInstanceOf(Operation::class, $operation, 'Operation class is not ' . Operation::class);
        $this->assertTrue($decrease->execute(), 'Operation increase failed');
        $this->assertNotNull($operation->succeeded_at, 'Temporary meta "succeeded_at" is null.');
        $this->assertEquals(OperationStateEnum::Succeeded, $operation->state, 'Status operation incorrect.');

        $this->assertEquals(-100, $owner->getBalance()->amount, 'The owner has an incorrect balance.');
    }

    /**
     * @depends testCreateBalance
     */
    public function testTransferBalance()
    {
        $senderOwner = $this->createOwner();
        $senderBalance = $senderOwner->getBalance();
        $senderBalance->limit = null;

        $recipientOwner = $this->createOwner();
        $recipientBalance = $recipientOwner->getBalance();
        $recipientBalance->limit = null;

        $decrease = new Transfer(
            $senderBalance,
            $recipientBalance,
            100,
        );
        $operation = $decrease->getOperation();

        $this->assertEquals(0, $senderOwner->getBalance()->amount, 'The sender has an incorrect balance.');
        $this->assertEquals(0, $recipientOwner->getBalance()->amount, 'The recipient has an incorrect balance.');

        $this->assertTrue($decrease->isAllow(), 'Not allow increase');
        $this->assertNull($operation->succeeded_at, 'Temporary meta "succeeded_at" is not null.');
        $this->assertEquals(OperationStateEnum::Created, $operation->state, 'Status operation incorrect.');
        $this->assertInstanceOf(Operation::class, $operation, 'Operation class is not ' . Operation::class);
        $this->assertTrue($decrease->execute(), 'Operation increase failed');
        $this->assertNotNull($operation->succeeded_at, 'Temporary meta "succeeded_at" is null.');
        $this->assertEquals(OperationStateEnum::Succeeded, $operation->state, 'Status operation incorrect.');

        $this->assertEquals(-100, $senderOwner->getBalance()->amount, 'The sender has an incorrect balance.');
        $this->assertEquals(100, $recipientOwner->getBalance()->amount, 'The recipient has an incorrect balance.');
    }
}
