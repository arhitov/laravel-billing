<?php
/**
 * Billing module for laravel projects
 *
 * @link      https://github.com/arhitov/laravel-billing
 * @package   arhitov/laravel-billing
 * @license   MIT
 * @copyright Copyright (c) 2024, Alexander Arhitov, clgsru@gmail.com
 */

namespace Arhitov\LaravelBilling\Services\FiscalReceipt;

use Arhitov\LaravelBilling\Enums\ReceiptStateEnum;
use Arhitov\LaravelBilling\Models\Receipt;
use Arhitov\LaravelBilling\OmnireceiptGateway;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Omnireceipt\Common\Contracts\Http\ClientInterface;
use Omnireceipt\Common\Entities\Receipt as OmnireceiptReceipt;
use Symfony\Component\HttpFoundation\Request;

class FiscalReceiptCheckState
{
    protected OmnireceiptGateway $gateway;

    /** @var array<int, string> */
    protected array $operationUuid = [];

    public function __construct(protected string $gatewayName, ClientInterface $httpClient = null, Request $httpRequest = null)
    {
        $this->gateway = new OmnireceiptGateway($gatewayName, httpClient: $httpClient, httpRequest: $httpRequest);
    }

    public function useOperationUuid(string $operationUuid)
    {
        $this->operationUuid[] = $operationUuid;
    }

    public function countActive(): int
    {
        return $this->builder()->count();
    }

    public function execute(): void
    {
        // First we make a request to get a receipt from the API for the last X amount of time.
        try {
            $receiptList = $this->gateway->getGateway()->listReceipts([
                'date_from' => Carbon::now()->subDays(1)->startOfDay()->toString(),
                'date_to'   => Carbon::now()->endOfDay()->toString(),
            ]);
        } catch (\Omnireceipt\Common\Exceptions\Parameters\ParameterValidateException $exception) {
            dd(
                $exception->error
            );
        }

        $loaded = [];
        $successful = [];
        $receiptList->getList()->map(static function (OmnireceiptReceipt $receipt) use (&$loaded, &$successful) {
            if ($receipt->isSuccessful()) {
                $successful[] = $receipt->getId();
            } else {
                $loaded[] = $receipt->getId();
            }
        });

        // We look at the receipts in the database and change the state if necessary.
        $unknownReceipt = [];
        $this->builder()->chunk(100, function(Collection $receiptList) use ($loaded, $successful, &$unknownReceipt) {
            $receiptList->map(function(Receipt $receipt) use ($loaded, $successful, &$unknownReceipt) {
                $id = $receipt->getReceipt()->getId();
                if (in_array($id, $successful)) {
                    $receipt->changeStateOrFail(ReceiptStateEnum::Succeeded);
                } elseif (in_array($id, $loaded)) {
                    $receipt->changeStateOrFail(ReceiptStateEnum::Send);
                } elseif ($receipt->state->isPaid()) {
                    $response = $this->gateway->getGateway()->createReceipt($receipt->getReceipt());
                    if ($response->isSuccessful()) {
                        $receipt->changeStateOrFail(ReceiptStateEnum::Send);
                    }
                } else {
                    $unknownReceipt[] = $receipt;
                }
            });
        });

        //
        foreach ($unknownReceipt as $receipt) {
            $response = $this->gateway->getGateway()->detailsReceipt($receipt->getReceipt()->getId());
            if ($response->isSuccessful() and $omnireceipt = $response->getReceipt()) {
                if ($omnireceipt->isSuccessful()) {
                    $receipt->changeStateOrFail(ReceiptStateEnum::Succeeded);
                } else {
                    $receipt->changeState(ReceiptStateEnum::Send);
                }
            }
        }
    }

    protected function builder(): Builder
    {
        $builder = Receipt::query()
                          ->where('gateway', '=', $this->gatewayName)
                          ->whereIn('state', [
                              ReceiptStateEnum::Paid->value,
                              ReceiptStateEnum::Send->value,
                          ]);
        if (! empty($this->operationUuid)) {
            $builder->whereIn('operation_uuid', $this->operationUuid);
        }

        return $builder;
    }
}
