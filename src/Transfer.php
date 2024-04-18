<?php

namespace Arhitov\LaravelBilling;

use Arhitov\LaravelBilling\Enums\OperationStateEnum;
use Arhitov\LaravelBilling\Events\BalanceDecreaseEvent;
use Arhitov\LaravelBilling\Events\BalanceIncreaseEvent;
use Arhitov\LaravelBilling\Exceptions;
use Arhitov\LaravelBilling\Models\Balance;
use Arhitov\LaravelBilling\Models\Operation;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class Transfer
{
    const SENDER_BALANCE_CHANGE = true;
    const RECIPIENT_BALANCE_CHANGE = true;

    protected ?Operation $operation = null;
    protected ?Throwable $exception = null;

    /**
     * @param Balance $sender
     * @param Balance $recipient
     * @param float $amount
     * @param string $gateway
     * @param string|null $description
     * @param string|null $operation_identifier
     * @param string|null $operation_uuid
     * @throws \Arhitov\LaravelBilling\Exceptions\Common\AmountException
     */
    public function __construct(
        protected Balance $sender,
        protected Balance $recipient,
        protected float   $amount,
        protected string  $gateway = 'internal',
        protected ?string $description = null,
        protected ?string $operation_identifier = null,
        protected ?string $operation_uuid = null,
    )
    {
        if (0 > $this->amount || $this->amount > INF) {
            throw new Exceptions\Common\AmountException($this->amount);
        }

        // Create operation
        $this->operation = Operation::make([
            'operation_identifier' => $this->operation_identifier ?? null,
            'operation_uuid' => $this->operation_uuid ?? Str::orderedUuid()->toString(),
            'gateway' => $this->gateway,
            'amount' => $this->amount,
            'currency' => $this->recipient->currency,
            'sender_balance_id' => $this->sender->id,
            'sender_amount_before' => $this->sender->amount,
            'recipient_balance_id' => $this->recipient->id,
            'recipient_amount_before' => $this->recipient->amount,
            'state' => OperationStateEnum::Created,
            'description' => $this->description,
        ]);
    }

    /**
     * @return Operation|null
     */
    public function getOperation(): ?Operation
    {
        return $this->operation;
    }

    /**
     * @return Throwable|null
     */
    public function getException(): ?Throwable
    {
        return $this->exception;
    }

    public function setDescription(string $description): void
    {
        $this->operation->fill(['description' => $description]);
    }

    /**
     * @return bool
     */
    public function isAllow(): bool
    {
        try {
            $this->isAllowOrFail();
            return true;
        } catch (Throwable $exception) {
            $this->exception = $exception;
            return false;
        }
    }

    /**
     * @return void
     * @throws Exceptions\BalanceException
     * @throws Exceptions\OperationCurrencyNotMatchException
     */
    public function isAllowOrFail(): void
    {
        if ($this->sender->currency !== $this->recipient->currency) {
            throw new Exceptions\OperationCurrencyNotMatchException($this->operation);
        }
        if (static::SENDER_BALANCE_CHANGE) {
            if (! $this->sender->state->isAllowDecrease()) {
                throw new Exceptions\BalanceNotAllowDecreaseException($this->sender);
            }
            if (! is_null($this->sender->limit) && 0 > (($this->sender->amount - $this->amount) + $this->sender->limit)) {
                throw new Exceptions\BalanceEmptyException($this->sender);
            }
        }
        if (static::RECIPIENT_BALANCE_CHANGE && ! $this->recipient->state->isAllowIncrease()) {
            throw new Exceptions\BalanceNotAllowIncreaseException($this->recipient);
        }
    }

    public function create(): bool
    {
        try {
            $this->createOrFail();
            return true;
        } catch (Throwable $exception) {
            $this->exception = $exception;
            return false;
        }
    }

    /**
     * @return Operation
     * @throws Exceptions\OperationAlreadyCreatedException
     * @throws Throwable
     */
    public function createOrFail(): Operation
    {
        if ($this->operation->exists) {
            throw new Exceptions\OperationAlreadyCreatedException($this->operation);
        }

        $this->operation->saveOrFail();

        return $this->operation;
    }

    /**
     * @return bool
     */
    public function execute(): bool
    {
        try {
            $this->executeOrFail();
            return true;
        } catch (Throwable $exception) {
            $this->exception = $exception;
            return false;
        }
    }

    /**
     * @return void
     * @throws Exceptions\BalanceException
     * @throws Exceptions\OperationException
     * @throws Throwable
     */
    public function executeOrFail(): void
    {
        $this->isAllowOrFail();

        $this->createOrFail();

        try {

            $groupOperation = function() {

                /** @var Balance $balanceRecipient */
                /** @var Balance $balanceSender */
                if (config('billing.database.use_lock_line') && config('billing.database.use_transaction')) {

                    if (static::SENDER_BALANCE_CHANGE) {
                        $this->sender->lockForUpdate();
                    }
                    if (static::RECIPIENT_BALANCE_CHANGE) {
                        $this->recipient->lockForUpdate();
                    }
                    $this->operation->lockForUpdate();

                    if (static::SENDER_BALANCE_CHANGE) {
                        // Load actual data
                        $balanceSender = Balance::findOrFail($this->sender->id);
                        $balanceSender->amount = $balanceSender->amount - $this->amount;
                        $balanceSender->saveOrFail();
                        $this->operation->sender_amount_after = $balanceSender->amount;
                    }
                    if (static::RECIPIENT_BALANCE_CHANGE) {
                        // Load actual data
                        $balanceRecipient = Balance::findOrFail($this->recipient->id);
                        $balanceRecipient->amount = $balanceRecipient->amount + $this->amount;
                        $balanceRecipient->saveOrFail();
                        $this->operation->recipient_amount_after = $balanceRecipient->amount;
                    }

                } else { // If a lock is used, then such a complex mechanism is not needed

                    if (static::SENDER_BALANCE_CHANGE) {
                        $this->sender->newQuery()
                            ->where('id', '=', $this->sender->id)
                            ->limit(1)
                            ->update([
                                'amount' => DB::raw("`amount` - {$this->amount}"),
                            ]);

                        // Load actual data
                        $balanceSender = $this->recipient->newQuery()
                            ->select('amount')
                            ->where('id', '=', $this->recipient->id)
                            ->firstOrFail();
                        $this->operation->sender_amount_after = $balanceSender->amount;
                    }

                    if (static::RECIPIENT_BALANCE_CHANGE) {
                        $this->recipient->newQuery()
                            ->where('id', '=', $this->recipient->id)
                            ->limit(1)
                            ->update([
                                'amount' => DB::raw("`amount` + {$this->amount}"),
                            ]);

                        // Load actual data
                        $balanceRecipient = $this->recipient->newQuery()
                            ->select('amount')
                            ->where('id', '=', $this->recipient->id)
                            ->firstOrFail();
                        $this->operation->recipient_amount_after = $balanceRecipient->amount;
                    }
                }

                $this->operation->state = OperationStateEnum::Succeeded;
                $this->operation->saveOrFail();

                if (static::SENDER_BALANCE_CHANGE) {
                    $this->sender->amount = $this->operation->sender_amount_after;
                }
                if (static::RECIPIENT_BALANCE_CHANGE) {
                    $this->recipient->amount = $this->operation->recipient_amount_after;
                }
            };

            if (config('billing.database.use_transaction')) {
                DB::connection($this->operation->getConnectionName())->transaction($groupOperation, 3);
            } else {
                $groupOperation();
            }

            if (static::SENDER_BALANCE_CHANGE) {
                event(new BalanceDecreaseEvent($this->sender));
            }
            if (static::RECIPIENT_BALANCE_CHANGE) {
                event(new BalanceIncreaseEvent($this->recipient));
            }

        } catch (Exception $exception) {
            $this->operation->state = OperationStateEnum::Errored;
            $this->operation->saveOrFail();
            throw $exception;
        }
    }
}
