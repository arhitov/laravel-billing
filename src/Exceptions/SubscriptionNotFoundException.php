<?php

namespace Arhitov\LaravelBilling\Exceptions;

use Arhitov\LaravelBilling\Models\Subscription;

class SubscriptionNotFoundException extends SubscriptionException
{
    public function __construct(string $key)
    {
        parent::__construct(new Subscription(), "Subscription \"{$key}\" not fount");
    }
}
