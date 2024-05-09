<?php
/**
 * Billing module for laravel projects
 *
 * @link      https://github.com/arhitov/laravel-billing
 * @package   arhitov/laravel-billing
 * @license   MIT
 * @copyright Copyright (c) 2024, Alexander Arhitov, clgsru@gmail.com
 */

namespace Arhitov\LaravelBilling\Console\Commands;

use Arhitov\LaravelBilling\Exceptions\Gateway\GatewayNotFoundException;
use Arhitov\LaravelBilling\Models\Operation;
use Arhitov\LaravelBilling\OmnipayGateway;
use Illuminate\Console\Command;
use Omnipay\Common\Exception\InvalidRequestException;

#[\Symfony\Component\Console\Attribute\AsCommand(name: 'billing:capture-omnipay-payment')]
class CaptureOmnipayPaymentCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:capture-omnipay-payment {transaction} {--gateway=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Capture payment';

    public function handle(): int
    {
        $transaction = $this->argument('transaction');
        $gateway = $this->option('gateway') ?? null;

        try {
            $omnipayGateway = new OmnipayGateway($gateway);
        } catch (GatewayNotFoundException) {
            $this->error('Gateway not found!');
            return self::FAILURE;
        }

        /** @var Operation|null $operation */
        $operation = Operation::query()
            ->where('gateway', '=', $gateway)
            ->where('gateway_payment_id', '=', $transaction)
            ->first();

        if (is_null($operation)) {
            $this->error('Payment not found!');
            return self::FAILURE;
        }

        $capture = $omnipayGateway->capture([
            'transactionReference' => $transaction,
            'amount' => $operation->amount,
            'currency' => $operation->currency->value,
        ]);

        try {
            /** @var \Omnipay\Common\Message\AbstractResponse $response */
            $response = $capture->send();
        } catch (InvalidRequestException $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $operation->setStateByOmnipayGateway($omnipayGateway, $response)
                  ->saveOrFail();

        $gatewayPaymentStatus = method_exists($response, 'getState') ? $response->getState() : null;

        $this->info("TransactionReference: {$response->getTransactionReference()}");
        $this->info('TransactionId: ' . $operation->operation_uuid);
        $this->info('Paid: ' . ($response->isSuccessful() ? 'YES' : 'NO'));
        $this->info("Amount: {$response->getAmount()} {$response->getCurrency()}");
        $this->info("State payment: {$gatewayPaymentStatus}");
        $this->info("State operation: {$operation->state->value}");
        if ($response->isSuccessful()) {
            $this->info("Payer: {$response->getPayer()}");
            $this->info("Payment date: {$response->getPaymentDate()->format(DATE_ATOM)}");
        }

        return self::SUCCESS;
    }
}
