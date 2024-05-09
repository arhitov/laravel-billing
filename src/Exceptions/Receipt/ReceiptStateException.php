<?php
/**
 * Billing module for laravel projects
 *
 * @link      https://github.com/arhitov/laravel-billing
 * @package   arhitov/laravel-billing
 * @license   MIT
 * @copyright Copyright (c) 2024, Alexander Arhitov, clgsru@gmail.com
 */

namespace Arhitov\LaravelBilling\Exceptions\Receipt;

use Arhitov\LaravelBilling\Enums\ReceiptStateEnum;
use Arhitov\LaravelBilling\Models\Receipt;

class ReceiptStateException extends ReceiptException
{
    /**
     * Create a new exception instance.
     *
     * @param Receipt|null $receipt
     * @param ReceiptStateEnum $newState
     * @param string|null $msg
     */
    public function __construct(public Receipt|null $receipt, public ReceiptStateEnum $newState, string $msg = null)
    {
        parent::__construct($receipt, $msg ?? '');
    }
}
