<?php

namespace Arhitov\LaravelBilling\Contracts;

use Arhitov\LaravelBilling\Enums\CurrencyEnum;
use Arhitov\LaravelBilling\Models\Balance;
use Arhitov\LaravelBilling\Models\SavedPayment;
use Arhitov\LaravelBilling\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\MorphMany;
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

    public function getBalance(string $key = 'main'): Balance;

    public function getBalanceOrNull(string $key = 'main'): ?Balance;

    public function hasBalance(string $key = 'main'): bool;

    public function createBalance(CurrencyEnum $currency = null, string $key = 'main'): Balance;

    /**
     * ********************
     * *** SavedPayment ***
     * ********************
     */

    /**
     * @return Collection<SavedPayment>
     */
    public function getPaymentMethodList(): Collection;

    /**
     * ********************
     * *** Subscription ***
     * ********************
     */

    /**
     * @return MorphMany<Subscription>
     */
    public function subscription(): MorphMany;

    public function getSubscription(string $key): Subscription;

    public function getSubscriptionOrNull(string $key): ?Subscription;

    public function hasSubscription(string $key): bool;

    public function hasSubscriptionActive(string $key): bool;

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
