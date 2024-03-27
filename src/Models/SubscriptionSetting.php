<?php

namespace Arhitov\LaravelBilling\Models;

class SubscriptionSetting extends AbstractSubscriptionSetting
{
    public function isAllowAppend(): bool
    {
        return true;
    }
}
