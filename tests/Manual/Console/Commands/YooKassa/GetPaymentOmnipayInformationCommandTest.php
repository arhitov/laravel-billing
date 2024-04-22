<?php

namespace Arhitov\LaravelBilling\Tests\Manual\Console\Commands\YooKassa;

use Arhitov\LaravelBilling\Tests\ConsoleCommandsTestCase;

class GetPaymentOmnipayInformationCommandTest extends ConsoleCommandsTestCase
{
    const GATEWAY = 'yookassa';

    /**
     * @return void
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

        $transaction = $balance->operation()->firstOrFail()->gateway_payment_id;
        $this->assertNotEmpty($transaction);

        $this
            ->artisan('billing:get-payment-omnipay-information', [
                'transaction' => $transaction,
                '--gateway'   => self::GATEWAY,
            ])
            ->expectsOutputToContain('TransactionReference: ')
            ->expectsOutputToContain('TransactionId: ')
            ->expectsOutputToContain('Paid: ')
            ->expectsOutputToContain('Amount: ')
            ->expectsOutputToContain('State payment: ')
            ->expectsOutputToContain('State operation: ')
//            ->expectsOutputToContain('Payer: ')
//            ->expectsOutputToContain('Payment date: ')
            ->assertSuccessful()
            ->assertOk();
    }

    /**
     * @return void
     */
    public function testCommandNotFound()
    {
        $this
            ->artisan('billing:get-payment-omnipay-information', [
                'transaction' => '11111111-2222-3333-4444-555555555555',
                '--gateway'   => self::GATEWAY,
            ])
            ->expectsOutputToContain('Payment doesn\'t exist or access denied')
            ->assertFailed();
    }
}
