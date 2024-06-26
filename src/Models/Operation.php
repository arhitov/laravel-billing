<?php
/**
 * Billing module for laravel projects
 *
 * @link      https://github.com/arhitov/laravel-billing
 * @package   arhitov/laravel-billing
 * @license   MIT
 * @copyright Copyright (c) 2024, Alexander Arhitov, clgsru@gmail.com
 */

namespace Arhitov\LaravelBilling\Models;

use Arhitov\Helpers\Model\Eloquent\StateDatetimeTrait;
use Arhitov\Helpers\Validating\EloquentModelExtendTrait;
use Arhitov\LaravelBilling\Enums\CurrencyEnum;
use Arhitov\LaravelBilling\Enums\OperationStateEnum;
use Arhitov\LaravelBilling\Enums\ReceiptStateEnum;
use Arhitov\LaravelBilling\Exceptions\OperationException;
use Arhitov\LaravelBilling\OmnipayGateway;
use Arhitov\LaravelBilling\Transfer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Omnipay\Common\Message\AbstractResponse;
use Watson\Validating\ValidatingTrait;

/**
 * @property int $id
 * @property string $operation_identifier
 * @property string $operation_uuid
 * @property string $linked_operation_id
 * @property string $gateway
 * @property ?string $gateway_payment_id
 * @property ?string $gateway_payment_state
 * @property float $amount
 * @property CurrencyEnum $currency
 * @property string $sender_balance_id
 * @property float $sender_amount_before
 * @property float $sender_amount_after
 * @property string $recipient_balance_id
 * @property float $recipient_amount_before
 * @property float $recipient_amount_after
 * @property OperationStateEnum $state
 * @property string $description
 * @property ?Carbon $state_pending_at
 * @property ?Carbon $state_waiting_for_capture_at
 * @property ?Carbon $state_succeeded_at
 * @property ?Carbon $state_canceled_at
 * @property ?Carbon $state_refund_at
 * @property ?Carbon $state_errored_at
 * @property ?Carbon $created_at Date of creation
 * @property ?Carbon $updated_at Date updated
 * Dependency:
 * @property Balance $sender_balance
 * @property Balance $recipient_balance
 *
 * @method static get($columns = ['*']): Collection
 * @method static make(array $attributes = []): Builder|Model
 * @method lockForUpdate(): Builder
 * @method static find($id, $columns = ['*']): Builder|Builder[]|Collection|Model
 * @method static findOrFail($id, $columns = ['*']): Builder|Builder[]|Collection|Model
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
        'gateway_payment_id',
        'gateway_payment_state',
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
        'operation_identifier'    => ['nullable', 'string'],
        'operation_uuid'          => ['required', 'string'],
        'linked_operation_id'     => ['nullable', 'int'],
        'gateway'                 => ['required', 'string', 'max:50'],
        'gateway_payment_id'      => ['nullable', 'string', 'max:100'],
        'gateway_payment_state'   => ['nullable', 'string', 'max:100'],
        'amount'                  => ['required', 'numeric'],
        'currency'                => ['required', 'in:class:' . CurrencyEnum::class],
        'sender_balance_id'       => ['required', 'int'],
        'sender_amount_before'    => ['nullable', 'numeric'],
        'sender_amount_after'     => ['nullable', 'numeric'],
        'recipient_balance_id'    => ['required', 'int'],
        'recipient_amount_before' => ['nullable', 'numeric'],
        'recipient_amount_after'  => ['nullable', 'numeric'],
        'description'             => ['nullable', 'string', 'max:1000'],
        'state'                   => ['required', 'in:class:' . OperationStateEnum::class],
    ];

    /**
     * The attributes, with a default value.
     *
     * @var array
     */
    protected $attributes = [
        'operation_identifier'         => null,
        'linked_operation_id'          => null,
        'gateway_payment_id'           => null,
        'gateway_payment_state'        => null,
        'description'                  => null,
        'sender_amount_before'         => null,
        'sender_amount_after'          => null,
        'recipient_amount_before'      => null,
        'recipient_amount_after'       => null,
        'state_pending_at'             => null,
        'state_waiting_for_capture_at' => null,
        'state_succeeded_at'           => null,
        'state_canceled_at'            => null,
        'state_refund_at'              => null,
        'state_errored_at'             => null,
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'operation_uuid'               => 'string',
        'amount'                       => 'float',
        'currency'                     => CurrencyEnum::class,
        'sender_amount_before'         => 'float',
        'sender_amount_after'          => 'float',
        'recipient_amount_before'      => 'float',
        'recipient_amount_after'       => 'float',
        'state'                        => OperationStateEnum::class,
        'state_pending_at'             => 'datetime',
        'state_waiting_for_capture_at' => 'datetime',
        'state_succeeded_at'           => 'datetime',
        'state_canceled_at'            => 'datetime',
        'state_refund_at'              => 'datetime',
        'state_errored_at'             => 'datetime',
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

    /**
     * Dependency The balance sender.
     *
     * @return BelongsTo
     */
    public function senderBalance(): BelongsTo
    {
        return $this->belongsTo(Balance::class, 'sender_balance_id', 'id');
    }

    // @TODO Fix this double method
    public function sender_balance(): BelongsTo
    {
        return $this->senderBalance();
    }

    /**
     * Dependency The balance recipient.
     *
     * @return BelongsTo
     */
    public function recipientBalance(): BelongsTo
    {
        return $this->belongsTo(Balance::class, 'recipient_balance_id', 'id');
    }

    // @TODO Fix this double method
    public function recipient_balance(): BelongsTo
    {
        return $this->recipientBalance();
    }

    /**
     * @param \Arhitov\LaravelBilling\OmnipayGateway $omnipayGateway
     * @param AbstractResponse $response
     * @return $this
     * @throws \Arhitov\LaravelBilling\Exceptions\BalanceException
     * @throws \Arhitov\LaravelBilling\Exceptions\Common\AmountException
     * @throws \Arhitov\LaravelBilling\Exceptions\OperationException
     * @throws \Throwable
     */
    public function setStateByOmnipayGateway(OmnipayGateway $omnipayGateway, AbstractResponse $response): self
    {
        $state = method_exists($response, 'getState')
            ? $response->getState()
            : match (true) {
                $response->isSuccessful() => 'success',
                $response->isCancelled() => 'cancel',
            };

        $newState = match (true) {
            'waiting_for_capture' === $state => OperationStateEnum::WaitingForCapture,
            $response->isSuccessful() => OperationStateEnum::Succeeded,
            $response->isCancelled() => OperationStateEnum::Canceled,
            default => $this->state,
        };

        $this->gateway_payment_state = $state;

        // If it was active and not paid, but is now paid, then we carry out payment.
        if (
            $this->state->isActive() &&
            ! $this->state->isSucceeded() &&
            $newState->isSucceeded()
        ) {
            Transfer::make($this)->executeOrFail();

            if (! $this->state->isSucceeded()) {
                throw new OperationException($this, 'Error transfer');
            } elseif ($omnipayGateway->isUseOmnireceipt() and $receipt = Receipt::query()->where('operation_uuid', '=', $this->operation_uuid)->first()) {
                $receipt->setState(ReceiptStateEnum::Paid)
                        ->saveOrFail();
            }

        } else {
            $this->state = $newState;
        }

        return $this;
    }
}
