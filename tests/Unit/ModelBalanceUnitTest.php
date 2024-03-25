<?php

namespace Arhitov\LaravelBilling\Tests\Unit;

use Arhitov\Helpers\Validating\EloquentModelExtendTrait;
use Arhitov\LaravelBilling\Models\Balance;
use Arhitov\LaravelBilling\Tests\TestCase;
use Illuminate\Support\Facades\Validator;
use ValueError;
use Watson\Validating\ValidatingTrait;

class ModelBalanceUnitTest extends TestCase
{
    protected static Balance $model;

    public static function setUpBeforeClass(): void
    {
        self::$model = new Balance;
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
        $model = new Balance();
        $model->amount = '123.23';

        $this->assertFalse($model->isValid());

        $validator = Validator::make(
            [
                'amount' => 'qwe',
                'currency' => 'unknown',
            ],
            $model->getRules(),
        );

        $this->assertTrue($validator->fails(), 'Error validator');
        $errorsList = $validator->errors()->all() ?? [];

        $this->assertContains('The owner type field is required.', $errorsList, 'The owner_type not validating.');
        $this->assertContains('The owner id field is required.', $errorsList, 'The owner_id not validating.');
        $this->assertContains('The key field is required.', $errorsList, 'The key not validating.');
        $this->assertContains('The amount field must be a number.', $errorsList, 'The amount not validating.');
        $this->assertContains('The selected currency is invalid.', $errorsList, 'The currency not validating.');
    }

    /**
     * @depends testValidation
     * @return void
     */
    public function testValidationStateException()
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('"unknown" is not a valid backing value for enum Arhitov\LaravelBilling\Enums\CurrencyEnum');
        new Balance(['amount' => '123.23', 'currency' => 'unknown']);
    }
}
