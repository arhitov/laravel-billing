<?php

namespace Arhitov\LaravelBilling\Contracts;

use Carbon\Carbon;

interface SubscriptionSettingInterface
{
    /**
     * Unique subscription key.
     * @return string
     */
    public function getKey(): string;
    /**
     * Subscription cost.
     * @return float
     */
    public function getAmount(): float;

    public function getBeginningAt(): Carbon;

    public function getExpiryAt(): Carbon;

    /**
     * Is renewal allowed for an existing subscription?
     * @return bool
     */
    public function isAllowAppend(): bool;
}
