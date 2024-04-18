<?php

namespace Arhitov\LaravelBilling\Exceptions\Common;

use Arhitov\LaravelBilling\Exceptions\LaravelBillingException;

class AmountException extends LaravelBillingException
{
    /**
     * Create a new exception instance.
     *
     * @param int|float $amount
     * @param string $msg
     */
    public function __construct(
        public int|float $amount,
        string $msg = '',
    )
    {
        parent::__construct($msg);
    }
}
