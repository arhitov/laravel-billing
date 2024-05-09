<?php
/**
 * Billing module for laravel projects
 *
 * @link      https://github.com/arhitov/laravel-billing
 * @package   arhitov/laravel-billing
 * @license   MIT
 * @copyright Copyright (c) 2024, Alexander Arhitov, clgsru@gmail.com
 */

namespace Arhitov\LaravelBilling\Tests;

use Omnireceipt\Common\Entities\Customer as BaseCustomer;
use Omnireceipt\Common\Entities\Receipt as BaseReceipt;
use Omnireceipt\Common\Entities\ReceiptItem as BaseReceiptItem;
use Omnireceipt\Common\Entities\Seller as BaseSeller;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getApplicationTimezone($app)
    {
        return ini_get('date.timezone') ?: 'UTC';
    }

    protected function getEnvironmentSetUp($app): void
    {
        config(['billing' => require __DIR__ . '/../config/billing.php']);

        $configApp = config('app');
        $configApp['timezone'] = ini_get('date.timezone') ?: $configApp['timezone'];
        config(['app' => $configApp]);
    }

    public static function makeSeller(array $parameters = []): BaseSeller
    {
        $parameters['name'] ??= 'Seller Number One';
        return new class($parameters) extends BaseSeller{};
    }

    public static function makeCustomer(array $parameters = []): BaseCustomer
    {
        $parameters['type'] ??= 'Shopaholic';
        return new class($parameters) extends BaseCustomer{};
    }

    public static function makeReceipt(array $parameters = []): BaseReceipt
    {
        $parameters['type'] ??= 'payment';
        $parameters['date'] ??= '2024.05.06 17:05:37';
        return new class($parameters) extends BaseReceipt
        {
            public function getId(): string
            {
                return $this->getParameter('id');
            }

            public function isPending(): bool
            {
                return false;
            }

            public function isSuccessful(): bool
            {
                return false;
            }

            public function isCancelled(): bool
            {
                return false;
            }
        };
    }

    public static function makeReceiptItem(array $parameters = []): BaseReceiptItem
    {
        $parameters['name'] ??= 'Information Services';
        $parameters['amount'] ??= 2.12;
        $parameters['currency'] ??= 'RUB';
        $parameters['quantity'] ??= 1;
        $parameters['unit'] ??= 'pc';
        return new class($parameters) extends BaseReceiptItem{};
    }
}
