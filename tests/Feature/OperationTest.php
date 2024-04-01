<?php

namespace Arhitov\LaravelBilling\Tests\Feature;

use Arhitov\LaravelBilling\Increase;
use Arhitov\LaravelBilling\Tests\FeatureTestCase;
use Arhitov\LaravelBilling\Transfer;

class OperationTest extends FeatureTestCase
{
    public function testList()
    {
        $owner1 = $this->createOwner();
        $balance1 = $owner1->getBalanceOrCreate();
        $owner2 = $this->createOwner();
        $balance2 = $owner2->getBalanceOrCreate();
        $owner3 = $this->createOwner();
        $balance3 = $owner3->getBalanceOrCreate();

        $this->assertEquals(0, $owner1->builderOperation()->count(), 'There should be no surgery at this stage.');
        $this->assertEquals(0, $balance1->operation()->count(), 'There should be no surgery at this stage.');

        (new Increase(
            $balance1,
            100,
        ))->executeOrFail();

        (new Transfer(
            $balance1,
            $balance2,
            10,
        ))->executeOrFail();

        $this->assertEquals(2, $owner1->builderOperation()->count(), 'There should be 2 transactions on the balance.');
        $this->assertEquals(2, $balance1->operation()->count(), 'There should be 2 transactions on the balance.');

        $this->assertEquals(1, $owner2->builderOperation()->count(), 'There should be 1 transactions on the balance.');
        $this->assertEquals(1, $balance2->operation()->count(), 'There should be 1 transactions on the balance.');

        $this->assertEquals(0, $owner3->builderOperation()->count(), 'There should be 0 transactions on the balance.');
        $this->assertEquals(0, $balance3->operation()->count(), 'There should be 0 transactions on the balance.');

    }
}