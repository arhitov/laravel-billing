<?php

namespace Arhitov\LaravelBilling\Tests\Unit;

use Arhitov\Helpers\Validating\EloquentModelExtendTrait;
use Arhitov\LaravelBilling\Models\CreditCard;
use Arhitov\LaravelBilling\Tests\TestCase;
use Illuminate\Support\Facades\Validator;
use ValueError;
use Watson\Validating\ValidatingTrait;

class ModelCreditCardUnitTest extends TestCase
{
    protected static CreditCard $model;

    public static function setUpBeforeClass(): void
    {
        self::$model = new CreditCard;
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
        $model = new CreditCard();

        $this->assertFalse($model->isValid());

        $validator = Validator::make(
            [
                'state' => 'unknown',
            ],
            $model->getRules(),
        );

        $this->assertTrue($validator->fails(), 'Error validator');
        $errorsList = $validator->errors()->all() ?? [];

        $this->assertContains('The owner balance id field is required.', $errorsList, 'The owner_balance_id not validating.');
        $this->assertContains('The rebill id field is required.', $errorsList, 'The rebill_id not validating.');
        $this->assertContains('The gateway field is required.', $errorsList, 'The gateway not validating.');
        $this->assertContains('The selected state is invalid.', $errorsList, 'The state not validating.');
    }

    /**
     * @depends testValidation
     * @return void
     */
    public function testValidationStateException()
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('"unknown" is not a valid backing value for enum Arhitov\\LaravelBilling\\Enums\\CreditCardStateEnum');
        new CreditCard(['state' => 'unknown']);
    }
}
