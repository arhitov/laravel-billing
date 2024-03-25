<?php

namespace Arhitov\LaravelBilling\Exceptions;

use Arhitov\LaravelBilling\Transfer;
use Exception;

class TransferException extends Exception
{
    /**
     * Create a new exception instance.
     *
     * @param Transfer $transfer
     * @param string|null $msg
     */
    public function __construct(public Transfer $transfer, string $msg = null)
    {
        parent::__construct($msg ?? '');
    }
}
