<?php

namespace Arhitov\LaravelBilling\Models;

use Arhitov\LaravelBilling\BillableTrait;
use Arhitov\LaravelBilling\Contracts\BillableInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static create(array $attributes = []): Builder|Model
 */
class User extends Model implements BillableInterface
{
    use BillableTrait;

    protected $table = 'users';

    protected $guarded = [];
}
