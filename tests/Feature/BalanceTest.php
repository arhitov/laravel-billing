<?php

namespace Arhitov\LaravelBilling\Tests\Feature;

use Arhitov\LaravelBilling\Decrease;
use Arhitov\LaravelBilling\Enums\BalanceStateEnum;
use Arhitov\LaravelBilling\Enums\CurrencyEnum;
use Arhitov\LaravelBilling\Enums\OperationStateEnum;
use Arhitov\LaravelBilling\Exceptions\TransferUsageException;
use Arhitov\LaravelBilling\Increase;
use Arhitov\LaravelBilling\Models\Balance;
use Arhitov\LaravelBilling\Models\Operation;
use Arhitov\LaravelBilling\Tests\FeatureTestCase;
use Arhitov\LaravelBilling\Transfer;
use ErrorException;
use Illuminate\Support\Facades\Cache;

class BalanceTest extends FeatureTestCase
{
    public function testCreateBalance()
    {
        $owner = $this->createOwner();
        /** @var Balance $balance */
        $balance = $owner->balance()->create([
            'key' => 'test',
            'amount' => 123.23,
            'currency' => CurrencyEnum::RUB,
        ]);

        $this->assertTrue($balance->exists, 'No balance was created.');

        $this->assertEquals(
            Balance::class,
            get_class($balance),
            'Created balance is not ' . Balance::class,
        );
        $this->assertEquals(
            get_class($owner),
            get_class($balance->owner),
            'Created balance is not owner class',
        );
        $this->assertEquals(
            get_class($owner),
            get_class(Balance::find($balance->id)->owner),
            'Created balance is not owner class.',
        );

        $owner2 = $this->createOwner();
        $this->assertEquals(
            0,
            $owner2->balance()->count(),
            'For owner2 there is already a balance.',
        );
        $this->assertFalse($owner2->hasBalance('test'), 'For owner2 there is already a balance "test".');
        $balance2 = $owner2->getBalanceOrCreate('test');

        $this->assertInstanceOf(Balance::class, $balance2, 'Created balance is not ' . Balance::class);
        $this->assertTrue($owner2->hasBalance('test'), 'For owner2 there is not exist balance "test".');
        $this->assertEquals(
            'test',
            $balance2->key,
            'Key balance incorrect.',
        );
    }

    /**
     * @depends testCreateBalance
     * @return void
     */
    public function testGetBalance()
    {
        $balanceKey = 'test';
        $owner = $this->createOwner();
        /** @var Balance|null $balance */
        $balance = $owner->getBalance($balanceKey);

        $this->assertNull($balance, 'Balance shouldn\'t exist');

        $balance = $owner->getBalanceOrCreate($balanceKey);
        $this->assertInstanceOf(Balance::class, $balance, 'Created balance is not ' . Balance::class);
        $this->assertEquals($balanceKey, $balance->key, 'The "key" field has an incorrect value.');

        $balance = $owner->getBalance($balanceKey);
        $this->assertInstanceOf(Balance::class, $balance, 'Created balance is not ' . Balance::class);
    }

    /**
     * @depends testCreateBalance
     * @throws ErrorException
     */
    public function testChangeState()
    {
        $owner = $this->createOwner();
        $balance = $owner->getBalanceOrCreate();
        $this->assertNotNull($balance->state_active_at, 'Datetime "state_active_at" not set.');
        $this->assertNull($balance->state_locked_at, 'Datetime "state_locked_at" should not be installed yet.');

        $balance->setState('locked');
        $this->assertEquals(BalanceStateEnum::Locked, $balance->state);
        $balance->state = BalanceStateEnum::Active;
        $this->assertEquals(BalanceStateEnum::Active, $balance->state);

        $balance->setState(BalanceStateEnum::Locked);
        $this->assertEquals(BalanceStateEnum::Locked, $balance->state);
        $balance->state = BalanceStateEnum::Active;
        $this->assertEquals(BalanceStateEnum::Active, $balance->state);

        $balance->state = BalanceStateEnum::Locked;
        $balance->save();
        $this->assertEquals(BalanceStateEnum::Locked, $balance->state);
        $this->assertNotNull($balance->state_locked_at, 'Datetime "state_active_at" not set.');
    }

    /**
     * @depends testCreateBalance
     * @throws TransferUsageException
     */
    public function testIncreaseBalance()
    {
        $owner = $this->createOwner();
        $increase = new Increase(
            $owner->getBalanceOrCreate(),
            100,
        );
        $operation = $increase->getOperation();

        $this->assertEquals(0, $owner->getBalance()?->amount, 'The owner has an incorrect balance.');

        $this->assertTrue($increase->isAllow(), 'Not allow increase');
        $this->assertNull($operation->state_succeeded_at, 'Temporary meta "state_succeeded_at" is not null.');
        $this->assertEquals(OperationStateEnum::Created, $operation->state, 'Status operation incorrect.');
        $this->assertInstanceOf(Operation::class, $operation, 'Operation class is not ' . Operation::class);
        $this->assertTrue($increase->execute(), 'Operation increase failed');
        $this->assertNotNull($operation->state_succeeded_at, 'Temporary meta "state_succeeded_at" is null.');
        $this->assertEquals(OperationStateEnum::Succeeded, $operation->state, 'Status operation incorrect.');

        $this->assertEquals(100, $owner->getBalance()?->amount, 'The owner has an incorrect balance.');
    }

    /**
     * @depends testCreateBalance
     * @throws TransferUsageException
     */
    public function testIncreaseDescriptionBalance()
    {
        $descriptionTest = fake()->text(1000);
        $descriptionTest2 = fake()->text(1000);
        $this->assertNotEquals($descriptionTest, $descriptionTest2, 'Faker created identical texts.');

        $owner = $this->createOwner();
        $increase = new Increase(
            $owner->getBalanceOrCreate(),
            100,
            description: $descriptionTest
        );
        $operation = $increase->getOperation();

        $this->assertEquals($descriptionTest, $operation->description, 'The operation description contains an incorrect value.');

        $increase->setDescription($descriptionTest2);
        $this->assertNotEquals($descriptionTest, $operation->description, 'The operation description contains the old value.');
        $this->assertEquals($descriptionTest2, $operation->description, 'The operation description contains an incorrect value.');

    }

    /**
     * @11depends testIncreaseDescriptionBalance
     * @return void
     * @throws TransferUsageException
     */
    public function testGetBalanceCacheAmount()
    {
        $balanceAmount = 100.0;
        $balanceAmount2 = 200.0;
        $owner = $this->createOwner();

        $this->assertNull($owner->getBalance(), 'There must be no balance.');
        $this->assertEquals(0, $owner->getBalanceCacheAmount(), 'Balance must be empty.');

        $balance = $owner->getBalanceOrCreate();

        (new Increase(
            $balance,
            $balanceAmount,
        ))->execute();

        $balanceCacheKeySetting = $balance->getSettingCacheAmount();
        $this->assertIsArray($balanceCacheKeySetting);

        $this->assertEquals($balanceAmount, $owner->getBalanceCacheAmount(), 'Balance contains incorrect value.');
        $this->assertTrue(Cache::has($balanceCacheKeySetting['key']), 'Cache key not found.');
        $this->assertEquals($balanceAmount, Cache::get($balanceCacheKeySetting['key']), 'Balance in cache contains incorrect value.');

        Cache::put($balanceCacheKeySetting['key'], $balanceAmount2, $balanceCacheKeySetting['ttl']);

        $this->assertEquals($balanceAmount2, Cache::get($balanceCacheKeySetting['key']), 'Balance in cache contains incorrect value.');
        $this->assertEquals($balanceAmount2, $owner->getBalanceCacheAmount(), 'Balance contains incorrect value.');

        $billingConfigBefore = config('billing');

        $billingConfig = $billingConfigBefore;
        $billingConfig['cache']['keys']['owner_balance_amount'] = null;
        config(['billing' => $billingConfig]);

        $this->assertNull(config('billing.cache.keys.owner_balance_amount', '-1'), 'Fail change config.');
        $this->assertEquals($balanceAmount, $owner->getBalanceCacheAmount(), 'Balance uses cache.');

        $billingConfig['cache'] = null;
        config(['billing' => $billingConfig]);
        $this->assertArrayNotHasKey('owner_balance_amount', config('billing.cache.keys', []), 'Fail change config.');
        $this->assertEquals($balanceAmount, $owner->getBalanceCacheAmount(), 'Balance uses cache.');

        config(['billing' => $billingConfigBefore]);
        $this->assertEquals($billingConfigBefore, config('billing'), 'ATTENTION!!! Failed to return configuration state!');

        $this->assertEquals($balanceAmount2, $owner->getBalanceCacheAmount(), 'Balance contains incorrect value.');

        Cache::delete($balanceCacheKeySetting['key']);
        $this->assertFalse(Cache::has($balanceCacheKeySetting['key']), 'Cache key found.');

        $this->assertEquals($balanceAmount, $owner->getBalanceCacheAmount(), 'Balance contains incorrect value.');
    }

    /**
     * @depends testCreateBalance
     * @throws TransferUsageException
     */
    public function testDecreaseBalance()
    {
        $owner = $this->createOwner();
        $ownerBalance = $owner->getBalanceOrCreate();
        $ownerBalance->limit = null;

        $decrease = new Decrease(
            $ownerBalance,
            100,
        );
        $operation = $decrease->getOperation();

        $this->assertEquals(0, $owner->getBalance()?->amount, 'The owner has an incorrect balance.');

        $this->assertTrue($decrease->isAllow(), 'Not allow increase');
        $this->assertNull($operation->state_succeeded_at, 'Temporary meta "state_succeeded_at" is not null.');
        $this->assertEquals(OperationStateEnum::Created, $operation->state, 'Status operation incorrect.');
        $this->assertInstanceOf(Operation::class, $operation, 'Operation class is not ' . Operation::class);
        $this->assertTrue($decrease->execute(), 'Operation increase failed');
        $this->assertNotNull($operation->state_succeeded_at, 'Temporary meta "state_succeeded_at" is null.');
        $this->assertEquals(OperationStateEnum::Succeeded, $operation->state, 'Status operation incorrect.');

        $this->assertEquals(-100, $owner->getBalance()?->amount, 'The owner has an incorrect balance.');
    }

    /**
     * @depends testCreateBalance
     */
    public function testTransferBalance()
    {
        $senderOwner = $this->createOwner();
        $senderBalance = $senderOwner->getBalanceOrCreate();
        $senderBalance->limit = null;

        $recipientOwner = $this->createOwner();
        $recipientBalance = $recipientOwner->getBalanceOrCreate();
        $recipientBalance->limit = null;

        $decrease = new Transfer(
            $senderBalance,
            $recipientBalance,
            100,
        );
        $operation = $decrease->getOperation();

        $this->assertEquals(0, $senderOwner->getBalance()?->amount, 'The sender has an incorrect balance.');
        $this->assertEquals(0, $recipientOwner->getBalance()?->amount, 'The recipient has an incorrect balance.');

        $this->assertTrue($decrease->isAllow(), 'Not allow increase');
        $this->assertNull($operation->state_succeeded_at, 'Temporary meta "state_succeeded_at" is not null.');
        $this->assertEquals(OperationStateEnum::Created, $operation->state, 'Status operation incorrect.');
        $this->assertInstanceOf(Operation::class, $operation, 'Operation class is not ' . Operation::class);
        $this->assertTrue($decrease->execute(), 'Operation increase failed');
        $this->assertNotNull($operation->state_succeeded_at, 'Temporary meta "state_succeeded_at" is null.');
        $this->assertEquals(OperationStateEnum::Succeeded, $operation->state, 'Status operation incorrect.');

        $this->assertEquals(-100, $senderOwner->getBalance()?->amount, 'The sender has an incorrect balance.');
        $this->assertEquals(100, $recipientOwner->getBalance()?->amount, 'The recipient has an incorrect balance.');
    }
}
