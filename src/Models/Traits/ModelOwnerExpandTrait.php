<?php

namespace Arhitov\LaravelBilling\Models\Traits;

use Arhitov\LaravelBilling\Enums\CurrencyEnum;
use Arhitov\LaravelBilling\Enums\SubscriptionStateEnum;
use Arhitov\LaravelBilling\Events\BalanceCreatedEvent;
use Arhitov\LaravelBilling\Events\SubscriptionCreatedEvent;
use Arhitov\LaravelBilling\Models\Balance;
use Arhitov\LaravelBilling\Models\SavedPayment;
use Arhitov\LaravelBilling\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

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

    public function getBalance(string $key = 'main'): Balance
    {
        return $this->getBalanceOrNull() ?? $this->createBalance(key: $key);
    }

    public function getBalanceOrNull(string $key = 'main'): ?Balance
    {
        /** @var Balance|null $balance */
        $balance = $this->balance()->where('key', '=', $key)->first();
        return $balance;
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

    /**
     * ********************
     * *** SavedPayment ***
     * ********************
     */

    /**
     * @return Collection<SavedPayment>
     */
    public function getPaymentMethodList(): Collection
    {
        $balanceIdList = $this->balance()->pluck('id')->toArray();
        if (empty($balanceIdList)) {
            return new Collection();
        }

        return SavedPayment::query()->whereIn('owner_balance_id', $balanceIdList)->get();
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

    public function getSubscription(string $key): Subscription
    {
        return $this->getSubscriptionOrNull($key) ?? $this->createSubscription(key: $key);
    }

    public function getSubscriptionOrNull(string $key): ?Subscription
    {
        /** @var Subscription|null $subscription */
        $subscription = $this->subscription()->where('key', '=', $key)->first();
        return $subscription;
    }

    public function hasSubscription(string $key): bool
    {
        return $this->subscription()
            ->where('key', '=', $key)
            ->exists();
    }

    public function hasSubscriptionActive(string $key): bool
    {
        return $this->subscription()
            ->where('key', '=', $key)
            ->where('state', '=', SubscriptionStateEnum::Active->value)
            ->exists();
    }

    public function makeSubscription(
        string $key,
        Balance $balance = null,
        float $amount = null,
        Carbon $beginning_at = null,
        Carbon $expiry_at = null,
    ): Subscription
    {
        /** @var Subscription $subscription */
        $subscription = $this->subscription()->make([
            'key' => $key,
            'amount' => $amount,
            'beginning_at' => $beginning_at,
            'expiry_at' => $expiry_at,
        ]);
        if ($balance) {
            $subscription->setBalance($balance);
        };
        return $subscription;
    }

    public function createSubscription(
        string $key,
        Balance $balance = null,
        float $amount = null,
        Carbon $beginning_at = null,
        Carbon $expiry_at = null,
    ): Subscription
    {
        $subscription = $this->makeSubscription($key, $balance, $amount, $beginning_at, $expiry_at);
        $subscription->saveOrFail();
        // Method boot::created in model Balance doesn't start in this case. Call manually.
        event(new SubscriptionCreatedEvent($subscription));
        return $subscription;
    }
}
