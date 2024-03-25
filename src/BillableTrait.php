<?php

namespace Arhitov\LaravelBilling;

use Arhitov\LaravelBilling\Models\Traits\ModelOwnerExpandTrait;

trait BillableTrait
{
    use ModelOwnerExpandTrait;

    public static function bootBillableTrait(): void
    {
        self::bootDeleteCascadeBalance();
    }
}
