<?php

namespace Arhitov\LaravelBilling;

use Arhitov\LaravelBilling\Contracts\SubscriptionSettingInterface;
use Arhitov\LaravelBilling\Enums\SubscriptionStateEnum;
use Arhitov\LaravelBilling\Models;
use ErrorException;
use Throwable;

class Subscription
{
    protected Models\Subscription $subscription;

    public function __construct(
        protected Models\Balance $balance,
        protected SubscriptionSettingInterface $subscriptionSetting,
        protected string $description,
    )
    {
        $this->subscription = $balance->owner->getSubscription($subscriptionSetting->getKey()) ??
            $balance->owner->makeSubscription(
                $subscriptionSetting->getKey(),
                $balance,
                $subscriptionSetting->getAmount(),
                $subscriptionSetting->getBeginningAt(),
                $subscriptionSetting->getExpiryAt(),
            );
    }

    /**
     * @return bool
     */
    public function create(): bool
    {
        try {
            $this->creatOrFail();
            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @return void
     * @throws Throwable
     */
    public function creatOrFail(): void
    {
        if (! $this->subscription->exists) {
            $this->subscription->saveOrFail();
        }
    }

    /**
     * @return bool
     */
    public function buy(): bool
    {
        try {
            $this->buyOrFail();
            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @return void
     * @throws Exceptions\BalanceException
     * @throws Exceptions\OperationException
     * @throws Exceptions\TransferUsageException
     * @throws Throwable
     * @throws ErrorException
     */
    public function buyOrFail(): void
    {
        $this->creatOrFail();
        $decrease = new Decrease(
            $this->balance,
            $this->subscription->amount,
            description: $this->description,
        );

        $operation = $decrease->getOperation();
        $operation->operation_identifier = 'subscription';
        $operation->operation_uuid = $this->subscription->uuid;

        $decrease->executeOrFail();

        $this->subscription
            ->setState(SubscriptionStateEnum::Active)
            ->saveOrFail();
    }
}
