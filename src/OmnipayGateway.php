<?php

namespace Arhitov\LaravelBilling;

use Arhitov\LaravelBilling\Exceptions\Gateway\GatewayNotFoundException;
use Arhitov\LaravelBilling\Exceptions\Gateway\GatewayNotSpecifiedException;
use Arhitov\LaravelBilling\Exceptions\Gateway\GatewayNotSupportMethodException;
use Omnipay\Common\GatewayInterface;
use Omnipay\Common\Message\AbstractRequest;
use Omnipay\Common\Message\RequestInterface;
use Omnipay\Omnipay;
use Symfony\Component\HttpFoundation\Request;

class OmnipayGateway
{
    private GatewayInterface $gateway;
    private string $gatewayName;
    private array $gatewayConfig;

    public function __construct(string|null $gateway, Request $httpRequest = null)
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
        $this->gateway = Omnipay::create($gatewayConfig['omnipay_class'], httpRequest: $httpRequest);
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
     * Create a payment.
     *
     * @param array $options
     * @return AbstractRequest|RequestInterface
     */
    public function purchase(array $options = []): AbstractRequest|RequestInterface
    {
        return $this->getGateway()->purchase($options);
    }

    /**
     * Payment confirmation.
     *
     * @param array $options
     * @return AbstractRequest|RequestInterface
     */
    public function capture(array $options = []): AbstractRequest|RequestInterface
    {
        return $this->getGateway()->capture($options);
    }

    /**
     * Get payment information.
     *
     * @param array $options
     * @return AbstractRequest
     * @throws GatewayNotSupportMethodException
     */
    public function details(array $options = []): AbstractRequest
    {
        $gatewayInterface = $this->getGateway();
        if (! method_exists($gatewayInterface, 'details')) {
            throw new GatewayNotSupportMethodException($this->getGatewayName(), 'details');
        }

        return $gatewayInterface->details($options);
    }

    /**
     * Input payment notification.
     *
     * @param array $options
     * @return AbstractRequest
     * @throws GatewayNotSupportMethodException
     */
    public function notification(array $options = []): AbstractRequest
    {
        $gatewayInterface = $this->getGateway();
        if (! method_exists($gatewayInterface, 'notification')) {
            throw new GatewayNotSupportMethodException($this->getGatewayName(), 'notification');
        }

        return $gatewayInterface->notification($options);
    }

    /**
     * @param string|null $key
     * @param $default
     * @return mixed
     */
    public function getConfig(string $key = null, $default = null): mixed
    {
        if ($key) {
            $value = $this->gatewayConfig;
            foreach (explode('.', $key) as $keyPart) {
                if (is_array($value) && array_key_exists($keyPart, $value)) {
                    $value = $value[$keyPart];
                } else {
                    return $default;
                }
            }
            return $value;
        }
        return $this->gatewayConfig;
    }

    /**
     * @param array $parameters using only for return_route
     * @return string|null
     */
    public function getReturnUrl(array $parameters = []): ?string
    {
        return match (true) {
            ! empty($this->gatewayConfig['return_url']) => $this->gatewayConfig['return_url'],
            ! empty($this->gatewayConfig['return_route']) => (function() use ($parameters) {
                if (is_array($this->gatewayConfig['return_route'])) {
                    $parametersConfig = $this->gatewayConfig['return_route']['parameters'] ?? [];
                    array_walk(
                        $parametersConfig,
                        fn(&$value, $key) => $value = (is_null($value) && array_key_exists($key,
                                $parameters)) ? $parameters[$key] : $value,
                    );
                    foreach ($parameters as $key => $value) {
                        if (! array_key_exists($key, $parametersConfig)) {
                            $parametersConfig[$key] = $value;
                        }
                    }
                    return route(
                        $this->gatewayConfig['return_route']['name'],
                        $parametersConfig,
                    );
                } else {
                    return route($this->gatewayConfig['return_route']);
                }
            })(),
            default => null
        };
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
}
