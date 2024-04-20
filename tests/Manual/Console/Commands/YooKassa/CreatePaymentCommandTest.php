<?php

namespace Arhitov\LaravelBilling\Tests\Manual\Console\Commands\YooKassa;

use Arhitov\LaravelBilling\Tests\ConsoleCommandsTestCase;

class CreatePaymentCommandTest extends ConsoleCommandsTestCase
{
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

        $this
            ->artisan('billing:create-payment', [
                'balance'   => $balance->getKey(),
                'amount'    => $amount,
                '--gateway' => 'yookassa',
            ])
            ->expectsOutputToContain('Created payment: ')
            ->expectsOutputToContain('Payment operation_uuid: ')
            ->expectsOutputToContain('Please, goto link for payment: ')
            ->assertSuccessful()
            ->assertOk();

        $this->assertEquals(0, $owner->getBalanceOrFail()->amount);
    }
}
