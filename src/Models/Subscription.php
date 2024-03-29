<?php

namespace Arhitov\LaravelBilling\Models;

use Arhitov\Helpers\Model\Eloquent\StateDatetimeTrait;
use Arhitov\Helpers\Validating\EloquentModelExtendTrait;
use Arhitov\LaravelBilling\Enums\CurrencyEnum;
use Arhitov\LaravelBilling\Enums\SubscriptionStateEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Watson\Validating\ValidatingTrait;

/**
 * @property int $id
 * @property string $uuid
 * @property string $key Unique subscription name on your system.
 * @property ?string $key_extend More information about subscriptions.
 * @property int $balance_id The balance from which the payment was made.
 * @property float $amount The amount that was paid upon purchase.
 * @property ?CurrencyEnum $currency
 * @property ?Carbon $beginning_at Subscription start date.
 * @property ?Carbon $expiry_at Subscription expiration date.
 * @property SubscriptionStateEnum $state
 * @property ?Carbon $state_pending_at
 * @property ?Carbon $state_active_at
 * @property ?Carbon $state_inactive_at
 * @property ?Carbon $state_locked_at
 * @property ?Carbon $state_expiry_at
 * @property ?Carbon $created_at Date of creation
 * @property ?Carbon $updated_at Date updated
 * Dependency:
 * @property \Arhitov\LaravelBilling\Contracts\BillableInterface $owner
 * @property Balance $balance The balance from which the payment was made.
 */
class Subscription extends Model
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
        'uuid',
        'key',
        'key_extend',
        'balance_id',
        'currency',
        'amount',
        'beginning_at',
        'expiry_at',
        'state',
    ];

    /**
     * The default rules that the model will validate against.
     *
     * @var array[]
     */
    protected $rules = [
        'uuid' => ['required', 'string'],
        'key' => ['required', 'string', 'max:255'],
        'key_extend' => ['nullable', 'string', 'max:255'],
        'balance_id' => ['nullable', 'required_with:amount', 'int', 'min:1'],
        'currency' => ['nullable', 'required_with:balance_id', 'in:class:' . CurrencyEnum::class],
        'amount' => ['nullable', 'numeric'],
        'beginning_at' => ['nullable', 'date'],
        'expiry_at' => ['nullable', 'date'],
        'state' => ['required', 'in:class:' . SubscriptionStateEnum::class],
    ];

    /**
     * The attributes, with a default value.
     *
     * @var array
     */
    protected $attributes = [
        'key_extend' => null,
        'amount' => null,
        'beginning_at' => null,
        'expiry_at' => null,
        'state' => SubscriptionStateEnum::Pending,
        'state_pending_at' => null,
        'state_active_at' => null,
        'state_inactive_at' => null,
        'state_locked_at' => null,
        'state_expiry_at' => null,
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'uuid' => 'string',
        'key' => 'string',
        'amount' => 'float',
        'currency' => CurrencyEnum::class,
        'beginning_at' => 'datetime',
        'expiry_at' => 'datetime',
        'state' => SubscriptionStateEnum::class,
        'state_pending_at' => 'datetime',
        'state_active_at' => 'datetime',
        'state_inactive_at' => 'datetime',
        'state_locked_at' => 'datetime',
        'state_expiry_at' => 'datetime',
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
        return config('billing.database.tables.subscription');
    }

//    public static function boot(): void
//    {
////        // Call event in Owner::createSubscription
////        self::created(function (self $subscription) {
////            event(new SubscriptionCreatedEvent($subscription));
////        });
//
//        parent::boot();
//    }

    /**
     * Dependency Owner
     *
     * @return MorphTo
     */
    public function owner(): MorphTo
    {
        return $this->morphTo('owner');
    }

    /**
     * Dependency The balance from which the payment was made.
     *
     * @return BelongsTo
     */
    public function balance(): BelongsTo
    {
        return $this->belongsTo(Balance::class,'balance_id', 'id');
    }

    public function setBalance(Balance $balance): self
    {
        $this->balance_id = $balance->id;
        $this->currency = $balance->currency;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->exists && $this->state->isActive();
    }
}
