<?php

namespace Arhitov\LaravelBilling\Helpers;

use Closure;
use Illuminate\Support\Facades\DB;

class DatabaseHelper
{
    /**
     * @throws \Throwable
     */
    public static function transaction(Closure $callback): void
    {
        if (config('billing.database.use_transaction')) {
            DB::connection(config('billing.database.connection'))->transaction($callback, 3);
        } else {
            $callback();
        }
    }
}
