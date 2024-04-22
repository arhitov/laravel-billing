<?php

namespace Arhitov\LaravelBilling\Tests\Manual\Console\Commands\YooKassa;

use Arhitov\LaravelBilling\Models\Operation;
use Arhitov\LaravelBilling\Tests\ConsoleCommandsTestCase;

class CaptureOmnipayPaymentCommandTest extends ConsoleCommandsTestCase
{
    const GATEWAY = 'yookassa-two-step';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMockingConsoleOutput();
    }

    public function testCommand()
    {
        $owner = $this->createOwner();
        $balance = $owner->getBalanceOrCreate();
        $amount = '123.45';

        $this->assertEquals(0, $balance->amount);

        $this->callCommandAndOutput(
            'billing:create-payment',
            [
                'balance'   => $balance->getKey(),
                'amount'    => $amount,
                '--gateway' => self::GATEWAY,
            ],
        );

        /** @var Operation|null $operation */
        $operation = $balance->operation()->get()?->first();

        $this->assertInstanceOf(Operation::class, $operation);
        $this->assertEquals('pending', $operation->state->value);
        $this->assertNotEmpty($operation->gateway_payment_id);

        do {
            self::readline('Please follow the link and make a payment, then Enter to continue.');

            $this->callCommandAndOutput(
                'billing:get-payment-information',
                [
                    'operation' => $operation->operation_uuid,
                ],
            );

            $this->callCommandAndOutput(
                'billing:check-omnipay-payment-state',
                [
                    'transaction' => $operation->gateway_payment_id,
                    '--gateway'   => self::GATEWAY,
                ],
            );

            $operation->refresh();

            $this->assertNotEquals('succeeded', $operation->state->value);

        } while (! $operation->state->isPaid());

        $this->callCommandAndOutput(
            'billing:capture-omnipay--payment',
            [
                'transaction' => $operation->gateway_payment_id,
                '--gateway'   => self::GATEWAY,
            ],
        );

        $operation->refresh();

        $this->assertEquals('succeeded', $operation->state->value);
    }
}
