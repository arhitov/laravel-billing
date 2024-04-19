<?php

namespace Arhitov\LaravelBilling\Tests\Feature;

use Arhitov\LaravelBilling\Contracts\BillableInterface;
use Arhitov\LaravelBilling\Exceptions;
use Arhitov\LaravelBilling\Tests\FeatureTestCase;
use Illuminate\Database\Eloquent\Model;

class CreatePaymentExceptionTest extends FeatureTestCase
{
    private Model|BillableInterface $owner;
    private array $billingConfigSaved;
    private string $billingConfigOmnipayGatewayDefault;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = $this->createOwner();
        $this->billingConfigSaved = config('billing');
        $this->billingConfigOmnipayGatewayDefault = config('billing.omnipay_gateway.default');
    }

    /**
     * @return void
     */
    public function testBalanceNotFoundException()
    {
        $this->expectException(Exceptions\BalanceNotFoundException::class);
        $this->owner->createPayment(
            100,
            'Test payment',
            'main'
        );
    }

    /**
     * @depends testBalanceNotFoundException
     * @return void
     */
    public function testGatewayNotFoundException()
    {
        $balance = $this->owner->getBalanceOrCreate();

        $this->expectException(Exceptions\Gateway\GatewayNotFoundException::class);
        $this->owner->createPayment(
            100,
            'Test payment',
            $balance,
            gatewayName: 'qwe',
        );
    }

    /**
     * @depends testGatewayNotFoundException
     * @return void
     */
    public function testGatewayNotSpecifiedException()
    {
        $balance = $this->owner->getBalanceOrCreate();

        // Set null for default gateway
        $billingConfig = config('billing');
        $billingConfig['omnipay_gateway']['default'] = null;
        config(['billing' => $billingConfig]);

        $this->expectException(Exceptions\Gateway\GatewayNotSpecifiedException::class);
        $this->owner->createPayment(
            100,
            'Test payment',
            $balance,
        );
    }

    /**
     * @depends testGatewayNotSpecifiedException
     * @return void
     */
    public function testRestoreConfigAfterTestGatewayNotSpecifiedException()
    {
        config(['billing' => $this->billingConfigSaved]);
        $this->assertEquals($this->billingConfigOmnipayGatewayDefault, config('billing.omnipay_gateway.default'));
    }
}
