<?php

namespace Arhitov\LaravelBilling\Console\Commands;

use Arhitov\LaravelBilling\Exceptions\Gateway\GatewayNotFoundException;
use Arhitov\LaravelBilling\Models\Balance;
use Arhitov\LaravelBilling\OmnipayGateway;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

#[\Symfony\Component\Console\Attribute\AsCommand(name: 'billing:create-payment')]
class CreatePaymentCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:create-payment {balance} {amount} {--description=} {--gateway=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Decrease the owner\'s balance';

    /**
     * @return int
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Arhitov\LaravelBilling\Exceptions\Gateway\GatewayException
     */
    public function handle(): int
    {
        $input = Validator::make(
            array_merge(
                $this->arguments(),
                $this->options(),
            ),
            [
                'balance'     => ['required', 'int', 'min:1'],
                'amount'      => ['required', 'billing_amount', 'min:0'],
                'description' => ['nullable', 'string', 'max:1000'],
                'gateway'     => ['nullable', 'string', 'max:50'],
            ],
        )->validate();

        $input['description'] ??= config('billing.omnipay_gateway.payment.default_description');

        /** @var Balance|null $balance */
        $balance = Balance::find($input['balance']);
        if (is_null($balance)) {
            $this->error('Balance not found!');
            return self::FAILURE;
        }

        try {
            $omnipayGateway = new OmnipayGateway($input['gateway'] ?? null);
        } catch (GatewayNotFoundException) {
            $this->error('Gateway not found!');
            return self::FAILURE;
        }

        if ($omnipayGateway->isCardRequired()) {
            $number = (string)$this->ask('Please input card number?');
            $expiry = (string)$this->ask('Please input card expiry?');
            $cvv =    (string)$this->ask('Please input card cvv?');

            $cardData = Validator::make(
                [
                    'number' => $number,
                    'expiry' => $expiry,
                    'cvv'    => $cvv,
                ],
                [
                    'number' => ['required', 'string', 'regex:/^\d{12,19}$/'],
                    'expiry' => ['required', 'string', 'regex:/^\d{1,2}\/\d{2,4}$/'],
                    'cvv'    => ['required', 'string', 'min:3', 'max:3'],
                ],
            )->validate();

            $payment = $balance->owner->createPayment(
                $input['amount'],
                $input['description'],
                $balance,
                gatewayName: $input['gateway'],
                card: [
                    'number' => $cardData['number'],
                    'expiryMonth' => explode('/', $cardData['expiry'])[0],
                    'expiryYear' => explode('/', $cardData['expiry'])[1],
                    'cvv' => $cardData['cvv'],
                ],
            );
        } else {
            $payment = $balance->owner->createPayment(
                $input['amount'],
                $input['description'],
                $balance,
                gatewayName: $input['gateway'],
            );
        }

        $this->info("Created payment: {$payment->getIncrease()->getOperation()->getKey()}");
        $this->info("Payment operation_uuid: {$payment->getIncrease()->getOperation()->operation_uuid}");

        if ($payment->getResponse()->isSuccessful()) {
            $this->info('Payment successful');
            return self::SUCCESS;
        } elseif ($payment->getResponse()->isRedirect()) {
            $this->info('Please, goto link for payment: ' . $payment->getResponse()->getRedirectUrl());
            return self::SUCCESS;
        } else {
            $this->error($payment->getResponse()->getMessage());
            return self::FAILURE;
        }
    }
}
