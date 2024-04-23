<?php

namespace Arhitov\LaravelBilling\Http\Controllers;

use Arhitov\LaravelBilling\Exceptions\Gateway\GatewayException;
use Arhitov\LaravelBilling\Exceptions\Operation\OperationNotFoundException;
use Arhitov\LaravelBilling\Models\Operation;
use Arhitov\LaravelBilling\OmnipayGateway;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Omnipay\Common\Exception\InvalidResponseException;

class WebhookController
{
    public function webhookNotification(Request $request, string $gateway = null): Response
    {
        $omnipayGateway = new OmnipayGateway($gateway, httpRequest: $request);
        $gateway = $omnipayGateway->getGatewayName();

        /** @var \Omnipay\Common\Message\AbstractResponse $response */
        $response = $omnipayGateway->notification()->send();

        $gatewayPaymentId = $response->getTransactionReference();

        if (! $omnipayGateway->getConfig('webhook.trust_input_data', false)) {
            try {
                /** @var \Omnipay\Common\Message\AbstractResponse $response */
                $response = $omnipayGateway->details([
                    'transactionReference' => $gatewayPaymentId,
                ])->send();
            } catch (InvalidResponseException $exception) {
                throw new GatewayException($gateway, $exception->getMessage(), exception: $exception);
            }
        }

        $gatewayPaymentId = $response->getTransactionReference();
        /** @var Operation|null $operation */
        $operation = Operation::query()
                              ->where('gateway', '=', $gateway)
                              ->where('gateway_payment_id', '=', $gatewayPaymentId)
                              ->first();
        if (! $operation) {
            throw new OperationNotFoundException("Operation not found for {$gateway}:{$gatewayPaymentId}");
        }

        $operation->setStateByOmnipayGateway($response)
                  ->saveOrFail();

        return response($omnipayGateway->getConfig('webhook.response.content'), $omnipayGateway->getConfig('webhook.response.status', 201));
    }
}
