<?php

namespace Arhitov\LaravelBilling\Tests\Feature;

use Arhitov\LaravelBilling\Decrease;
use Arhitov\LaravelBilling\Exceptions\TransferUsageException;
use Arhitov\LaravelBilling\Increase;
use Arhitov\LaravelBilling\Models\Operation;
use Arhitov\LaravelBilling\Tests\FeatureTestCase;
use Illuminate\Support\Str;

class ModelOperationTest extends FeatureTestCase
{
    /**
     * A simple test, performed in another file.
     * This is only there to avoid checking whether exceptions work if a simple test fails.
     * @return void
     * @throws TransferUsageException
     */
    public function testSimple()
    {
        $owner = $this->createOwner();
        $increase = new Increase(
            $owner->getBalance(),
            100,
        );
        $operation = $increase->getOperation();
        $this->assertInstanceOf(Operation::class, $operation, 'Operation class is not ' . Operation::class);
    }

    /**
     * @depends testSimple
     * @return void
     * @throws TransferUsageException
     */
    public function testIdentifierIncrease()
    {
        $owner = $this->createOwner();

        $operationIdentifier = 'test';
        $operationUUID = Str::orderedUuid()->toString();

        $increase = new Increase(
            $owner->getBalance(),
            100,
            operation_identifier: $operationIdentifier,
            operation_uuid: $operationUUID,
        );
        $operation = $increase->getOperation();

        $this->assertEquals($operationIdentifier, $operation->operation_identifier, 'Filed "operation_identifier" has the wrong meaning.');
        $this->assertEquals($operationUUID, $operation->operation_uuid, 'Filed "operation_uuid" has the wrong meaning.');
    }

    /**
     * @depends testSimple
     * @return void
     * @throws TransferUsageException
     */
    public function testIdentifierDecrease()
    {
        $owner = $this->createOwner();

        $operationIdentifier = 'test';
        $operationUUID = Str::orderedUuid()->toString();

        $increase = new Decrease(
            $owner->getBalance(),
            100,
            operation_identifier: $operationIdentifier,
            operation_uuid: $operationUUID,
        );
        $operation = $increase->getOperation();

        $this->assertEquals($operationIdentifier, $operation->operation_identifier, 'Filed "operation_identifier" has the wrong meaning.');
        $this->assertEquals($operationUUID, $operation->operation_uuid, 'Filed "operation_uuid" has the wrong meaning.');
    }
}
