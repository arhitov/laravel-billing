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
}
