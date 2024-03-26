<?php

namespace Arhitov\LaravelBilling;

use Arhitov\LaravelBilling\Contracts\BillableRootInterface;
use Arhitov\LaravelBilling\Exceptions\TransferUsageException;
use Arhitov\LaravelBilling\Models\Balance;

class Decrease extends Transfer
{
    const RECIPIENT_BALANCE_CHANGE = false;

    /**
     * @param Balance $sender
     * @param float $amount
     * @param string $gateway
     * @param string|null $description
     * @param Balance|null $recipient
     * @param string|null $operation_identifier
     * @param string|null $operation_uuid
     * @throws TransferUsageException
     */
    public function __construct(
        Balance  $sender,
        float    $amount,
        string   $gateway = 'internal',
        ?string  $description = null,
        ?Balance $recipient = null,
        ?string  $operation_identifier = null,
        ?string  $operation_uuid = null,
    )
    {
        $recipient ??= (new RootBalance())->getBalance();

        if (! ($recipient->owner instanceof BillableRootInterface)) {
            throw new TransferUsageException($this, 'Recipient in not RootBalance');
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
