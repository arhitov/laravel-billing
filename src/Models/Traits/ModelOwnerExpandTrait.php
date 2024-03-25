<?php

namespace Arhitov\LaravelBilling\Models\Traits;

use Arhitov\LaravelBilling\Enums\CurrencyEnum;
use Arhitov\LaravelBilling\Events\BalanceCreatedEvent;
use Arhitov\LaravelBilling\Models\Balance;
use Illuminate\Database\Eloquent\Relations\MorphMany;

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
}
