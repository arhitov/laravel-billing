<?php

namespace Arhitov\LaravelBilling;

use Arhitov\LaravelBilling\Models\Traits\ModelOwnerExpandTrait;

trait BillableRootTrait
{
    use ModelOwnerExpandTrait;

    public static function bootBillableRootTrait(): void
    {
        self::bootDeleteCascadeBalance();
    }
}
