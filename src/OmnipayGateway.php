<?php

namespace Arhitov\LaravelBilling;

use Arhitov\LaravelBilling\Exceptions\Gateway\GatewayNotFoundException;
use Arhitov\LaravelBilling\Exceptions\Gateway\GatewayNotSpecifiedException;
use Omnipay\Common\GatewayInterface;
use Omnipay\Omnipay;

class OmnipayGateway
{
    private GatewayInterface $gateway;
    private string $gatewayName;
    private array $gatewayConfig;

    public function __construct(string|null $gateway)
    {

        $gateway ??= config('billing.omnipay_gateway.default', null);
        if (empty($gateway)) {
            throw new GatewayNotSpecifiedException();
        }

        $gatewayConfig = config("billing.omnipay_gateway.gateways.{$gateway}", null);
        if (is_null($gatewayConfig)) {
            throw new GatewayNotFoundException($gateway);
        }

        $this->gatewayName = $gateway;
        $this->gatewayConfig = $gatewayConfig;

        // Initialization gateway
        $this->gateway = Omnipay::create($gatewayConfig['omnipay_class']);
        if (! empty($gatewayConfig['omnipay_initialize'])) {
            $this->gateway->initialize($gatewayConfig['omnipay_initialize']);
        }
    }

    /**
     * @return \Omnipay\Common\GatewayInterface
     */
    public function getGateway(): GatewayInterface
    {
        return $this->gateway;
    }

    /**
     * @return string
     */
    public function getGatewayName(): string
    {
        return $this->gatewayName;
    }

    /**
     * @return string|null
     */
    public function getReturnUrl(): ?string
    {
        return $this->gatewayConfig['returnUrl'] ?? null;
    }

    /**
     * It is required to send card data in the request.
     *
     * @return bool
     */
    public function isCardRequired(): bool
    {
        return (bool)($this->gatewayConfig['card_required'] ?? false);
    }

    /**
     * Automatic payment acceptance.
     * true = One-stage payments. The payment is transferred to the success state immediately after payment.
     * false = Two-stage payments. Confirmation of receipt of payment from your side is required.
     *
     * @return bool
     */
    public function getCapture(): bool
    {
        return $this->gatewayConfig['capture'] ?? true;
    }

    /**
     * Status: pending -> (payment) -> succeeded
     *
     * @return bool
     */
    public function isOneStagePayment(): bool
    {
        return $this->getCapture();
    }

    /**
     * Status: pending -> (payment) -> waiting_for_capture -> (your confirmation) -> succeeded
     *
     * @return bool
     */
    public function isTwoStagePayment(): bool
    {
        return ! $this->getCapture();
    }

    public function isSupportDetails(): bool
    {
        return method_exists($this->gateway, 'details');
    }
}
