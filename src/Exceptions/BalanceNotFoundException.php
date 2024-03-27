<?php

namespace Arhitov\LaravelBilling\Exceptions;

use Arhitov\LaravelBilling\Models\Balance;

class BalanceNotFoundException extends BalanceException
{
    public function __construct(string $key)
    {
        parent::__construct(new Balance(), "Balance \"{$key}\" not fount");
    }
}
