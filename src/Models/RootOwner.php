<?php

namespace Arhitov\LaravelBilling\Models;

use Arhitov\LaravelBilling\BillableRootTrait;
use Arhitov\LaravelBilling\Contracts\BillableRootInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $name
 */
class RootOwner extends Model implements BillableRootInterface
{
    use BillableRootTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'name',
    ];

    /**
     * The default rules that the model will validate against.
     *
     * @var array[]
     */
    protected $rules = [
        'name' => ['required', 'string', 'min:1', 'max:255'],
    ];

    /**
     * Get the current connection name for the model.
     *
     * @return string|null
     */
    public function getConnectionName(): ?string
    {
        return config('billing.database.connection');
    }

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable(): string
    {
        return config('billing.root_owner.table');
    }
}
