<?php

namespace Arhitov\LaravelBilling;

use Arhitov\LaravelBilling\Contracts\BillableRootInterface;
use Arhitov\LaravelBilling\Exceptions\TransferUsageException;
use Arhitov\LaravelBilling\Models\Balance;

class Decrease extends Transfer
{
    const RECIPIENT_BALANCE_CHANGE = false;

    public function __construct(
        Balance  $sender,
        float    $amount,
        string   $gateway = 'internal',
        ?string  $description = null,
        ?Balance $recipient = null,
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
        );
    }
}
