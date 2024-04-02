<?php

namespace Arhitov\LaravelBilling\Tests\Unit;

use Arhitov\LaravelBilling\BillableRootTrait;
use Arhitov\LaravelBilling\Models\RootOwner;
use Arhitov\LaravelBilling\Tests\TestCase;

class ModelRootOwnerUnitTest extends TestCase
{
    /**
     * @return void
     */
    public function testBase()
    {
        $model = $this->createOwner();

        // Connection may be empty
        // $this->assertNotEmpty(self::$model->getConnectionName(), 'Connect name empty');
        $this->assertNotEmpty($model->getTable(), 'Table empty');
        $this->assertTrue(in_array(BillableRootTrait::class, class_uses($model)), 'Model no use ' . BillableRootTrait::class);
    }

    protected static function createOwner(): RootOwner
    {
        return new RootOwner;
    }
}
