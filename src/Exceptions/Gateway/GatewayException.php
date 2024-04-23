<?php

namespace Arhitov\LaravelBilling\Exceptions\Gateway;

use Arhitov\LaravelBilling\Exceptions\LaravelBillingException;
use Exception;

class GatewayException extends LaravelBillingException
{
    /**
     * Create a new exception instance.
     *
     * @param string $gateway
     * @param string $msg
     */
    public function __construct(
        public string $gateway,
        string $msg = '',
        public Exception|null $exception = null,
    )
    {
        parent::__construct($msg);
    }
}
