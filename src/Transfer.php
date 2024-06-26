<?php

namespace Arhitov\LaravelBilling;

use Arhitov\LaravelBilling\Contracts\BillableRootInterface;
use Arhitov\LaravelBilling\Enums\OperationStateEnum;
use Arhitov\LaravelBilling\Events\BalanceDecreaseEvent;
use Arhitov\LaravelBilling\Events\BalanceIncreaseEvent;
use Arhitov\LaravelBilling\Exceptions;
use Arhitov\LaravelBilling\Helpers\DatabaseHelper;
use Arhitov\LaravelBilling\Models\Balance;
use Arhitov\LaravelBilling\Models\Operation;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class Transfer
{
    protected ?Operation $operation = null;
    protected ?Throwable $exception = null;

    /**
     * @param Operation $operation
     * @return self
     * @throws \Arhitov\LaravelBilling\Exceptions\Common\AmountException
     */
    public static function make(Operation $operation): self
    {
        return new self(
            $operation->sender_balance,
            $operation->recipient_balance,
            $operation->amount,
            $operation->gateway,
            $operation->description,
            $operation->operation_identifier,
            $operation->operation_uuid,
            operation: $operation,
        );
    }

    /**
     * @param Balance $sender
     * @param Balance $recipient
     * @param float $amount
     * @param string $gateway
     * @param string|null $description
     * @param string|null $operation_identifier
     * @param string|null $operation_uuid
     * @param Operation|null $operation
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
        Operation $operation = null,
    )
    {
        if (0 > $this->amount || $this->amount > INF) {
            throw new Exceptions\Common\AmountException($this->amount);
        }

        // Create operation
        $this->operation = $operation ?? Operation::make([
            'operation_identifier'    => $this->operation_identifier ?? null,
            'operation_uuid'          => $this->operation_uuid ?? Str::orderedUuid()->toString(),
            'gateway'                 => $this->gateway,
            'amount'                  => $this->amount,
            'currency'                => $this->recipient->currency,
            'sender_balance_id'       => $this->sender->id,
            'sender_amount_before'    => $this->sender->amount,
            'recipient_balance_id'    => $this->recipient->id,
            'recipient_amount_before' => $this->recipient->amount,
            'state'                   => OperationStateEnum::Created,
            'description'             => $this->description,
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
        } catch (Exceptions\OperationException|Exceptions\BalanceException $exception) {
            $this->exception = $exception;
            return false;
        }
    }

    public function isChangeSenderBalance(): bool
    {
        return ! $this->sender->owner instanceof BillableRootInterface;
    }

    public function isChangeRecipientBalance(): bool
    {
        return ! $this->recipient->owner instanceof BillableRootInterface;
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

        if ($this->isChangeSenderBalance()) {
            if (! $this->sender->state->isAllowDecrease()) {
                throw new Exceptions\BalanceNotAllowDecreaseException($this->sender);
            }
            if (! is_null($this->sender->limit) && 0 > (($this->sender->amount - $this->amount) + $this->sender->limit)) {
                throw new Exceptions\BalanceEmptyException($this->sender);
            }
        }
        if ($this->isChangeRecipientBalance() && ! $this->recipient->state->isAllowIncrease()) {
            throw new Exceptions\BalanceNotAllowIncreaseException($this->recipient);
        }
    }

    /**
     * @return bool
     */
    public function isAllowExecute(): bool
    {
        try {
            $this->isAllowExecuteOrFail();
            return true;
        } catch (Exceptions\Operation\OperationIsNotActiveException $exception) {
            $this->exception = $exception;
            return false;
        }
    }

    /**
     * @return void
     * @throws \Arhitov\LaravelBilling\Exceptions\Operation\OperationIsNotActiveException
     */
    public function isAllowExecuteOrFail(): void
    {
        if (! $this->operation->state->isActive()) {
            throw new Exceptions\Operation\OperationIsNotActiveException($this->operation);
        }
    }

    /**
     * @return bool
     */
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

        if (! $this->operation->exists) {
            $this->createOrFail();
        }

        $this->isAllowExecuteOrFail();

        try {

            DatabaseHelper::transaction(function() {

                /** @var Balance $balanceRecipient */
                /** @var Balance $balanceSender */
                if (config('billing.database.use_lock_line') && config('billing.database.use_transaction')) {

                    if ($this->isChangeSenderBalance()) {
                        $this->sender->lockForUpdate();
                    }
                    if ($this->isChangeRecipientBalance()) {
                        $this->recipient->lockForUpdate();
                    }
                    $this->operation->lockForUpdate();

                    if ($this->isChangeSenderBalance()) {
                        // Load actual data
                        $balanceSender = Balance::findOrFail($this->sender->id);
                        $balanceSender->amount = $balanceSender->amount - $this->amount;
                        $balanceSender->saveOrFail();
                        $this->operation->sender_amount_after = $balanceSender->amount;
                    }
                    if ($this->isChangeRecipientBalance()) {
                        // Load actual data
                        $balanceRecipient = Balance::findOrFail($this->recipient->id);
                        $balanceRecipient->amount = $balanceRecipient->amount + $this->amount;
                        $balanceRecipient->saveOrFail();
                        $this->operation->recipient_amount_after = $balanceRecipient->amount;
                    }

                } else { // If a lock is used, then such a complex mechanism is not needed

                    if ($this->isChangeSenderBalance()) {
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

                    if ($this->isChangeRecipientBalance()) {
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

                if ($this->isChangeSenderBalance()) {
                    $this->sender->amount = $this->operation->sender_amount_after;
                }
                if ($this->isChangeRecipientBalance()) {
                    $this->recipient->amount = $this->operation->recipient_amount_after;
                }
            });

            if ($this->isChangeSenderBalance()) {
                event(new BalanceDecreaseEvent($this->sender));
            }
            if ($this->isChangeRecipientBalance()) {
                event(new BalanceIncreaseEvent($this->recipient));
            }

        } catch (Exception $exception) {
            $this->operation->state = OperationStateEnum::Errored;
            $this->operation->saveOrFail();
            throw $exception;
        }
    }
}
