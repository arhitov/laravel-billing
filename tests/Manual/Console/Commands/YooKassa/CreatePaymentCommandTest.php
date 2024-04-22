<?php

namespace Arhitov\LaravelBilling\Tests\Manual\Console\Commands\YooKassa;

use Arhitov\LaravelBilling\Models\Operation;
use Arhitov\LaravelBilling\Tests\ConsoleCommandsTestCase;

class CreatePaymentCommandTest extends ConsoleCommandsTestCase
{
    const GATEWAY = 'yookassa';

    /**
     * @return void
     * @throws \Arhitov\LaravelBilling\Exceptions\BalanceNotFoundException
     */
    public function testCommand()
    {
        $owner = $this->createOwner();
        $balance = $owner->getBalanceOrCreate();
        $amount = '123.45';

        $this->assertEquals(0, $balance->amount);
        $this->assertEquals(0, $balance->operation()->count());

        $this
            ->artisan('billing:create-payment', [
                'balance'   => $balance->getKey(),
                'amount'    => $amount,
                '--gateway' => self::GATEWAY,
            ])
            ->expectsOutputToContain('Created payment: ')
            ->expectsOutputToContain('Payment operation_uuid: ')
            ->expectsOutputToContain('Please, goto link for payment: ')
            ->assertSuccessful()
            ->assertOk();

        $this->assertEquals(0, $owner->getBalanceOrFail()->amount);

        /** @var Operation|null $operation */
        $operation = $balance->operation()->first();

        $this->assertInstanceOf(Operation::class, $operation);
        $this->assertEquals(self::GATEWAY, $operation->gateway);
        $this->assertEquals($amount, $operation->amount);
        $this->assertEquals($balance->getKey(), $operation->recipient_balance_id);
        $this->assertEquals('pending', $operation->state->value);
    }
}
