<?php
/**
 * Billing module for laravel projects
 *
 * @link      https://github.com/arhitov/laravel-billing
 * @package   arhitov/laravel-billing
 * @license   MIT
 * @copyright Copyright (c) 2024, Alexander Arhitov, clgsru@gmail.com
 */

namespace Arhitov\LaravelBilling\Tests\Feature\FiscalReceipt;

use Arhitov\LaravelBilling\Enums\ReceiptStateEnum;
use Arhitov\LaravelBilling\Models\Receipt;
use Arhitov\LaravelBilling\Services\FiscalReceipt\FiscalReceiptCheckState;
use Arhitov\LaravelBilling\Tests\Fixtures\FixtureTrait;
use Arhitov\LaravelBilling\Tests\MockTestCase;

class FiscalReceiptCheckStateTest extends MockTestCase
{
    use FixtureTrait;

    public function testBase()
    {
        $gatewayName = 'dummy';
        $service = new FiscalReceiptCheckState(
            $gatewayName,
            httpClient: $this->getHttpClient(),
        );

        $this->assertEquals(0, $service->countActive());

        $receipt = new Receipt;
        $receiptData = $this->fixtureAsArray('fiscal_receipt_check_state');
        $receiptData['gateway'] = $gatewayName;
        foreach ($receiptData as $key => $value) {
            $receipt->$key = $value;
        }

        $this->assertEquals(0, Receipt::query()->count());

        $this->assertTrue($receipt->isValid(), json_encode($receipt->getErrors(), JSON_UNESCAPED_UNICODE));
        $receipt->saveOrFail();
        $this->assertEquals(1, Receipt::query()->count());

        $this->assertTrue($receipt->state->isActive());
        $this->assertFalse($receipt->state->isSucceeded());

        $this->assertTrue($receipt->changeState(ReceiptStateEnum::Paid));
        $receipt->saveOrFail();
        $this->assertTrue($receipt->state->isActive());
        $this->assertFalse($receipt->state->isSucceeded());

        $this->assertEquals(1, $service->countActive());

        $this->setMockHttpResponse('List_Successful.txt');
        $service->execute();

        $receipt->refresh();
        $this->assertFalse($receipt->state->isActive());
        $this->assertTrue($receipt->state->isSucceeded());
    }
}
