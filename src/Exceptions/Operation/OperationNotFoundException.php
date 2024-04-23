<?php

namespace Arhitov\LaravelBilling\Exceptions\Operation;

use Arhitov\LaravelBilling\Exceptions\OperationException;
use Arhitov\LaravelBilling\Models\Operation;

class OperationNotFoundException extends OperationException
{
    /**
     * Create a new exception instance.
     *
     * @param string|null $msg
     */
    public function __construct(string $msg = null)
    {
        parent::__construct(new Operation, $msg ?? '');
    }
}
