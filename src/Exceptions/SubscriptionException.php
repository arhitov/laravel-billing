<?php

namespace Arhitov\LaravelBilling\Exceptions;

use Arhitov\LaravelBilling\Models\Subscription;
use Exception;

class SubscriptionException extends Exception
{
    /**
     * Create a new exception instance.
     *
     * @param Subscription $subscription
     * @param string|null $msg
     */
    public function __construct(public Subscription $subscription, string $msg = null)
    {
        parent::__construct($msg ?? '');
    }
}
