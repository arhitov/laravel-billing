<?php
/**
 * Billing module for laravel projects
 *
 * @link      https://github.com/arhitov/laravel-billing
 * @package   arhitov/laravel-billing
 * @license   MIT
 * @copyright Copyright (c) 2024, Alexander Arhitov, clgsru@gmail.com
 */

namespace Arhitov\LaravelBilling\Tests\Unit;

use Arhitov\Helpers\Validating\EloquentModelExtendTrait;
use Arhitov\LaravelBilling\Models\Receipt;
use Arhitov\LaravelBilling\Tests\TestCase;
use Watson\Validating\ValidatingTrait;

class ModelReceiptUnitTest extends TestCase
{
    protected static Receipt $model;

    public static function setUpBeforeClass(): void
    {
        self::$model = new Receipt;
    }

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

        $model = new Receipt(['amount' => 'qwe']);
        $this->assertNull($model->amount);

        $model->amount = 'qwe';
        $this->assertEquals(0.0, $model->amount);
    }

    /**
     * @depends testRules
     * @return void
     */
    public function testValidation()
    {
        $model = new Receipt();

        $this->assertFalse($model->isValid());

        $errorsList = $model->getErrors()->all() ?? [];

        $this->assertCount(5, $errorsList);
        $this->assertContains('The operation uuid field is required.', $errorsList, 'The "operation_uuid" not validating.');
        $this->assertContains('The gateway field is required.', $errorsList, 'The "gateway" not validating.');
        $this->assertContains('The amount field is required.', $errorsList, 'The "amount" not validating.');
        $this->assertContains('The currency field is required.', $errorsList, 'The "currency" not validating.');
        $this->assertContains('The receipt field is required.', $errorsList, 'The "receipt" not validating.');

        $model->operation_uuid = 'f435d926-848a-11d9-55bc-0bd72011229a';
        $model->gateway = 'gateway';

        $receipt = self::makeReceipt();
        $receipt->setSeller(
            self::makeSeller(),
        );
        $receipt->addItem(
            self::makeReceiptItem(['amount' => 2.12, 'currency' => 'RUB']),
        );

        $model->setReceipt($receipt);
        $this->assertEquals('RUB', $model->currency->value);

        $this->assertFalse($model->isValid());
        $errorsList = $model->getErrors()->all() ?? [];
        $this->assertCount(1, $errorsList);
        $this->assertContains('The receipt is not validate.', $errorsList, 'The "receipt" not validating.');

        $lastError = $model->getReceipt()->validateLastError();
        $this->assertCount(1, $lastError['parameters']['customer']);
        $this->assertEquals('Customer must be', $lastError['parameters']['customer'][0] ?? null);

        $receipt->setCustomer(
            self::makeCustomer(),
        );

        $this->assertTrue($model->isValid());

        $model->currency = 'USD';
        $this->assertFalse($model->isValid());
        $errorsList = $model->getErrors()->all() ?? [];
        $this->assertCount(1, $errorsList);
        $this->assertContains('Currency does not match.', $errorsList, 'The "receipt" not validating.');

        $model->setReceipt($receipt);
        $this->assertTrue($model->isValid());
    }

    /**
     * @depends testValidation
     * @return void
     */
    public function testValidationCurrencyException()
    {
        $this->expectException(\Watson\Validating\ValidationException::class);
        $this->expectExceptionMessage('The operation uuid field is required. (and 3 more errors)');
        (new Receipt())
            ->isValidOrFail();
    }
}
