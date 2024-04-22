<?php

namespace Arhitov\LaravelBilling\Tests\Manual\Console\Commands\YooKassa;

use Arhitov\LaravelBilling\Models\Operation;
use Arhitov\LaravelBilling\Tests\ConsoleCommandsTestCase;

class CheckOmnipayPaymentStateCommandTest extends ConsoleCommandsTestCase
{
    const GATEWAY = 'yookassa';

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

        } while ($operation->state->value !== 'succeeded');

        $this->assertEquals('succeeded', $operation->gateway_payment_state);
    }
}
