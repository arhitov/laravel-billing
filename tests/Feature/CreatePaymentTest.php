<?php

namespace Arhitov\LaravelBilling\Tests\Feature;

use Arhitov\LaravelBilling\Tests\FeatureTestCase;
use Omnipay\Common\CreditCard;

class CreatePaymentTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Append test dummy gateway
        $billingConfigSaved = config('billing');
        $billingConfigSaved['omnipay_gateway']['gateways']['billing-feature-create-payment-dummy'] = $this->getConfigOmnipayGatewayDummy();
        $billingConfigSaved['omnipay_gateway']['gateways']['billing-feature-create-payment-yookassa'] = $this->getConfigOmnipayGatewayYooKassa();
        config(['billing' => $billingConfigSaved]);
    }

    /**
     * @return void
     */
    public function testCreateSimpleByDummy()
    {
        $balanceKey = 'main';
        $owner = $this->createOwner();
        $owner->getBalanceOrCreate($balanceKey);

        $payment = $owner->createPayment(
            100,
            'Test payment',
            $balanceKey,
            gatewayName: 'billing-feature-create-payment-dummy',
            card: $this->getDataValidCard(),
        );

        $response = $payment->getResponse();
        $operation = $payment->getIncrease()->getOperation();
        $gatewayPaymentId = $response->getTransactionReference();
        $gatewayPaymentStatus = method_exists($response, 'getState') ? $response->getState() : null;

        $this->assertEquals($gatewayPaymentId, $operation->gateway_payment_id);
        $this->assertEquals($gatewayPaymentStatus, $operation->gateway_payment_status);

        $this->assertTrue(true);
    }

    /**
     * @depends testCreateSimpleByDummy
     * @return void
     */
    public function testCreateByDummy()
    {
        $balanceAmount = 123;
        $owner = $this->createOwner();
        $balance = $owner->getBalanceOrCreate();

        $this->assertEquals(0, $balance->amount);

        $card = new CreditCard($this->getDataValidCard());

        $payment = $owner->createPayment(
            $balanceAmount,
            'Test payment',
            gatewayName: 'billing-feature-create-payment-dummy',
            card: $card,
        );

        $this->assertTrue($payment->getResponse()->isSuccessful());
        $this->assertFalse($payment->getResponse()->isRedirect());

        $this->assertEquals($balanceAmount, $owner->getBalance()->amount);

        $this->assertNotEmpty($payment->getResponse()->getTransactionReference());
    }

    /**
     * @depends testCreateSimpleByDummy
     * @return void
     */
    public function testCreateByYooKassa()
    {
        $balanceAmount = 456;
        $owner = $this->createOwner();
        $balance = $owner->getBalanceOrCreate();

        $this->assertEquals(0, $balance->amount);

        $payment = $owner->createPayment(
            $balanceAmount,
            'Test payment',
            gatewayName: 'billing-feature-create-payment-yookassa',
        );

        $this->assertFalse($payment->getResponse()->isSuccessful());
        $this->assertTrue($payment->getResponse()->isRedirect());
        $this->assertEquals(
            $this->getConfigOmnipayGatewayYooKassa()['returnUrl'],
            $payment->getResponse()->getRequest()->getReturnUrl()
        );

        $this->assertEquals(0, $owner->getBalance()->amount);

        $this->assertEquals(
            $payment->getIncrease()->getOperation()->operation_uuid,
            $payment->getResponse()->getTransactionId()
        );

        $this->assertNotEmpty($payment->getResponse()->getTransactionReference());
        $this->assertNotEmpty($payment->getResponse()->getRedirectUrl());
    }
}
