<?php

namespace Arhitov\LaravelBilling\Models;

use Arhitov\LaravelBilling\BillableRootTrait;
use Arhitov\LaravelBilling\Contracts\BillableRootInterface;
use Illuminate\Database\Eloquent\Model;

class UserRoot extends Model implements BillableRootInterface
{
    use BillableRootTrait;

    protected $table = 'users';

    protected $guarded = [];
}
