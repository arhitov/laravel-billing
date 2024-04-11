<?php

namespace Arhitov\LaravelBilling\Contracts;

use Arhitov\LaravelBilling\Enums\CurrencyEnum;
use Arhitov\LaravelBilling\Exceptions;
use Arhitov\LaravelBilling\Models\Balance;
use Arhitov\LaravelBilling\Models\CreditCard;
use Arhitov\LaravelBilling\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

interface BillableInterface
{
    public static function bootDeleteCascadeBalance(): void;

    /**
     * ***************
     * *** Balance ***
     * ***************
     */

    /**
     * @return MorphMany<Balance>
     */
    public function balance(): MorphMany;

    public function getBalance(string $key = 'main'): ?Balance;

    /**
     * @param string $key
     * @return Balance
     * @throws Exceptions\BalanceNotFoundException
     */
    public function getBalanceOrFail(string $key = 'main'): Balance;

    public function getBalanceOrCreate(string $key = 'main'): Balance;

    public function hasBalance(string $key = 'main'): bool;

    public function createBalance(CurrencyEnum $currency = null, string $key = 'main'): Balance;

    public function getCacheBalance(string $key = 'main'): ?Balance;

    /**
     * ********************
     * *** Operation ***
     * ********************
     */

    /**
     * @return Builder
     */
    public function builderOperation(): Builder;

    /**
     * ********************
     * *** CreditCard ***
     * ********************
     */

    /**
     * @return Collection<CreditCard>
     */
    public function listCreditCard(): Collection;

    /**
     * ********************
     * *** Subscription ***
     * ********************
     */

    /**
     * @return MorphMany<Subscription>
     */
    public function subscription(): MorphMany;

    public function getSubscription(string $key): ?Subscription;

    /**
     * @param string $key
     * @return Subscription
     * @throws Exceptions\SubscriptionNotFoundException
     */
    public function getSubscriptionOrFail(string $key): Subscription;

    public function getSubscriptionOrCreate(string $key): Subscription;

    public function hasSubscription(string $key): bool;

    public function hasSubscriptionActive(string $key): bool;

    public function listSubscriptionActive(): Collection;

    public function builderSubscriptionActive(): Builder;

    public function makeSubscription(
        string  $key,
        Balance $balance = null,
        float   $amount = null,
        Carbon  $beginning_at = null,
        Carbon  $expiry_at = null,
        string  $uuid = null,
    ): Subscription;

    public function createSubscription(
        string  $key,
        Balance $balance = null,
        float   $amount = null,
        Carbon  $beginning_at = null,
        Carbon  $expiry_at = null,
        string  $uuid = null,
    ): Subscription;
}
