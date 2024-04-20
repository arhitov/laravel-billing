<?php

namespace Arhitov\LaravelBilling\Tests\Manual\Console\Commands\Dummy;

use Arhitov\LaravelBilling\Models\Operation;
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
        $gateway = 'dummy';

        $this->assertEquals(0, $balance->amount);
        $this->assertEquals(0, Operation::all()->count());

        $cardData = $this->getDataValidCard('omnipay_dummy_success');

        $this
            ->artisan('billing:create-payment', [
                'balance'   => $balance->getKey(),
                'amount'    => $amount,
                '--gateway' => $gateway,
            ])
            ->expectsQuestion('Please input card number?', $cardData['number'])
            ->expectsQuestion('Please input card expiry?', $cardData['expiryMonth'] . '/' . $cardData['expiryYear'])
            ->expectsQuestion('Please input card cvv?', $cardData['cvv'])
            ->expectsOutputToContain('Created payment: ')
            ->expectsOutputToContain('Payment operation_uuid: ')
            ->expectsOutputToContain('Payment successful')
            ->assertSuccessful()
            ->assertOk();

        $this->assertEquals($amount, $owner->getBalanceOrFail()->amount);

        /** @var Operation|null $operation */
        $operation = Operation::get()?->first();
        $this->assertInstanceOf(Operation::class, $operation);
        $this->assertEquals($gateway, $operation->gateway);
        $this->assertEquals($amount, $operation->amount);
        $this->assertEquals($balance->getKey(), $operation->recipient_balance_id);
        $this->assertEquals('succeeded', $operation->state->value);
    }
}
