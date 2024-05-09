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

use Arhitov\LaravelBilling\Exceptions\LaravelBillingException;
use Arhitov\LaravelBilling\Models\Receipt;

class ReceiptException extends LaravelBillingException
{
    /**
     * Create a new exception instance.
     *
     * @param Receipt|null $receipt
     * @param string|null $msg
     */
    public function __construct(public Receipt|null $receipt, string $msg = null)
    {
        parent::__construct($msg ?? '');
    }
}
