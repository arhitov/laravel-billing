<?php

namespace Arhitov\LaravelBilling\Tests\Manual\Console\Commands\YooKassa;

use Arhitov\LaravelBilling\Tests\ConsoleCommandsTestCase;

class GetPaymentInformationCommandTest extends ConsoleCommandsTestCase
{
    /**
     * @return void
     */
    public function testCommand()
    {
        $this
            ->artisan('billing:get-payment-information', [
                'transaction' => '2db5b0fd-000f-5000-a000-17f4199ec423',
                '--gateway'   => 'yookassa',
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
            ->artisan('billing:get-payment-information', [
                'transaction' => '11111111-2222-3333-4444-555555555555',
                '--gateway'   => 'yookassa',
            ])
            ->expectsOutputToContain('Payment doesn\'t exist or access denied')
            ->assertFailed();
    }
}
