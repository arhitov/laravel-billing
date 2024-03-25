<?php

namespace Arhitov\LaravelBilling\Enums;

enum BalanceStateEnum: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Locked = 'locked';

    public function isAllowIncrease(): bool
    {
        return $this === BalanceStateEnum::Active;
    }

    public function isAllowDecrease(): bool
    {
        return $this === BalanceStateEnum::Active;
    }
}
