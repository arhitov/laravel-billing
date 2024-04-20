<?php

namespace Arhitov\LaravelBilling\Tests\Feature;

use Arhitov\LaravelBilling\Increase;
use Arhitov\LaravelBilling\Providers\PackageServiceProvider;
use Arhitov\LaravelBilling\Tests\FeatureTestCase;
use Illuminate\Support\Facades\Artisan;

class ConsoleCommandsTest extends FeatureTestCase
{
    /**
     * @return void
     */
    public function loadCommands(): void
    {
        (new PackageServiceProvider($this->app))->boot();
    }

    /**
     * @return void
     * @throws \Arhitov\LaravelBilling\Exceptions\BalanceNotFoundException
     */
    public function testGetBalanceCommand()
    {
        $this->loadCommands();

        $owner = $this->createOwner();

        // Balance has not yet been created
        $this->withoutMockingConsoleOutput()
             ->artisan('billing:get-balance', $owner->getOwnerIdentifier());
        $this->assertStringContainsString('Balance not found', Artisan::output());

        // Creating balance
        $owner->getBalanceOrCreate();

        // Test creating balance
        $owner->getBalanceOrFail();

        // Balance already created
        $this->withoutMockingConsoleOutput()
             ->artisan('billing:get-balance', $owner->getOwnerIdentifier());

        $currency = config('billing.currency');
        $this->assertStringContainsString("Balance: 0 {$currency}", Artisan::output());
    }

    /**
     * @depends testGetBalanceCommand
     * @return void
     */
    public function testIncreaseBalanceCommand()
    {
        $this->loadCommands();

        $owner = $this->createOwner();
        $amount = '123.12';

        // Creating balance
        $owner->getBalanceOrCreate();

        $this->withoutMockingConsoleOutput()
             ->artisan('billing:increase-balance', $owner->getOwnerIdentifier() + ['amount' => $amount]);
        $output = Artisan::output();

        $currency = config('billing.currency');
        $this->assertStringContainsString('Successful', $output);
        $this->assertStringContainsString("Balance before: 0 {$currency}", $output);
        $this->assertStringContainsString("Balance after: {$amount} {$currency}", $output);
    }

    /**
     * @depends testGetBalanceCommand
     * @return void
     * @throws \Arhitov\LaravelBilling\Exceptions\BalanceException
     * @throws \Arhitov\LaravelBilling\Exceptions\Common\AmountException
     * @throws \Arhitov\LaravelBilling\Exceptions\OperationException
     * @throws \Arhitov\LaravelBilling\Exceptions\TransferUsageException
     * @throws \Throwable
     */
    public function testDecreaseBalanceCommand()
    {
        $this->loadCommands();

        $owner = $this->createOwner();
        $amount = '123.12';

        // Creating balance
        $balance = $owner->getBalanceOrCreate();

        $this->assertEquals(0, $balance->amount);

        // Increase balance
        (new Increase(
            $balance,
            $amount,
        ))->executeOrFail();

        $this->assertEquals($amount, $balance->amount);

        $this->withoutMockingConsoleOutput()
             ->artisan('billing:decrease-balance', $owner->getOwnerIdentifier() + ['amount' => $amount]);
        $output = Artisan::output();

        $currency = config('billing.currency');
        $this->assertStringContainsString('Successful', $output);
        $this->assertStringContainsString("Balance before: {$amount} {$currency}", $output);
        $this->assertStringContainsString("Balance after: 0 {$currency}", $output);

        // Getting new balance
        $balance = $owner->getBalance();
        $this->assertEquals(0, $balance->amount);
    }

    /**
     * @return void
     * @throws \Arhitov\LaravelBilling\Exceptions\BalanceNotFoundException
     */
    public function testDummyCreatePaymentCommand()
    {
        $this->loadCommands();

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
            ->expectsOutputToContain('Payment successful')
            ->assertSuccessful()
            ->assertOk();

        $this->assertEquals($amount, $owner->getBalanceOrFail()->amount);
    }

    /**
     * @return void
     * @throws \Arhitov\LaravelBilling\Exceptions\BalanceNotFoundException
     */
    public function testYooKassaCreatePaymentCommand()
    {
        $this->loadCommands();

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
            ->expectsOutputToContain('Please, goto link for payment: ')
            ->assertSuccessful()
            ->assertOk();

        $this->assertEquals(0, $owner->getBalanceOrFail()->amount);
    }

    /**
     * @depends testYooKassaCreatePaymentCommand
     * @return void
     */
    public function testYooKassaGetPaymentInformationCommand()
    {
        $skipped = true;

        if ($skipped) {
            $this->markTestSkipped('There is no way to check automatically, because... need TransactionReference.');
        } else {
            $this->loadCommands();

            $this
                ->artisan('billing:get-payment-information', [
                    'transaction' => '2db4e646-000f-5000-8000-1db3875eeabe',
                    '--gateway'   => 'yookassa',
                ])
                ->expectsOutputToContain('TransactionReference: ')
                ->expectsOutputToContain('TransactionId: ')
                ->expectsOutputToContain('Paid: ')
                ->expectsOutputToContain('Amount: ')
                ->expectsOutputToContain('State: ')
                ->expectsOutputToContain('Payer: ')
                ->expectsOutputToContain('Payment date: ')
                ->assertSuccessful()
                ->assertOk();
        }
    }
}
