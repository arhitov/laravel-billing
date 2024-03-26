<?php

namespace Arhitov\LaravelBilling\Tests\Unit;

use Arhitov\Helpers\Validating\EloquentModelExtendTrait;
use Arhitov\LaravelBilling\Models\Subscription;
use Arhitov\LaravelBilling\Tests\TestCase;
use Illuminate\Support\Facades\Validator;
use Watson\Validating\ValidatingTrait;

class ModelSubscriptionUnitTest extends TestCase
{
    protected static Subscription $model;

    public static function setUpBeforeClass(): void
    {
        self::$model = new Subscription;
    }

    /**
     * @return void
     */
    public function testBase()
    {
        // Connection may be empty
        // $this->assertNotEmpty(self::$model->getConnectionName(), 'Connect name empty');
        $this->assertNotEmpty(self::$model->getTable(), 'Table empty');
        $this->assertTrue(in_array(ValidatingTrait::class, class_uses(self::$model)), 'Model no use ' . ValidatingTrait::class);
        $this->assertTrue(in_array(EloquentModelExtendTrait::class, class_uses(self::$model)), 'Model no use ' . EloquentModelExtendTrait::class);
    }

    /**
     * @depends testBase
     * @return void
     */
    public function testRules()
    {
        $this->assertNotEmpty(self::$model->getRules());
    }

    /**
     * @depends testBase
     * @return void
     */
    public function testAttributes()
    {
        $this->assertNotEmpty(self::$model->getAttributes());
    }

    /**
     * @depends testBase
     * @return void
     */
    public function testCasts()
    {
        $this->assertNotEmpty(self::$model->getCasts());
    }

    /**
     * @depends testRules
     * @return void
     */
    public function testValidation()
    {
        $this->assertFalse(self::$model->isValid());

        $validator = Validator::make(
            [
                'balance_id' => 'qwe',
                'amount' => 'qwe',
                'currency' => 'unknown',
                'state' => 'unknown',
            ],
            self::$model->getRules(),
        );

        $this->assertTrue($validator->fails(), 'Error validator');
        $errorsList = $validator->errors()->all() ?? [];

        $this->assertContains('The key field is required.', $errorsList, 'The "key" not validating.');
        $this->assertContains('The balance id field must be an integer.', $errorsList, 'The "balance_id" not validating.');
        $this->assertContains('The selected currency is invalid.', $errorsList, 'The "currency" not validating.');
        $this->assertContains('The amount field must be a number.', $errorsList, 'The "amount" not validating.');
        $this->assertContains('The selected state is invalid.', $errorsList, 'The "state" not validating.');
    }

}
