<?php

namespace Arhitov\LaravelBilling\Tests\Feature;

use Arhitov\LaravelBilling\Decrease;
use Arhitov\LaravelBilling\Enums\BalanceStateEnum;
use Arhitov\LaravelBilling\Enums\CurrencyEnum;
use Arhitov\LaravelBilling\Exceptions;
use Arhitov\LaravelBilling\Increase;
use Arhitov\LaravelBilling\Tests\FeatureTestCase;
use Arhitov\LaravelBilling\Transfer;
use Throwable;

class ExceptionsTest extends FeatureTestCase
{
    /**
     * A simple test, performed in another file.
     * This is only there to avoid checking whether exceptions work if a simple test fails.
     * @return void
     * @throws Exceptions\TransferUsageException
     * @throws \Arhitov\LaravelBilling\Exceptions\Common\AmountException
     */
    public function testSimple()
    {
        $owner = $this->createOwner();
        $ownerBalance = $owner->getBalanceOrCreate();

        $increase = new Increase(
            $ownerBalance,
            100,
        );

        $this->assertEquals(0, $owner->getBalance()?->amount, 'The owner has an incorrect balance.');
        $this->assertTrue($increase->execute(), 'Operation increase failed');
        $this->assertEquals(100, $owner->getBalance()?->amount, 'The owner has an incorrect balance.');
    }

    /**
     * @depends testSimple
     * @throws Throwable
     */
    public function testBalanceEmptyException()
    {
        $owner = $this->createOwner();
        $ownerBalance = $owner->getBalanceOrCreate();
        $ownerBalance->limit = 100;

        $decrease = new Decrease(
            $ownerBalance,
            100,
        );
        $this->assertTrue($decrease->execute(), 'Operation increase failed.');

        $decrease = new Decrease(
            $ownerBalance,
            100,
        );
        $this->assertFalse($decrease->execute(), 'Operation increase failed.');

        $this->expectException(Exceptions\BalanceEmptyException::class);
        $decrease->isAllowOrFail();
    }

    /**
     * @depends testSimple
     * @throws Throwable
     */
    public function testBalanceNotAllowIncreaseException()
    {
        $owner = $this->createOwner();
        $ownerBalance = $owner->getBalanceOrCreate();

        $decrease = new Increase(
            $ownerBalance,
            100,
        );

        $this->assertTrue($decrease->isAllow(), 'The operation must be allowed.');

        $ownerBalance->state = BalanceStateEnum::Inactive;
        $this->assertFalse($decrease->isAllow(), 'The operation should not be allowed.');

        $ownerBalance->state = BalanceStateEnum::Locked;
        $this->assertFalse($decrease->isAllow(), 'The operation should not be allowed.');

        $this->expectException(Exceptions\BalanceNotAllowIncreaseException::class);
        $decrease->isAllowOrFail();
    }

    /**
     * @depends testSimple
     * @throws Throwable
     */
    public function testBalanceNotAllowDecreaseException()
    {
        $owner = $this->createOwner();
        $ownerBalance = $owner->getBalanceOrCreate();
        $ownerBalance->limit = 100;

        $decrease = new Decrease(
            $ownerBalance,
            100,
        );

        $this->assertTrue($decrease->isAllow(), 'The operation must be allowed.');

        $ownerBalance->state = BalanceStateEnum::Inactive;
        $this->assertFalse($decrease->isAllow(), 'The operation should not be allowed.');

        $ownerBalance->state = BalanceStateEnum::Locked;
        $this->assertFalse($decrease->isAllow(), 'The operation should not be allowed.');

        $this->expectException(Exceptions\BalanceNotAllowDecreaseException::class);
        $decrease->isAllowOrFail();
    }

    /**
     * @depends testSimple
     * @throws Exceptions\BalanceException
     * @throws \Arhitov\LaravelBilling\Exceptions\Common\AmountException
     */
    public function testOperationCurrencyNotMatchException()
    {
        $senderOwner = $this->createOwner();
        $senderBalance = $senderOwner->getBalanceOrCreate();
        $senderBalance->currency = CurrencyEnum::RUB;
        $senderBalance->limit = null;

        $recipientOwner = $this->createOwner();
        $recipientBalance = $recipientOwner->getBalanceOrCreate();
        $recipientBalance->currency = CurrencyEnum::RUB;
        $recipientBalance->limit = null;

        $decrease = new Transfer(
            $senderBalance,
            $recipientBalance,
            100,
        );

        $this->assertTrue($decrease->isAllow(), 'The operation must be allowed.');

        $recipientBalance->currency = CurrencyEnum::BYR;
        $this->assertFalse($decrease->isAllow(), 'The operation should not be allowed.');

        $this->expectException(Exceptions\OperationCurrencyNotMatchException::class);
        $decrease->isAllowOrFail();
    }

    /**
     * @return void
     * @throws Exceptions\OperationAlreadyCreatedException
     * @throws Throwable
     */
    public function testOperationAlreadyCreatedException()
    {
        $owner = $this->createOwner();
        $ownerBalance = $owner->getBalanceOrCreate();

        $increase = new Increase(
            $ownerBalance,
            100,
        );

        $this->assertTrue($increase->create(), 'Failed to create operation.');
        $this->assertFalse($increase->create(), 'Recreated the operation.');

        $this->expectException(Exceptions\OperationAlreadyCreatedException::class);
        $increase->createOrFail();
    }
}
