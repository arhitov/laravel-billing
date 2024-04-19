<?php

namespace Arhitov\LaravelBilling\Exceptions\Gateway;

use Arhitov\LaravelBilling\Exceptions\LaravelBillingException;

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
    )
    {
        parent::__construct($msg);
    }
}
