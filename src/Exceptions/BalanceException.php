<?php

namespace Arhitov\LaravelBilling\Exceptions;

use Arhitov\LaravelBilling\Models\Balance;

class BalanceException extends LaravelBillingException
{
    /**
     * Create a new exception instance.
     *
     * @param Balance $balance
     * @param string|null $msg
     */
    public function __construct(public Balance $balance, string $msg = null)
    {
        parent::__construct($msg ?? '');
    }
}
