<?php

namespace Arhitov\LaravelBilling\Models;

use Arhitov\LaravelBilling\Contracts\SubscriptionSettingInterface;
use Arhitov\LaravelBilling\Exceptions\SubscriptionSettingExpiryException;
use Carbon\Carbon;

abstract class AbstractSubscriptionSetting implements SubscriptionSettingInterface
{
    protected Carbon $beginning_at;
    protected Carbon $expiry_at;

    /**
     * @param string $key
     * @param float $amount
     * @param Carbon|string $expiry_at
     * @param Carbon|string $beginning_at
     * @throws SubscriptionSettingExpiryException
     */
    public function __construct(
        protected string $key,
        protected float $amount,
        Carbon|string $expiry_at,
        Carbon|string $beginning_at = 'now',
    )
    {
        $this->beginning_at = is_string($beginning_at) ? Carbon::parse($beginning_at) : $beginning_at;
        $this->expiry_at = is_string($expiry_at) ? Carbon::parse($expiry_at) : $expiry_at;

        if ($this->beginning_at >= $this->expiry_at) {
            throw new SubscriptionSettingExpiryException($this);
        }
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getBeginningAt(): Carbon
    {
        return $this->beginning_at;
    }

    public function getExpiryAt(): Carbon
    {
        return $this->expiry_at;
    }
}
