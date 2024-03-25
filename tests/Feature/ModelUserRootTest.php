<?php

namespace Arhitov\LaravelBilling\Tests\Feature;

use Arhitov\LaravelBilling\Contracts\BillableInterface;
use Arhitov\LaravelBilling\Contracts\BillableRootInterface;
use Arhitov\LaravelBilling\Decrease;
use Arhitov\LaravelBilling\Exceptions\TransferUsageException;
use Arhitov\LaravelBilling\Increase;
use Arhitov\LaravelBilling\Models\Balance;
use Arhitov\LaravelBilling\Models\UserRoot;
use Arhitov\LaravelBilling\Tests\FeatureTestCase;
use Illuminate\Database\Eloquent\Model;

class ModelUserRootTest extends FeatureTestCase
{
    public function testBase()
    {
        $owner = new UserRoot;
        // Connection may be empty
        // $this->assertNotEmpty(self::$model->getConnectionName(), 'Connect name empty');
        $this->assertNotEmpty($owner->getTable(), 'Table empty');
        $this->assertInstanceOf(Model::class, $owner, 'Model no use ' . Model::class);
        $this->assertInstanceOf(BillableInterface::class, $owner, 'Model no implements ' . BillableInterface::class);
        $this->assertInstanceOf(BillableRootInterface::class, $owner, 'Model no implements ' . BillableRootInterface::class);
    }

    /**
     * @depends testBase
     * @return void
     */
    public function testBalance()
    {
        $owner = new UserRoot;
        $balance = $owner->getBalance();
        $this->assertEquals(
            Balance::class,
            get_class($balance),
            'Created balance is not ' . Balance::class,
        );
    }

    /**
     * @depends testBase
     * @return void
     * @throws TransferUsageException
     */
    public function testRootBalanceIncreaseTransferUsageException()
    {
        $senderOwner = $this->createOwner();
        $senderBalance = $senderOwner->getBalance();

        $recipientOwner = $this->createOwner();
        $recipientBalance = $recipientOwner->getBalance();

        $this->expectException(TransferUsageException::class);
        new Increase(
            $senderBalance,
            100,
            sender: $recipientBalance,
        );
    }

    /**
     * @depends testBase
     * @return void
     * @throws TransferUsageException
     */
    public function testRootBalanceDecreaseTransferUsageException()
    {
        $senderOwner = $this->createOwner();
        $senderBalance = $senderOwner->getBalance();

        $recipientOwner = $this->createOwner();
        $recipientBalance = $recipientOwner->getBalance();

        $this->expectException(TransferUsageException::class);
        new Decrease(
            $senderBalance,
            100,
            recipient: $recipientBalance,
        );
    }
}
