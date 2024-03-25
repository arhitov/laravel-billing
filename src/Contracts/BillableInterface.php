<?php

namespace Arhitov\LaravelBilling\Contracts;

use Arhitov\LaravelBilling\Enums\CurrencyEnum;
use Arhitov\LaravelBilling\Models\Balance;
use Illuminate\Database\Eloquent\Relations\MorphMany;

interface BillableInterface
{
    public static function bootDeleteCascadeBalance(): void
    ;
    public function balance(): MorphMany;

    public function getBalance(string $key = 'main'): Balance;

    public function getBalanceOrNull(string $key = 'main'): ?Balance;

    public function hasBalance(string $key = 'main'): bool;

    public function createBalance(CurrencyEnum $currency = null, string $key = 'main'): Balance;
}
