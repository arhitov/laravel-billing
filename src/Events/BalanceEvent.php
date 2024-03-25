<?php

namespace Arhitov\LaravelBilling\Events;

use Arhitov\LaravelBilling\Models\Balance;

abstract class BalanceEvent
{
    public function __construct(
        public Balance $balance,
    )
    {
    }
}
