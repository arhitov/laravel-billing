<?php

namespace Arhitov\LaravelBilling\Exceptions\Gateway;

class GatewayNotSupportMethodException extends GatewayException
{
    public function __construct(string $gateway, public string $method, string $msg = '')
    {
        parent::__construct($gateway, $msg);
    }
}
