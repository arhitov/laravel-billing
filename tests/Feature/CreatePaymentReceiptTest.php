<?php
/**
 * Billing module for laravel projects
 *
 * @link      https://github.com/arhitov/laravel-billing
 * @package   arhitov/laravel-billing
 * @license   MIT
 * @copyright Copyright (c) 2024, Alexander Arhitov, clgsru@gmail.com
 */

namespace Arhitov\LaravelBilling\Tests\Feature;

use Arhitov\LaravelBilling\Models\Receipt;
use Arhitov\LaravelBilling\OmnipayGateway;
use Arhitov\LaravelBilling\Tests\FeatureTestCase;
use PHPUnit\Framework\Attributes\Depends;

class CreatePaymentReceiptTest extends FeatureTestCase
{
    /**
     * @return void
     */
    public function testByCreatePaymentOneStep()
    {
        $balanceKey = 'main';
        $owner = $this->createOwner();
        $owner->getBalanceOrCreate($balanceKey);

        $this->assertEquals(0, $owner->receipt()->count());

        $payment = $owner->createPayment(
            100,
            'Test payment',
            $balanceKey,
            gatewayName: 'dummy-omnireceipt-full',
            card: $this->getDataValidCard(),
        );

        $response = $payment->getResponse();
        $this->assertTrue($response->isSuccessful());

        $operation = $payment->getIncrease()->getOperation();
        $gatewayPaymentId = $response->getTransactionReference();
        $gatewayPaymentStatus = method_exists($response, 'getState') ? $response->getState() : null;

        $this->assertFalse($operation->state->isActive());
        $this->assertTrue($operation->state->isSucceeded());
        $this->assertEquals($gatewayPaymentId, $operation->gateway_payment_id);
        $this->assertEquals($gatewayPaymentStatus, $operation->gateway_payment_state);

        /** @var Receipt $receipt */
        $receipt = $owner->receipt()->first();
        $this->assertTrue($receipt->state->isActive());
        $this->assertFalse($receipt->state->isSucceeded());
        $this->assertEquals($operation->operation_uuid, $receipt->operation_uuid);
    }

    /**
     * @depends testByCreatePaymentOneStep
     * @return void
     */
    #[Depends('testByCreatePaymentOneStep')]
    public function testByCreatePaymentTwoStep()
    {
        $balanceKey = 'main';
        $gatewayName = 'yookassa-two-step-omnireceipt-full';

        $owner = $this->createOwner();
        $owner->getBalanceOrCreate($balanceKey);

        $this->assertEquals(0, $owner->receipt()->count());

        $payment = $owner->createPayment(
            100,
            'Test payment',
            $balanceKey,
            gatewayName: $gatewayName,
            card: $this->getDataValidCard(),
        );

        $response = $payment->getResponse();
        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isRedirect());

        $operation = $payment->getIncrease()->getOperation();
        $gatewayPaymentId = $response->getTransactionReference();
        $gatewayPaymentStatus = method_exists($response, 'getState') ? $response->getState() : null;

        $this->assertTrue($operation->state->isActive());
        $this->assertFalse($operation->state->isSucceeded());
        $this->assertEquals($gatewayPaymentId, $operation->gateway_payment_id);
        $this->assertEquals($gatewayPaymentStatus, $operation->gateway_payment_state);

        /** @var Receipt $receipt */
        $receipt = $owner->receipt()->first();
        $this->assertTrue($receipt->state->isActive());
        $this->assertFalse($receipt->state->isSucceeded());
        $this->assertEquals($operation->operation_uuid, $receipt->operation_uuid);

        $omnipayGateway = new OmnipayGateway(
            $gatewayName,
            httpRequest: new \Symfony\Component\HttpFoundation\Request(
                content: '{"object":{"id":"' . $operation->gateway_payment_id . '","metadata":{"transactionId":"' . $operation->operation_uuid . '"}}}',
            ),
        );

        /** @var \Omnipay\Common\Message\AbstractResponse $response */
        $response = $omnipayGateway->notification()->send();

        $transactionId = $response->getTransactionId();
        $this->assertEquals($operation->operation_uuid, $transactionId);

        $operation->setStateByOmnipayGateway($omnipayGateway, $response)
                  ->saveOrFail();

        $this->assertFalse($operation->state->isActive());
        $this->assertTrue($operation->state->isSucceeded());

        /** @var Receipt $receipt */
        $receipt = $owner->receipt()->first();
        $this->assertTrue($receipt->state->isActive());
        $this->assertTrue($receipt->state->isPaid());
        $this->assertFalse($receipt->state->isSucceeded());
        $this->assertEquals($operation->operation_uuid, $receipt->operation_uuid);
    }

    /**
     * @depends testByCreatePaymentOneStep
     * @return void
     */
    #[Depends('testByCreatePaymentOneStep')]
    public function testByCreatePaymentNoReceipt()
    {
        $balanceKey = 'main';
        $gatewayName = 'yookassa-two-step';

        $owner = $this->createOwner();
        $owner->getBalanceOrCreate($balanceKey);

        $this->assertEquals(0, $owner->receipt()->count());

        $payment = $owner->createPayment(
            100,
            'Test payment',
            $balanceKey,
            gatewayName: $gatewayName,
            card: $this->getDataValidCard(),
        );

        $response = $payment->getResponse();
        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isRedirect());

        $operation = $payment->getIncrease()->getOperation();
        $gatewayPaymentId = $response->getTransactionReference();
        $gatewayPaymentStatus = method_exists($response, 'getState') ? $response->getState() : null;

        $this->assertTrue($operation->state->isActive());
        $this->assertFalse($operation->state->isSucceeded());
        $this->assertEquals($gatewayPaymentId, $operation->gateway_payment_id);
        $this->assertEquals($gatewayPaymentStatus, $operation->gateway_payment_state);


        $this->assertEquals(0, $owner->receipt()->count());

        $omnipayGateway = new OmnipayGateway(
            $gatewayName,
            httpRequest: new \Symfony\Component\HttpFoundation\Request(
                content: '{"object":{"id":"' . $operation->gateway_payment_id . '","metadata":{"transactionId":"' . $operation->operation_uuid . '"}}}',
            ),
        );

        /** @var \Omnipay\Common\Message\AbstractResponse $response */
        $response = $omnipayGateway->notification()->send();

        $transactionId = $response->getTransactionId();
        $this->assertEquals($operation->operation_uuid, $transactionId);

        $operation->setStateByOmnipayGateway($omnipayGateway, $response)
                  ->saveOrFail();

        $this->assertFalse($operation->state->isActive());
        $this->assertTrue($operation->state->isSucceeded());

        $this->assertEquals(0, $owner->receipt()->count());
    }
}
