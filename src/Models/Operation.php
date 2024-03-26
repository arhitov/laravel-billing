<?php

namespace Arhitov\LaravelBilling\Models;

use Arhitov\Helpers\Model\Eloquent\StateDatetimeTrait;
use Arhitov\Helpers\Validating\EloquentModelExtendTrait;
use Arhitov\LaravelBilling\Enums\CurrencyEnum;
use Arhitov\LaravelBilling\Enums\OperationStateEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Watson\Validating\ValidatingTrait;

/**
 * @property int $id
 * @property string $operation_identifier
 * @property string $operation_uuid
 * @property string $linked_operation_id
 * @property string $gateway
 * @property float $amount
 * @property string $sender_balance_id
 * @property float $sender_amount_before
 * @property float $sender_amount_after
 * @property string $recipient_balance_id
 * @property float $recipient_amount_before
 * @property float $recipient_amount_after
 * @property string $state
 * @property string $description
 * @property ?Carbon $state_pending_at
 * @property ?Carbon $state_succeeded_at
 * @property ?Carbon $state_canceled_at
 * @property ?Carbon $state_refund_at
 * @property ?Carbon $state_errored_at
 * @property ?Carbon $created_at Date of creation
 * @property ?Carbon $updated_at Date updated
 */
class Operation extends Model
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
        'operation_identifier',
        'operation_uuid',
        'linked_operation_id',
        'gateway',
        'amount',
        'currency',
        'sender_balance_id',
        'sender_amount_before',
        'sender_amount_after',
        'recipient_balance_id',
        'recipient_amount_before',
        'recipient_amount_after',
        'description',
        'state',
    ];

    /**
     * The default rules that the model will validate against.
     *
     * @var array[]
     */
    protected $rules = [
        'operation_identifier' => ['nullable', 'string'],
        'operation_uuid' => ['required', 'string'],
        'linked_operation_id' => ['nullable', 'int'],
        'gateway' => ['required', 'string', 'max:50'],
        'amount' => ['required', 'numeric'],
        'currency' => ['required', 'in:class:' . CurrencyEnum::class],
        'sender_balance_id' => ['required', 'int'],
        'sender_amount_before' => ['nullable', 'numeric'],
        'sender_amount_after' => ['nullable', 'numeric'],
        'recipient_balance_id' => ['required', 'int'],
        'recipient_amount_before' => ['nullable', 'numeric'],
        'recipient_amount_after' => ['nullable', 'numeric'],
        'description' => ['nullable', 'string', 'max:1000'],
        'state' => ['required', 'in:class:' . OperationStateEnum::class],
    ];

    /**
     * The attributes, with a default value.
     *
     * @var array
     */
    protected $attributes = [
        'operation_identifier' => null,
        'linked_operation_id' => null,
        'description' => null,
        'sender_amount_before' => null,
        'sender_amount_after' => null,
        'recipient_amount_before' => null,
        'recipient_amount_after' => null,
        'state_pending_at' => null,
        'state_succeeded_at' => null,
        'state_canceled_at' => null,
        'state_refund_at' => null,
        'state_errored_at' => null,
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'operation_uuid' => 'string',
        'amount' => 'float',
        'currency' => CurrencyEnum::class,
        'sender_amount_before' => 'float',
        'sender_amount_after' => 'float',
        'recipient_amount_before' => 'float',
        'recipient_amount_after' => 'float',
        'state' => OperationStateEnum::class,
        'state_pending_at' => 'datetime',
        'state_succeeded_at' => 'datetime',
        'state_canceled_at' => 'datetime',
        'state_refund_at' => 'datetime',
        'state_errored_at' => 'datetime',
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
        return config('billing.database.tables.operation');
    }
}
