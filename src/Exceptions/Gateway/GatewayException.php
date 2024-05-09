<?php
/**
 * Billing module for laravel projects
 *
 * @link      https://github.com/arhitov/laravel-billing
 * @package   arhitov/laravel-billing
 * @license   MIT
 * @copyright Copyright (c) 2024, Alexander Arhitov, clgsru@gmail.com
 */

namespace Arhitov\LaravelBilling\Exceptions\Gateway;

use Arhitov\LaravelBilling\Exceptions\LaravelBillingException;
use Exception;

class GatewayException extends LaravelBillingException
{
    /**
     * Create a new exception instance.
     *
     * @param string $gateway
     * @param string $msg
     * @param \Exception|null $exception
     */
    public function __construct(
        public string $gateway,
        string $msg = '',
        public Exception|null $exception = null,
    )
    {
        parent::__construct($msg);
    }
}
