<?php
/**
 * Billing module for laravel projects
 *
 * @link      https://github.com/arhitov/laravel-billing
 * @package   arhitov/laravel-billing
 * @license   MIT
 * @copyright Copyright (c) 2024, Alexander Arhitov, clgsru@gmail.com
 */

namespace Arhitov\LaravelBilling\Enums;

enum ReceiptStateEnum: string
{
    case Created =   'created';   // The receipt is created
    case Pending =   'pending';   // Waiting for payment
    case Paid =      'paid';      // Paid. Ready to create a receipt.
    case Send =      'send';      // Sent. Waiting for completion confirmation.
    case Succeeded = 'succeeded'; // Receipt completed
    case Canceled =  'canceled';

    const WEIGHTS = [
        'created'    => 0,
        'pending'    => 1,
        'paid'       => 2,
        'send'       => 3,
        'succeeded'  => 4,
        'canceled'   => 99,
    ];

    public function isActive(): bool
    {
        return in_array($this->value, [
            self::Created->value,
            self::Pending->value,
            self::Paid->value,
            self::Send->value,
        ]);
    }

    public function isPaid(): bool
    {
        return $this->value === self::Paid->value;
    }

    public function isSucceeded(): bool
    {
        return $this->value === self::Succeeded->value;
    }

    public function allowUpdate(self $enum): bool
    {
        return self::WEIGHTS[$enum->value] >= self::WEIGHTS[$this->value];
    }
}
