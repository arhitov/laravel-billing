<?php

namespace Arhitov\LaravelBilling\Providers;

use Arhitov\LaravelBilling\Contracts\Balance\BalanceChangedEventInterface;
use Arhitov\LaravelBilling\Listeners\BalanceChangedListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        BalanceChangedEventInterface::class => [
            BalanceChangedListener::class,
        ],
    ];
}
