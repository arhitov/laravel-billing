<?php

namespace Arhitov\LaravelBilling\Events;

use Arhitov\LaravelBilling\Models\Subscription;

class SubscriptionEvent
{
    public function __construct(
        public Subscription $subscription,
    )
    {
    }
}
