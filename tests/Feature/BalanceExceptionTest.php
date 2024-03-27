<?php

namespace Arhitov\LaravelBilling\Tests\Feature;

use Arhitov\LaravelBilling\Exceptions;
use Arhitov\LaravelBilling\Models\Balance;
use Arhitov\LaravelBilling\Tests\FeatureTestCase;

class BalanceExceptionTest extends FeatureTestCase
{
    /**
     * A simple test, performed in another file.
     * This is only there to avoid checking whether exceptions work if a simple test fails.
     * @return void
     */
    public function testCreateBalance()
    {
        $owner = $this->createOwner();

        $this->assertFalse($owner->hasBalance('test'), 'The owner must not have any balance.');

        $owner->getBalanceOrCreate('test');

        $this->assertTrue($owner->hasBalance('test'), 'No balance was created for the owner.');
    }

    /**
     * @depends testCreateBalance
     * @return void
     */
    public function testGetBalanceOrFail()
    {
        $this->expectException(Exceptions\BalanceNotFoundException::class);
        $this->createOwner()->getBalanceOrFail();
    }

    /**
     * @depends testGetBalanceOrFail
     * @return void
     * @throws Exceptions\BalanceNotFoundException
     */
    public function testGetBalanceOrFail2()
    {
        $owner = $this->createOwner();
        $owner->getBalanceOrCreate();

        $balance = $owner->getBalanceOrFail();
        $this->assertInstanceOf(Balance::class, $balance, 'Created balance is not ' . Balance::class);
    }
}
