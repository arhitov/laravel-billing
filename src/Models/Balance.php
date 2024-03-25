<?php

namespace Arhitov\LaravelBilling\Models;

use Arhitov\Helpers\Model\Eloquent\StateDatetimeTrait;
use Arhitov\Helpers\Validating\EloquentModelExtendTrait;
use Arhitov\LaravelBilling\Enums\BalanceStateEnum;
use Arhitov\LaravelBilling\Enums\CurrencyEnum;
use Arhitov\LaravelBilling\Events\BalanceCreatedEvent;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Watson\Validating\ValidatingTrait;

/**
 * @property int $id
 * @property string $key
 * @property float $amount
 * @property CurrencyEnum $currency
 * @property float $limit
 * @property BalanceStateEnum $state
 * @property ?Carbon $active_at
 * @property ?Carbon $inactive_at
 * @property ?Carbon $locked_at
 * @property ?Carbon $created_at Date of creation
 * @property ?Carbon $updated_at Date updated
 * Dependency:
 * @property \Arhitov\LaravelBilling\Contracts\BillableInterface $owner
 * @property \Illuminate\Support\Collection<SavedPayment> $saved_payment
 */
class Balance extends Model
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
        'owner_type',
        'owner_id',
        'key',
        'amount',
        'currency',
        'limit',
        'state',
    ];

    /**
     * The default rules that the model will validate against.
     *
     * @var array[]
     */
    protected $rules = [
        'owner_type' => ['required', 'string', 'max:255'],
        'owner_id' => ['required', 'int', 'min:1'],
        'key' => ['required', 'string', 'max:255'],
        'amount' => ['required', 'numeric'],
        'currency' => ['required', 'in:class:' . CurrencyEnum::class],
        'limit' => ['nullable', 'numeric', 'min:0'],
        'state' => ['required', 'in:class:' . BalanceStateEnum::class],
    ];

    /**
     * The attributes, with a default value.
     *
     * @var array
     */
    protected $attributes = [
        'key' => 'main',
        'amount' => 0,
        'limit' => 0,
        'state' => BalanceStateEnum::Active,
        'active_at' => null,
        'inactive_at' => null,
        'locked_at' => null,
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
//        'owner' => 'string',
        'key' => 'string',
        'amount' => 'float',
        'currency' => CurrencyEnum::class,
        'state' => BalanceStateEnum::class,
        'active_at' => 'datetime',
        'inactive_at' => 'datetime',
        'locked_at' => 'datetime',
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
        return config('billing.database.tables.balance');
    }

    public static function boot(): void
    {
        self::created(function (self $balance) {
            event(new BalanceCreatedEvent($balance));
        });

        parent::boot();
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

    /**
     * Dependency Saved payment methods
     *
     * @return HasMany
     */
    public function savedPayment(): HasMany
    {
        return $this->hasMany(SavedPayment::class,'owner_balance_id', 'id');
    }

    public function addPaymentMethodsOrFail(array $attributes): SavedPayment
    {
        /** @var SavedPayment $savedPayment */
        $savedPayment = $this->savedPayment()->create($attributes);
        return $savedPayment;
    }
}
