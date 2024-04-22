<?php

namespace Arhitov\LaravelBilling\Tests\Manual\Console\Commands\Dummy;

use Arhitov\LaravelBilling\Tests\ConsoleCommandsTestCase;

class GetPaymentOmnipayInformationCommandTest extends ConsoleCommandsTestCase
{
    const GATEWAY = 'dummy';

    public function testCommand()
    {
        $owner = $this->createOwner();
        $balance = $owner->getBalanceOrCreate();
        $amount = '123.45';

        $this->assertEquals(0, $balance->amount);
        $this->assertEquals(0, $balance->operation()->count());

        $cardData = $this->getDataValidCard('omnipay_dummy_success');

        $this
            ->artisan('billing:create-payment', [
                'balance'   => $balance->getKey(),
                'amount'    => $amount,
                '--gateway' => self::GATEWAY,
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

        $transaction = $balance->operation()->firstOrFail()->gateway_payment_id;
        $this->assertNotEmpty($transaction);

        $this
            ->artisan('billing:get-payment-omnipay-information', [
                'transaction' => $transaction,
                '--gateway'   => self::GATEWAY,
            ])
            ->expectsOutputToContain('This gateway cannot receive payment information.')
            ->assertFailed();

    }
}
