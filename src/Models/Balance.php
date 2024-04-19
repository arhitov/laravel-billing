<?php

namespace Arhitov\LaravelBilling\Models;

use Arhitov\Helpers\Model\Eloquent\StateDatetimeTrait;
use Arhitov\Helpers\Validating\EloquentModelExtendTrait;
use Arhitov\LaravelBilling\Contracts\BillableInterface;
use Arhitov\LaravelBilling\Enums\BalanceStateEnum;
use Arhitov\LaravelBilling\Enums\CurrencyEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Cache;
use Watson\Validating\ValidatingTrait;

/**
 * @property int $id
 * @property string $owner_type
 * @property int $owner_id
 * @property string $key
 * @property float $amount
 * @property CurrencyEnum $currency
 * @property float $limit
 * @property BalanceStateEnum $state
 * @property ?Carbon $state_active_at
 * @property ?Carbon $state_inactive_at
 * @property ?Carbon $state_locked_at
 * @property ?Carbon $created_at Date of creation
 * @property ?Carbon $updated_at Date updated
 * Dependency:
 * @property \Arhitov\LaravelBilling\Contracts\BillableInterface $owner
 * @property \Illuminate\Support\Collection<CreditCard> $credit_card
 *
 * @method lockForUpdate(): Builder
 * @method static make(array $attributes = []): Builder|Model
 * @method static find($id, $columns = ['*']): Builder|Builder[]|Collection|Model
 * @method static findOrFail($id, $columns = ['*']): Builder|Builder[]|Collection|Model
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
        'amount' => 0,
        'limit' => 0,
        'state' => BalanceStateEnum::Active,
        'state_active_at' => null,
        'state_inactive_at' => null,
        'state_locked_at' => null,
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
        'state_active_at' => 'datetime',
        'state_inactive_at' => 'datetime',
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
        return config('billing.database.tables.balance');
    }

//    public static function boot(): void
//    {
////        // Call event in Owner::createBalance
////        self::created(function (self $balance) {
////            event(new BalanceCreatedEvent($balance));
////        });
//
//        parent::boot();
//    }

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
     * Dependency Saved credit card methods
     *
     * @return HasMany
     */
    public function creditCard(): HasMany
    {
        return $this->hasMany(CreditCard::class,'owner_balance_id', 'id');
    }

    /**
     * Dependency Operation
     *
     * @return Builder
     */
    public function operation(): Builder
    {
        $id = $this->id;
        return Operation::query()->where(static function (Builder $queryBuilder) use ($id) {
            $queryBuilder
                ->orWhere('sender_balance_id', '=', $id)
                ->orWhere('recipient_balance_id', '=', $id);
        });
    }

    public function addCreditCardOrFail(array $attributes): CreditCard
    {
        /** @var CreditCard $creditCard */
        $creditCard = $this->creditCard()->create($attributes);
        return $creditCard;
    }

    public function putInCache(): void
    {
        $ownerCacheKey = $this->getSettingCache();
        if ($ownerCacheKey) {
            Cache::put($ownerCacheKey['key'], [
                'id' => $this->id,
                'amount' => $this->amount,
                'currency' => $this->currency->value,
                'limit' => $this->limit,
                'state' => $this->state->value,
            ], $ownerCacheKey['ttl']);
        }
    }

    public function deleteFromCache(): void
    {
        $ownerCacheKey = $this->getSettingCache();
        if ($ownerCacheKey) {
            Cache::delete($ownerCacheKey['key']);
        }
    }

    public static function getFromCache(BillableInterface $owner, string $key): ?self
    {
        $cacheKeySetting = self::makeCacheKeySetting($owner, $key);
        if (is_null($cacheKeySetting)) {
            return null;
        }

        $data = Cache::get($cacheKeySetting['key'], null);

        if (is_null($data)) {
            return null;
        }

        $balance = self::make([
            'owner_type' => get_class($owner),
            'owner_id' => $owner->id,
            'key' => $key,
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'limit' => $data['limit'],
            'state' => $data['state'],
        ]);
        $balance->id = $data['id'];

        return $balance;
    }

    public function getSettingCache(): ?array
    {
        if (! $this->exists) {
            return null;
        }

        $cacheKeySetting = self::makeCacheKeySetting($this->owner, $this->key);
        if (is_null($cacheKeySetting)) {
            return null;
        }

        return $cacheKeySetting;
    }

    public static function makeCacheKeySetting(BillableInterface $owner, string $key): ?array
    {
        $ownerCacheKey = config('billing.cache.keys.owner_balance_amount');
        if (empty($ownerCacheKey)) {
            return null;
        }

        $ownerType = get_class($owner);
        $ownerId = $owner->id;

        return [
            'key' => ($ownerCacheKey['prefix'] ?? 'owner_balance_amount') . ':' . md5($ownerType . '.' . $ownerId) . ':' . $key,
            'ttl' => Carbon::parse($ownerCacheKey['ttl'] ?? '10 minutes')
        ];
    }
}
