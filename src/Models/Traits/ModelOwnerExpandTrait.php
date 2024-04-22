<?php

namespace Arhitov\LaravelBilling\Models\Traits;

use Arhitov\LaravelBilling\Enums\CurrencyEnum;
use Arhitov\LaravelBilling\Enums\OperationStateEnum;
use Arhitov\LaravelBilling\Enums\SubscriptionStateEnum;
use Arhitov\LaravelBilling\Events\BalanceCreatedEvent;
use Arhitov\LaravelBilling\Events\SubscriptionCreatedEvent;
use Arhitov\LaravelBilling\Exceptions\BalanceNotFoundException;
use Arhitov\LaravelBilling\Exceptions\Common\AmountException;
use Arhitov\LaravelBilling\Exceptions\SubscriptionNotFoundException;
use Arhitov\LaravelBilling\Helpers\DatabaseHelper;
use Arhitov\LaravelBilling\Increase;
use Arhitov\LaravelBilling\Models\Balance;
use Arhitov\LaravelBilling\Models\Operation;
use Arhitov\LaravelBilling\Models\CreditCard;
use Arhitov\LaravelBilling\Models\Payment;
use Arhitov\LaravelBilling\Models\Subscription;
use Arhitov\LaravelBilling\OmnipayGateway;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Omnipay\Omnipay;
use \Omnipay\Common\CreditCard as OmnipayCreditCard;

/**
 * @property int $id
 */
trait ModelOwnerExpandTrait
{
    public static function bootDeleteCascadeBalance(): void
    {
        if (config('billing.database.delete_cascade')) {
            static::deleting(function($owner) {
                $owner->balance()->delete();
            });
        }
    }

    /**
     * @return array
     */
    final public function getOwnerIdentifier(): array
    {
        return [
            'owner_type' => static::class,
            'owner_id' => $this->getKey(),
        ];
    }

    /**
     * ***************
     * *** Balance ***
     * ***************
     */

    /**
     * @return MorphMany<Balance>
     */
    final public function balance(): MorphMany
    {
        return $this->morphMany(Balance::class, 'owner');
    }

    /**
     * @param string $key
     * @return Balance|null
     */
    final public function getBalance(string $key = 'main'): ?Balance
    {
        /** @var Balance|null $model */
        $model = $this->balance()->where('key', '=', $key)->first();
        return $model;
    }

    /**
     * @param string $key
     * @return Balance
     * @throws BalanceNotFoundException
     */
    final public function getBalanceOrFail(string $key = 'main'): Balance
    {
        $model = $this->getBalance($key);
        if (is_null($model)) {
            throw new BalanceNotFoundException($key);
        }
        return $model;
    }

    /**
     * @param string $key
     * @return Balance
     */
    final public function getBalanceOrCreate(string $key = 'main'): Balance
    {
        return $this->getBalance($key) ?? $this->createBalance(key: $key);
    }

    /**
     * @param string $key
     * @return bool
     */
    final public function hasBalance(string $key = 'main'): bool
    {
        return $this->balance()->where('key', '=', $key)->exists();
    }

    /**
     * @param CurrencyEnum|null $currency
     * @param string $key
     * @return Balance
     */
    final public function createBalance(CurrencyEnum $currency = null, string $key = 'main'): Balance
    {
        /** @var Balance $balance */
        $balance = $this->balance()->create([
            'key'      => $key,
            'amount'   => 0,
            'currency' => $currency ?? CurrencyEnum::from(config('billing.currency')),
        ]);
        // Method boot::created in model Balance doesn't start in this case. Call manually.
        event(new BalanceCreatedEvent($balance));
        return $balance;
    }

    /**
     * @param string $key
     * @return Balance|null
     */
    final public function getCacheBalance(string $key = 'main'): ?Balance
    {
        $balance = Balance::getFromCache($this, $key);
        if (is_null($balance)) {
            $balance = $this->getBalance($key);
            if (is_null($balance)) {
                return null;
            }
            $balance->putInCache();
        }

        return $balance;
    }

    /**
     * *****************
     * *** Operation ***
     * *****************
     */

    /**
     * @return Builder
     */
    final public function builderOperation(): Builder
    {
        $balanceIdList = $this->balance()->pluck('id')->toArray();

        return Operation::query()->where(static function(Builder $queryBuilder) use ($balanceIdList) {
            $queryBuilder
                ->orWhereIn('sender_balance_id', $balanceIdList)
                ->orWhereIn('recipient_balance_id', $balanceIdList);
        });
    }

    /**
     * ******************
     * *** CreditCard ***
     * ******************
     */

    /**
     * @return Collection<CreditCard>
     */
    final public function listCreditCard(): Collection
    {
        $balanceIdList = $this->balance()->pluck('id')->toArray();
        if (empty($balanceIdList)) {
            return new Collection();
        }

        return CreditCard::query()->whereIn('owner_balance_id', $balanceIdList)->get();
    }

    /**
     * ********************
     * *** Subscription ***
     * ********************
     */

    /**
     * @return MorphMany<Subscription>
     */
    final public function subscription(): MorphMany
    {
        return $this->morphMany(Subscription::class, 'owner');
    }

    /**
     * @param string $key
     * @return \Arhitov\LaravelBilling\Models\Subscription|null
     */
    final public function getSubscription(string $key): ?Subscription
    {
        /** @var Subscription|null $model */
        $model = $this->subscription()->where('key', '=', $key)->first();
        return $model;
    }

    /**
     * @param string $key
     * @return \Arhitov\LaravelBilling\Models\Subscription
     * @throws \Arhitov\LaravelBilling\Exceptions\SubscriptionNotFoundException
     */
    final public function getSubscriptionOrFail(string $key = 'main'): Subscription
    {
        $model = $this->getSubscription($key);
        if (is_null($model)) {
            throw new SubscriptionNotFoundException($key);
        }
        return $model;
    }

    /**
     * @param string $key
     * @return \Arhitov\LaravelBilling\Models\Subscription
     * @throws \Throwable
     */
    final public function getSubscriptionOrCreate(string $key = 'main'): Subscription
    {
        return $this->getSubscription($key) ?? $this->createSubscription(key: $key);
    }

    /**
     * @param string $key
     * @return bool
     */
    final public function hasSubscription(string $key): bool
    {
        return $this->subscription()
                    ->where('key', '=', $key)
                    ->exists();
    }

    /**
     * @param string $key
     * @return bool
     */
    final public function hasSubscriptionActive(string $key): bool
    {
        return $this->builderSubscriptionActive()
                    ->where('key', '=', $key)
                    ->exists();
    }

    /**
     * @return Collection
     */
    final public function listSubscriptionActive(): Collection
    {
        return $this->builderSubscriptionActive()->get();
    }

    /**
     * @return Builder
     */
    final public function builderSubscriptionActive(): Builder
    {
        return $this->subscription()
                    ->where('state', '=', SubscriptionStateEnum::Active->value)
                    ->getQuery();
    }

    /**
     * @param string $key
     * @param Balance|null $balance
     * @param float|null $amount
     * @param Carbon|null $beginning_at
     * @param Carbon|null $expiry_at
     * @param string|null $uuid
     * @param string|null $key_extend
     * @return Subscription
     */
    final public function makeSubscription(
        string  $key,
        Balance $balance = null,
        float   $amount = null,
        Carbon  $beginning_at = null,
        Carbon  $expiry_at = null,
        string  $uuid = null,
        string  $key_extend = null,
    ): Subscription {
        /** @var Subscription $subscription */
        $subscription = $this->subscription()->make([
            'uuid'         => $uuid ?? Str::orderedUuid()->toString(),
            'key'          => $key,
            'key_extend'   => $key_extend,
            'amount'       => $amount,
            'beginning_at' => $beginning_at,
            'expiry_at'    => $expiry_at,
        ]);
        if ($balance) {
            $subscription->setBalance($balance);
        }
        return $subscription;
    }

    /**
     * @param string $key
     * @param Balance|null $balance
     * @param float|null $amount
     * @param Carbon|null $beginning_at
     * @param Carbon|null $expiry_at
     * @param string|null $uuid
     * @param string|null $key_extend
     * @return Subscription
     * @throws \Throwable
     */
    final public function createSubscription(
        string  $key,
        Balance $balance = null,
        float   $amount = null,
        Carbon  $beginning_at = null,
        Carbon  $expiry_at = null,
        string  $uuid = null,
        string  $key_extend = null,
    ): Subscription {
        $subscription = $this->makeSubscription($key, $balance, $amount, $beginning_at, $expiry_at, $uuid, $key_extend);
        $subscription->saveOrFail();
        // Method boot::created in model Balance doesn't start in this case. Call manually.
        event(new SubscriptionCreatedEvent($subscription));
        return $subscription;
    }

    /**
     * ***************
     * *** Payment ***
     * ***************
     */

    /**
     * Create payment
     *
     * @param float $amount
     * @param string $description
     * @param Balance|string|null $balance
     * @param string|null $gatewayName
     * @param OmnipayCreditCard|array|null $card
     * @return Payment
     * @throws \Arhitov\LaravelBilling\Exceptions\BalanceException
     * @throws \Arhitov\LaravelBilling\Exceptions\BalanceNotFoundException
     * @throws \Arhitov\LaravelBilling\Exceptions\Common\AmountException
     * @throws \Arhitov\LaravelBilling\Exceptions\Gateway\GatewayNotFoundException
     * @throws \Arhitov\LaravelBilling\Exceptions\Gateway\GatewayNotSpecifiedException
     * @throws \Arhitov\LaravelBilling\Exceptions\OperationAlreadyCreatedException
     * @throws \Arhitov\LaravelBilling\Exceptions\OperationException
     * @throws \Arhitov\LaravelBilling\Exceptions\TransferUsageException
     * @throws \Throwable
     */
    final public function createPayment(
        float                   $amount,
        string                  $description,
        Balance|string          $balance = null,
        string                  $gatewayName = null,
        OmnipayCreditCard|array $card = null,
    ): Payment {
        if (0 > $amount || $amount > INF) {
            throw new AmountException($amount);
        }

        $balance = match (true) {
            is_null($balance)   => $this->getBalanceOrFail(),
            is_string($balance) => $this->getBalanceOrFail($balance),
            default             => $balance,
        };

        $omnipayGateway = new OmnipayGateway($gatewayName);

        // Creating a balance increase record
        $operationUuid = Str::orderedUuid()->toString();
        $increase = new Increase(
            $balance,
            $amount,
            gateway: $omnipayGateway->getGatewayName(),
            description: $description,
            operation_identifier: 'payment',
            operation_uuid: $operationUuid,
        );
        $increase->createOrFail();
        $operation = $increase->getOperation();

        $response = $omnipayGateway->getGateway()->purchase(array_filter([
            'amount'        => $amount,
            'currency'      => $balance->currency->value,
            'returnUrl'     => $omnipayGateway->getReturnUrl(['operation_uuid' => $operationUuid]),
            'transactionId' => $operation->operation_uuid,
            'description'   => $operation->description,
            'capture'       => $omnipayGateway->getCapture(),
            'card'          => $card,
        ], fn($value) => ! is_null($value)))->send();

        $operation->gateway_payment_id = $response->getTransactionReference();
        $operation->gateway_payment_state = method_exists($response, 'getState') ? $response->getState() : null;
        $operation->state = OperationStateEnum::Pending;
        $operation->saveOrFail();

        if ($response->isSuccessful()) {
            DatabaseHelper::transaction(function() use (&$increase) {
                $increase->executeOrFail();
            });
        }

        return new Payment(
            $increase,
            $response
        );
    }
}
