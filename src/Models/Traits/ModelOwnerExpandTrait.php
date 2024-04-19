<?php

namespace Arhitov\LaravelBilling\Models\Traits;

use Arhitov\LaravelBilling\Enums\CurrencyEnum;
use Arhitov\LaravelBilling\Enums\SubscriptionStateEnum;
use Arhitov\LaravelBilling\Events\BalanceCreatedEvent;
use Arhitov\LaravelBilling\Events\SubscriptionCreatedEvent;
use Arhitov\LaravelBilling\Exceptions\BalanceNotFoundException;
use Arhitov\LaravelBilling\Exceptions\SubscriptionNotFoundException;
use Arhitov\LaravelBilling\Models\Balance;
use Arhitov\LaravelBilling\Models\Operation;
use Arhitov\LaravelBilling\Models\CreditCard;
use Arhitov\LaravelBilling\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * @property int $id
 */
trait ModelOwnerExpandTrait
{
    public static function bootDeleteCascadeBalance(): void
    {
        if (config('billing.database.delete_cascade')) {
            static::deleting(function ($owner) {
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
    public function balance(): MorphMany
    {
        return $this->morphMany(Balance::class, 'owner');
    }

    public function getBalance(string $key = 'main'): ?Balance
    {
        /** @var Balance|null $model */
        $model = $this->balance()->where('key', '=', $key)->first();
        return $model;
    }

    public function getBalanceOrFail(string $key = 'main'): Balance
    {
        $model = $this->getBalance($key);
        if (is_null($model)) {
            throw new BalanceNotFoundException($key);
        }
        return $model;
    }

    public function getBalanceOrCreate(string $key = 'main'): Balance
    {
        return $this->getBalance($key) ?? $this->createBalance(key: $key);
    }

    public function hasBalance(string $key = 'main'): bool
    {
        return $this->balance()->where('key', '=', $key)->exists();
    }

    public function createBalance(CurrencyEnum $currency = null, string $key = 'main'): Balance
    {
        /** @var Balance $balance */
        $balance = $this->balance()->create([
            'key' => $key,
            'amount' => 0,
            'currency' => $currency ?? CurrencyEnum::from(config('billing.currency')),
        ]);
        // Method boot::created in model Balance doesn't start in this case. Call manually.
        event(new BalanceCreatedEvent($balance));
        return $balance;
    }

    public function getCacheBalance(string $key = 'main'): ?Balance
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
     * ********************
     * *** Operation ***
     * ********************
     */

    /**
     * @return Builder
     */
    public function builderOperation(): Builder
    {
        $balanceIdList = $this->balance()->pluck('id')->toArray();

        return Operation::query()->where(static function (Builder $queryBuilder) use ($balanceIdList) {
            $queryBuilder
                ->orWhereIn('sender_balance_id', $balanceIdList)
                ->orWhereIn('recipient_balance_id', $balanceIdList);
        });
    }

    /**
     * ********************
     * *** CreditCard ***
     * ********************
     */

    /**
     * @return Collection<CreditCard>
     */
    public function listCreditCard(): Collection
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
    public function subscription(): MorphMany
    {
        return $this->morphMany(Subscription::class, 'owner');
    }

    public function getSubscription(string $key): ?Subscription
    {
        /** @var Subscription|null $model */
        $model = $this->subscription()->where('key', '=', $key)->first();
        return $model;
    }

    public function getSubscriptionOrFail(string $key = 'main'): Subscription
    {
        $model = $this->getSubscription($key);
        if (is_null($model)) {
            throw new SubscriptionNotFoundException($key);
        }
        return $model;
    }

    public function getSubscriptionOrCreate(string $key = 'main'): Subscription
    {
        return $this->getSubscription($key) ?? $this->createSubscription(key: $key);
    }

    public function hasSubscription(string $key): bool
    {
        return $this->subscription()
            ->where('key', '=', $key)
            ->exists();
    }

    public function hasSubscriptionActive(string $key): bool
    {
        return $this->builderSubscriptionActive()
                    ->where('key', '=', $key)
                    ->exists();
    }

    public function listSubscriptionActive(): Collection
    {
        return $this->builderSubscriptionActive()->get();
    }

    public function builderSubscriptionActive(): Builder
    {
        return $this->subscription()
                    ->where('state', '=', SubscriptionStateEnum::Active->value)
                    ->getQuery();
    }

    public function makeSubscription(
        string  $key,
        Balance $balance = null,
        float   $amount = null,
        Carbon  $beginning_at = null,
        Carbon  $expiry_at = null,
        string  $uuid = null,
        string  $key_extend = null,
    ): Subscription
    {
        /** @var Subscription $subscription */
        $subscription = $this->subscription()->make([
            'uuid' =>         $uuid ?? Str::orderedUuid()->toString(),
            'key' =>          $key,
            'key_extend' =>   $key_extend,
            'amount' =>       $amount,
            'beginning_at' => $beginning_at,
            'expiry_at' =>    $expiry_at,
        ]);
        if ($balance) {
            $subscription->setBalance($balance);
        };
        return $subscription;
    }

    public function createSubscription(
        string  $key,
        Balance $balance = null,
        float   $amount = null,
        Carbon  $beginning_at = null,
        Carbon  $expiry_at = null,
        string  $uuid = null,
        string  $key_extend = null,
    ): Subscription
    {
        $subscription = $this->makeSubscription($key, $balance, $amount, $beginning_at, $expiry_at, $uuid, $key_extend);
        $subscription->saveOrFail();
        // Method boot::created in model Balance doesn't start in this case. Call manually.
        event(new SubscriptionCreatedEvent($subscription));
        return $subscription;
    }
}
