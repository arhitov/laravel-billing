<?php

namespace Arhitov\LaravelBilling\Tests\Manual\Console\Commands\Dummy;

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

        $cardData = $this->getDataValidCard('omnipay_dummy_success');

        $this
            ->artisan('billing:create-payment', [
                'balance'   => $balance->getKey(),
                'amount'    => $amount,
                '--gateway' => 'dummy',
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
    }
}
