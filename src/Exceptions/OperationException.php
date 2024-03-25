<?php

namespace Arhitov\LaravelBilling\Exceptions;

use Arhitov\LaravelBilling\Models\Operation;
use Exception;

class OperationException extends Exception
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
