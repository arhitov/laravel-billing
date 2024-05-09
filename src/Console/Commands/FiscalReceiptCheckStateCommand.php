<?php
/**
 * Billing module for laravel projects
 *
 * @link      https://github.com/arhitov/laravel-billing
 * @package   arhitov/laravel-billing
 * @license   MIT
 * @copyright Copyright (c) 2024, Alexander Arhitov, clgsru@gmail.com
 */

namespace Arhitov\LaravelBilling\Console\Commands;

use Arhitov\LaravelBilling\Services\FiscalReceipt\FiscalReceiptCheckState;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

#[\Symfony\Component\Console\Attribute\AsCommand(name: 'billing:fiscal-receipt-check-state')]
class FiscalReceiptCheckStateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:fiscal-receipt-check-state {--operation_uuid=} {--gateway=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the fiscal receipt state and adjust it.';

    /**
     * @return int
     * @throws \Illuminate\Validation\ValidationException
     */
    public function handle(): int
    {
        $input = Validator::make(
            $this->options(),
            [
                'operation_uuid' => ['nullable', 'string'],
                'gateway'        => ['nullable', 'string', Rule::in(array_keys(config('billing.omnireceipt_gateway.gateways')))],
            ],
        )->validate();

        $gatewayName = $input['gateway'] ?? config('billing.omnireceipt_gateway.default');
        $operationUuid = $input['operation_uuid'] ?? null;

        $service = new FiscalReceiptCheckState(
            $gatewayName
        );
        if ($operationUuid) {
            $service->useOperationUuid($operationUuid);
        }

        $service->execute();

        return self::SUCCESS;
    }
}
