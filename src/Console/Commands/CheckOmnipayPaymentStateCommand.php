<?php

namespace Arhitov\LaravelBilling\Console\Commands;

use Arhitov\LaravelBilling\Exceptions\Gateway\GatewayNotFoundException;
use Arhitov\LaravelBilling\Models\Operation;
use Arhitov\LaravelBilling\OmnipayGateway;
use Illuminate\Console\Command;
use Omnipay\Common\Exception\InvalidResponseException;

#[\Symfony\Component\Console\Attribute\AsCommand(name: 'billing:check-omnipay-payment-state')]
class CheckOmnipayPaymentStateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:check-omnipay-payment-state {transaction} {--gateway=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check payment state';

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
            /** @var \Omnipay\Common\Message\AbstractResponse $response */
            $response = $details->send();
        } catch (InvalidResponseException $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        /** @var Operation|null $operation */
        $operation = Operation::query()
                              ->where('gateway', '=', $gateway)
                              ->where('gateway_payment_id', '=', $response->getTransactionReference())
                              ->first();
        if (! $operation) {
            $this->error('Operation for payment not found');
            return self::FAILURE;
        }

        $operation->setStateByOmnipayGateway($response)
                  ->saveOrFail();

        return self::SUCCESS;
    }
}
