<?php

namespace Arhitov\LaravelBilling\Exceptions\Gateway;

class GatewayNotSpecifiedException extends GatewayException
{
    /**
     * Create a new exception instance.
     *
     * @param string $msg
     */
    public function __construct(
        string $msg = '',
    )
    {
        parent::__construct('(null)', $msg);
    }
}
