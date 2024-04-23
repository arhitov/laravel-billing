<?php

namespace Arhitov\LaravelBilling\Console\Commands;

use Arhitov\LaravelBilling\Exceptions\Gateway\GatewayNotFoundException;
use Arhitov\LaravelBilling\Exceptions\Gateway\GatewayNotSupportMethodException;
use Arhitov\LaravelBilling\Models\Operation;
use Arhitov\LaravelBilling\OmnipayGateway;
use Illuminate\Console\Command;
use Omnipay\Common\Exception\InvalidResponseException;

#[\Symfony\Component\Console\Attribute\AsCommand(name: 'billing:get-payment-information')]
class GetPaymentInformationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:get-payment-information {operation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get payment information';

    /**
     * @return int
     * @throws \Arhitov\LaravelBilling\Exceptions\Gateway\GatewayNotFoundException
     * @throws \Arhitov\LaravelBilling\Exceptions\Gateway\GatewayNotSpecifiedException
     */
    public function handle(): int
    {
        $operationUuid = $this->argument('operation');

        /** @var Operation|null $operation */
        $operation = Operation::query()
            ->where('operation_uuid', '=', $operationUuid)
            ->first();

        if (! $operation) {
            $this->error('Operation not found!');
            return self::FAILURE;
        }

        $gateway = $operation->gateway;
        $response = null;
        $gatewayPaymentStatus = null;
        if (! empty($operation->gateway_payment_id)) {
            try {
                $omnipayGateway = new OmnipayGateway($operation->gateway);

                $details = $omnipayGateway->details([
                    'transactionReference' => $operation->gateway_payment_id,
                ]);
                /** @var \Omnipay\Common\Message\AbstractResponse $response */
                $response = $details->send();

                $gatewayPaymentStatus = method_exists($response, 'getState') ? $response->getState() : null;

            } catch (GatewayNotSupportMethodException) {
                $response = null;
            } catch (GatewayNotFoundException) {
                $gateway .= ' (Error: Gateway not found!)';
            } catch (InvalidResponseException $exception) {
                $gateway .= " (Error: {$exception->getMessage()})";
            }
        }

        $this->info("Gateway: {$gateway}");
        $this->info('TransactionReference: '. ($response->gateway_payment_id ?? '-'));
        $this->info('TransactionId: ' . $operation->operation_uuid);
        $this->info('Paid: ' . ($operation->state->isPaid() ? 'YES' : 'NO'));
        $this->info("Amount: {$operation->amount} {$operation->currency->value}");
        $this->info('State payment: '. ($gatewayPaymentStatus ?? '-'));
        $this->info("State operation: {$operation->state->value}");
        if ($response && $response->isSuccessful()) {
            $this->info("Payer: {$response->getPayer()}");
            $this->info("Payment date: {$response->getPaymentDate()->format(DATE_ATOM)}");
        }

        return self::SUCCESS;
    }
}
