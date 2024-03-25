<?php

namespace Arhitov\LaravelBilling;

use Arhitov\LaravelBilling\Contracts\BillableInterface;
use Arhitov\LaravelBilling\Models\Balance;

class RootBalance
{
    private array $setting;

    public function __construct(array $setting = null)
    {
        $this->setting = $setting ?? config('billing.root_balance');
    }

    public function getOwner(): BillableInterface
    {
        $classOwner = $this->setting['owner_type'];
        /** @var BillableInterface $owner */
        $owner = $classOwner::findOrFail($this->setting['owner_id']);
        return $owner;
    }

    public function getBalance(): Balance
    {
        return $this->getOwner()->getBalance();
    }
}
