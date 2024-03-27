<?php

namespace Arhitov\LaravelBilling\Providers;

use Arhitov\LaravelBilling\Events\BalanceChangedEvent;
use Arhitov\LaravelBilling\Listeners\BalanceChangedListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        BalanceChangedEvent::class => [
            BalanceChangedListener::class,
        ]
    ];
}
