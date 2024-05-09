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
use Arhitov\LaravelBilling\Enums\ReceiptStateEnum;
use Arhitov\LaravelBilling\Exceptions\Receipt\ReceiptStateException;
use Arhitov\LaravelBilling\OmnireceiptGateway;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Omnireceipt\Common\Entities\Receipt as BaseReceipt;
use Throwable;
use Watson\Validating\ValidatingTrait;

/**
 * @property int $id
 * @property string $owner_type
 * @property int $owner_id
 * @property string $operation_uuid
 * @property string $gateway
 * @property float $amount
 * @property CurrencyEnum $currency
 * @property array $receipt_data
 * @property ReceiptStateEnum $state
 * @property ?Carbon $state_pending_at
 * @property ?Carbon $state_paid_at
 * @property ?Carbon $state_send_at
 * @property ?Carbon $state_succeeded_at
 * @property ?Carbon $state_canceled_at
 * @property ?Carbon $created_at Date of creation
 * @property ?Carbon $updated_at Date updated
 * Dependency:
 * @property \Arhitov\LaravelBilling\Contracts\BillableInterface $owner
 * Methods:
 * @method static Receipt create(array $attributes = []):
 */
class Receipt extends Model
{
    use ValidatingTrait, EloquentModelExtendTrait {
        ValidatingTrait::isValid as isValidValidatingTrait;
        EloquentModelExtendTrait::getRules insteadof ValidatingTrait;
    }
    use StateDatetimeTrait;

    protected ?BaseReceipt $receipt = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'operation_uuid',
        'gateway',
        'state',
    ];

    /**
     * The default rules that the model will validate against.
     *
     * @var array[]
     */
    protected $rules = [
        'operation_uuid' => ['required', 'string'],
        'gateway'        => ['required', 'string', 'max:50'],
        'amount'         => ['required', 'numeric'],
        'currency'       => ['required', 'in:class:' . CurrencyEnum::class],
        'state'          => ['required', 'in:class:' . ReceiptStateEnum::class],
    ];

    /**
     * The attributes, with a default value.
     *
     * @var array
     */
    protected $attributes = [
        'state'              => ReceiptStateEnum::Created,
        'state_pending_at'   => null,
        'state_paid_at'      => null,
        'state_send_at'      => null,
        'state_succeeded_at' => null,
        'state_canceled_at'  => null,
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount'             => 'float',
        'currency'           => CurrencyEnum::class,
        'receipt_data'       => 'array',
        'state'              => ReceiptStateEnum::class,
        'state_pending_at'   => 'datetime',
        'state_paid_at'      => 'datetime',
        'state_send_at'      => 'datetime',
        'state_succeeded_at' => 'datetime',
        'state_canceled_at'  => 'datetime',
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
        return config('billing.database.tables.fiscal_receipt');
    }

    /**
     * Dependency Balance owner
     *
     * @return MorphTo
     */
    public function owner(): MorphTo
    {
        return $this->morphTo('owner');
    }

    public function setReceipt(BaseReceipt $receipt): self
    {
        $this->receipt = $receipt;
        $this->amount = $receipt->getAmount();
        $this->currency = $receipt->getItemList()->first()->getCurrency();
        $this->receipt_data = $receipt->toArray();
        return $this;
    }

    public function getReceipt(): ?BaseReceipt
    {
        if (is_null($this->receipt) && ! empty($this->receipt_data)) {
            $this->receipt = (new OmnireceiptGateway($this->gateway))->getGateway()->receiptRestore($this->receipt_data);
        }
        return $this->receipt;
    }

    public function changeState(ReceiptStateEnum $state): bool
    {
        try {
            $this->changeStateOrFail($state);
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * @param ReceiptStateEnum $state
     * @return void
     * @throws ReceiptStateException
     * @throws Throwable
     */
    public function changeStateOrFail(ReceiptStateEnum $state): void
    {
        if (! $this->state->allowUpdate($state)) {
            throw new ReceiptStateException($this, $state, 'A new state is not allowed to be installed.');
        }
        $this->setState($state);

        $this->saveOrFail();
    }

    public function isValid(): bool
    {
        $valid = $this->isValidValidatingTrait();
        $validationErrors = $this->getErrors();

        $receipt = $this->getReceipt();
        if (is_null($receipt)) {
            $validationErrors->add('receipt', 'The receipt field is required.');
            $valid = false;
        } elseif (! $receipt->validate()) {
            $validationErrors->add('receipt', 'The receipt is not validate.');
            $valid = false;
        } else {
            $currency = $this->currency?->value;
            $currencyMatch = true;

            foreach ($receipt->getItemList() as $item) {
                if ($currency !== $item->getCurrency()) {
                    $currencyMatch = false;
                    break;
                }
            }

            if (! $currencyMatch) {
                $validationErrors->add('receipt.item', 'Currency does not match.');
                $valid = false;
            }
        }

        return $valid;
    }
}
