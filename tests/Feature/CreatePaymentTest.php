<?php

namespace Arhitov\LaravelBilling\Tests\Feature;

use Arhitov\LaravelBilling\Tests\FeatureTestCase;
use Omnipay\Common\CreditCard;

class CreatePaymentTest extends FeatureTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        /** @var \Illuminate\Routing\Router $router */
        $router = $this->app['router'];
        $router->get('yookassa/return-url')->name('yookassa-return-url');

        /** @var \Illuminate\Routing\UrlGenerator $url */
        $url = $this->app['url'];
        $url->forceScheme('https');
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
            gatewayName: 'dummy',
            card: $this->getDataValidCard(),
        );

        $response = $payment->getResponse();
        $operation = $payment->getIncrease()->getOperation();
        $gatewayPaymentId = $response->getTransactionReference();
        $gatewayPaymentStatus = method_exists($response, 'getState') ? $response->getState() : null;

        $this->assertEquals($gatewayPaymentId, $operation->gateway_payment_id);
        $this->assertEquals($gatewayPaymentStatus, $operation->gateway_payment_state);

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
            gatewayName: 'dummy',
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
            gatewayName: 'yookassa',
        );

        $this->assertFalse($payment->getResponse()->isSuccessful());
        $this->assertTrue($payment->getResponse()->isRedirect());
        $this->assertEquals(
            config('billing.omnipay_gateway.gateways.yookassa.return_url'),
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

    /**
     * @depends testCreateByYooKassa
     * @return void
     */
    public function testCreateUseRouteName()
    {
        $balanceAmount = 456;
        $owner = $this->createOwner();
        $owner->getBalanceOrCreate();

        $payment = $owner->createPayment(
            $balanceAmount,
            'Test payment',
            gatewayName: 'yookassa-use-route-name',
        );

        $returnUrl = $payment->getResponse()->getRequest()->getReturnUrl();

        $this->assertNotEmpty($returnUrl);
        $this->assertEquals(
            route('yookassa-return-url', ['operation_uuid' => $payment->getIncrease()->getOperation()->operation_uuid, 'order' => 123]),
            $returnUrl,
        );
    }
}
