<?php

namespace Arhitov\LaravelBilling\Models;

use Arhitov\Helpers\Validating\EloquentModelExtendTrait;
use Arhitov\LaravelBilling\Enums\CurrencyEnum;
use Arhitov\LaravelBilling\Enums\OperationStateEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Watson\Validating\ValidatingTrait;

/**
 * @property int $id
 * @property string $operation_id
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
 * @property ?Carbon $pending_at
 * @property ?Carbon $succeeded_at
 * @property ?Carbon $canceled_at
 * @property ?Carbon $refund_at
 * @property ?Carbon $errored_at
 * @property ?Carbon $created_at Дата создания
 * @property ?Carbon $updated_at Дата обновление
 */
class Operation extends Model
{
    use ValidatingTrait, EloquentModelExtendTrait {
        EloquentModelExtendTrait::getRules insteadof ValidatingTrait;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'operation_id',
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
        'state',
        'description',
        'pending_at',
        'succeeded_at',
        'canceled_at',
        'refund_at',
        'errored_at',
    ];

    /**
     * The default rules that the model will validate against.
     *
     * @var array[]
     */
    protected $rules = [
        'operation_id' => ['required', 'string'],
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
        'state' => ['required', 'in:class:' . OperationStateEnum::class],
        'description' => ['nullable', 'string', 'max:1000'],
        'pending_at' => ['nullable', 'datetime'],
        'succeeded_at' => ['nullable', 'datetime'],
        'canceled_at' => ['nullable', 'datetime'],
        'refund_at' => ['nullable', 'datetime'],
        'errored_at' => ['nullable', 'datetime'],
    ];

    /**
     * The attributes, with a default value.
     *
     * @var array
     */
    protected $attributes = [
        'linked_operation_id' => null,
        'description' => null,
        'sender_amount_before' => null,
        'sender_amount_after' => null,
        'recipient_amount_before' => null,
        'recipient_amount_after' => null,
        'pending_at' => null,
        'succeeded_at' => null,
        'canceled_at' => null,
        'refund_at' => null,
        'errored_at' => null,
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'operation_id' => 'string',
        'amount' => 'float',
        'currency' => CurrencyEnum::class,
        'sender_amount_before' => 'float',
        'sender_amount_after' => 'float',
        'recipient_amount_before' => 'float',
        'recipient_amount_after' => 'float',
        'state' => OperationStateEnum::class,
        'pending_at' => 'datetime',
        'succeeded_at' => 'datetime',
        'canceled_at' => 'datetime',
        'refund_at' => 'datetime',
        'errored_at' => 'datetime',
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

    public static function boot(): void
    {
        self::creating(function (self $operation) {
            $operation->setStatusDatetime();
        });
        self::updating(function (self $operation) {
            $operation->setStatusDatetime();
        });

        parent::boot();
    }

    private function setStatusDatetime(): void
    {
        $datetimeKey = ($this->state?->value ?? '') . '_at';
        if (($this->casts[$datetimeKey] ?? null) === 'datetime') {
            $this->$datetimeKey = Carbon::now();
        }
    }
}
