<?php

namespace Arhitov\LaravelBilling\Enums;

enum SubscriptionStateEnum: string
{
    case Pending  = 'pending';
    case Active   = 'active';
    case Inactive = 'inactive';
    case Locked   = 'locked';
    case Expiry   = 'expiry';

    public function isActive(): bool
    {
        return $this === SubscriptionStateEnum::Active;
    }
}
