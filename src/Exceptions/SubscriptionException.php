<?php

namespace Arhitov\LaravelBilling\Exceptions;

use Arhitov\LaravelBilling\Models\Subscription;

class SubscriptionException extends LaravelBillingException
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
