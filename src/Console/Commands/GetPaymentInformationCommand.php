<?php

namespace Arhitov\LaravelBilling\Console\Commands;

use Arhitov\LaravelBilling\Exceptions\Gateway\GatewayNotFoundException;
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
    protected $signature = 'billing:get-payment-information {transaction} {--gateway=}';

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
        $transaction = $this->argument('transaction');
        $gateway = $this->option('gateway') ?? null;

        try {
            $omnipayGateway = new OmnipayGateway($gateway);
        } catch (GatewayNotFoundException) {
            $this->error('Gateway not found!');
            return self::FAILURE;
        }

        if (! $omnipayGateway->isSupportDetails()) {
            $this->warn('This gateway cannot receive payment information.');
            return self::FAILURE;
        }

        $details = $omnipayGateway->getGateway()->details([
            'transactionReference' => $transaction,
        ]);
        try {
            $response = $details->send();
        } catch (InvalidResponseException $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $this->info("TransactionReference: {$response->getTransactionReference()}");
        $this->info('TransactionId: ' . ($response->getTransactionId() ?? '-'));
        $this->info('Paid: ' . ($response->isSuccessful() ? 'YES' : 'NO'));
        $this->info("Amount: {$response->getAmount()} {$response->getCurrency()}");
        $this->info("State: {$response->getState()}");
        if ($response->isSuccessful()) {
            $this->info("Payer: {$response->getPayer()}");
            $this->info("Payment date: {$response->getPaymentDate()->format(DATE_ATOM)}");
        }

        return self::SUCCESS;
    }
}
