<?php

namespace Arhitov\LaravelBilling\Listeners;

use Arhitov\LaravelBilling\Events\BalanceChangedEvent;

class BalanceChangedListener
{
    public function handle(BalanceChangedEvent $event)
    {
        $event->balance->deleteFromCache();
    }
}
