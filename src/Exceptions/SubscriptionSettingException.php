<?php

namespace Arhitov\LaravelBilling\Exceptions;

use Arhitov\LaravelBilling\Models\AbstractSubscriptionSetting;

class SubscriptionSettingException extends LaravelBillingException
{
    /**
     * Create a new exception instance.
     *
     * @param AbstractSubscriptionSetting $subscriptionSetting
     * @param string|null $msg
     */
    public function __construct(public AbstractSubscriptionSetting $subscriptionSetting, string $msg = null)
    {
        parent::__construct($msg ?? '');
    }
}
