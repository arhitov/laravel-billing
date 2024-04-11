<?php

namespace Arhitov\LaravelBilling\Models;

use Arhitov\Helpers\Model\Eloquent\StateDatetimeTrait;
use Arhitov\Helpers\Validating\EloquentModelExtendTrait;
use Arhitov\LaravelBilling\Enums\CreditCardStateEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Watson\Validating\ValidatingTrait;

/**
 * @property int $id
 * @property int $owner_balance_id
 * @property string $title
 * @property string $rebill_id
 * @property string $gateway
 * @property ?string $card_first6
 * @property ?string $card_last4
 * @property ?string $card_type
 * @property ?Carbon $card_expiry_at Expiration date
 * @property ?string $issuer_country
 * @property ?string $issuer_name
 * @property CreditCardStateEnum $state
 * @property ?Carbon $state_active_at
 * @property ?Carbon $state_inactive_at
 * @property ?Carbon $state_insolvent_at
 * @property ?Carbon $state_invalid_at
 * @property ?Carbon $state_locked_at
 * @property ?Carbon $created_at Date of creation
 * @property ?Carbon $updated_at Date updated
 */
class CreditCard extends Model
{
    use ValidatingTrait, EloquentModelExtendTrait {
        EloquentModelExtendTrait::getRules insteadof ValidatingTrait;
    }
    use StateDatetimeTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'rebill_id',
        'gateway',
        'card_first6',
        'card_last4',
        'card_type',
        'card_expiry_at',
        'issuer_country',
        'issuer_name',
        'state',
    ];

    /**
     * The default rules that the model will validate against.
     *
     * @var array[]
     */
    protected $rules = [
        'owner_balance_id' => ['required', 'int'],
        'title' => ['nullable', 'string', 'max:50'],
        'rebill_id' => ['required', 'string', 'max:255'],
        'gateway' => ['required', 'string', 'max:50'],
        'card_first6' => ['nullable', 'string', 'max:6'],
        'card_last4' => ['nullable', 'string', 'max:4'],
        'card_type' => ['nullable', 'string', 'max:50'],
        'card_expiry_at' => ['nullable', 'date'],
        'issuer_country' => ['nullable', 'string', 'max:20'],
        'issuer_name' => ['nullable', 'string', 'max:255'],
        'state' => ['required', 'in:class:' . CreditCardStateEnum::class],
    ];

    /**
     * The attributes, with a default value.
     *
     * @var array
     */
    protected $attributes = [
        'title' => 'Saved card',
        'card_first6' => null,
        'card_last4' => null,
        'card_type' => null,
        'card_expiry_at' => null,
        'issuer_country' => null,
        'issuer_name' => null,
        'state' => CreditCardStateEnum::Created,
        'state_active_at' => null,
        'state_inactive_at' => null,
        'state_insolvent_at' => null,
        'state_invalid_at' => null,
        'state_locked_at' => null,
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'card_expiry_at' => 'datetime',
        'state' => CreditCardStateEnum::class,
        'state_active_at' => 'datetime',
        'state_inactive_at' => 'datetime',
        'state_insolvent_at' => 'datetime',
        'state_invalid_at' => 'datetime',
        'state_locked_at' => 'datetime',
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
        return config('billing.database.tables.credit_card');
    }

    /**
     * Dependency The balance to which the saved payment method is linked.
     *
     * @return BelongsTo
     */
    public function balance(): BelongsTo
    {
        return $this->belongsTo(Balance::class, 'owner_balance_id', 'id');
    }
}
