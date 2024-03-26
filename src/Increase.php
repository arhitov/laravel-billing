<?php

namespace Arhitov\LaravelBilling;

use Arhitov\LaravelBilling\Contracts\BillableRootInterface;
use Arhitov\LaravelBilling\Exceptions\TransferUsageException;
use Arhitov\LaravelBilling\Models\Balance;

class Increase extends Transfer
{
    const SENDER_BALANCE_CHANGE = false;

    /**
     * @param Balance $recipient
     * @param float $amount
     * @param string $gateway
     * @param string|null $description
     * @param Balance|null $sender
     * @param string|null $operation_identifier
     * @param string|null $operation_uuid
     * @throws TransferUsageException
     */
    public function __construct(
        Balance  $recipient,
        float    $amount,
        string   $gateway = 'internal',
        ?string  $description = null,
        ?Balance $sender = null,
        ?string  $operation_identifier = null,
        ?string  $operation_uuid = null,
    )
    {
        $sender ??= (new RootBalance())->getBalance();

        if (! ($sender->owner instanceof BillableRootInterface)) {
            throw new TransferUsageException($this, 'Sender in not RootBalance');
        }

        parent::__construct(
            $sender,
            $recipient,
            $amount,
            $gateway,
            $description,
            $operation_identifier,
            $operation_uuid,
        );
    }
}
