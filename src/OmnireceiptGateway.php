<?php
/**
 * Billing module for laravel projects
 *
 * @link      https://github.com/arhitov/laravel-billing
 * @package   arhitov/laravel-billing
 * @license   MIT
 * @copyright Copyright (c) 2024, Alexander Arhitov, clgsru@gmail.com
 */

namespace Arhitov\LaravelBilling;

use Arhitov\LaravelBilling\Contracts\BillableInterface;
use Arhitov\LaravelBilling\Exceptions\Gateway\GatewayNotFoundException;
use Arhitov\LaravelBilling\Exceptions\Gateway\GatewayNotSpecifiedException;
use Arhitov\LaravelBilling\Models\Receipt;
use Omnireceipt\Common\AbstractGateway;
use Omnireceipt\Common\Contracts\GatewayInterface;
use Omnireceipt\Common\Contracts\Http\ClientInterface;
use Omnireceipt\Omnireceipt;
use Symfony\Component\HttpFoundation\Request;

class OmnireceiptGateway
{
    private GatewayInterface $gateway;
    private string $gatewayName;
    private array $gatewayConfig;

    public function __construct(string|null $gateway, ClientInterface $httpClient = null, Request $httpRequest = null)
    {

        $gateway ??= config('billing.omnireceipt_gateway.default', null);
        if (empty($gateway)) {
            throw new GatewayNotSpecifiedException();
        }

        $gatewayConfig = config("billing.omnireceipt_gateway.gateways.{$gateway}", null);
        if (is_null($gatewayConfig)) {
            throw new GatewayNotFoundException($gateway);
        }

        $this->gatewayName = $gateway;
        $this->gatewayConfig = $gatewayConfig;

        // Initialization gateway
        $this->gateway = Omnireceipt::create($gatewayConfig['omnireceipt_class'], httpClient: $httpClient, httpRequest: $httpRequest);
        if (! empty($gatewayConfig['omnireceipt_initialize'])) {
            $this->gateway->initialize($gatewayConfig['omnireceipt_initialize']);
        }
    }

    /**
     * @return \Omnireceipt\Common\AbstractGateway
     */
    public function getGateway(): AbstractGateway
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

    public function receiptFactory(BillableInterface $owner, array $parameters = [], array ...$parametersItemList): Receipt
    {
        $receipt = $this->getGateway()->receiptFactory($parameters, ...$parametersItemList);
        $receipt->setCustomer(
            $this->getGateway()->customerFactory(
                array_filter([
                    'name' => $owner->getOwnerName(),
                    'email' => $owner->getOwnerEmail(),
                    'phone' => $owner->getOwnerPhone(),
                ])
            )
        );
        $receipt->validateOrFail();

        return $owner->receipt()->make([
            'gateway' => $this->getGatewayName(),
            ])
            ->setReceipt($receipt);
    }
}
