<?php

namespace Arhitov\LaravelBilling\Exceptions;

use Arhitov\LaravelBilling\Models\Operation;

class OperationException extends LaravelBillingException
{
    /**
     * Create a new exception instance.
     *
     * @param Operation $operation
     * @param string|null $msg
     */
    public function __construct(public Operation $operation, string $msg = null)
    {
        parent::__construct($msg ?? '');
    }
}
