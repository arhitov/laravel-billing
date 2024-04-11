<?php

namespace Arhitov\LaravelBilling\Events;

use Arhitov\LaravelBilling\Contracts\Balance\BalanceChangedEventInterface;

class BalanceChangedEvent extends BalanceEvent implements BalanceChangedEventInterface
{
}
