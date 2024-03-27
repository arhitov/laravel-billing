<?php

namespace Arhitov\LaravelBilling\Exceptions;

use Arhitov\LaravelBilling\Models\AbstractSubscriptionSetting;
use Exception;

class SubscriptionSettingException extends Exception
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
