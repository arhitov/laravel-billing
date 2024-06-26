<?php

namespace Arhitov\LaravelBilling\Console\Commands;

use Arhitov\LaravelBilling\Exceptions\BalanceException;
use Arhitov\LaravelBilling\Exceptions\LaravelBillingException;
use Arhitov\LaravelBilling\Exceptions\OperationException;
use Arhitov\LaravelBilling\Increase;
use Arhitov\LaravelBilling\Models\Balance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

#[\Symfony\Component\Console\Attribute\AsCommand(name: 'billing:increase-balance')]
class IncreaseBalanceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:increase-balance {owner_type} {owner_id} {amount}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Increasing the owner\'s balance';

    /**
     * @return int
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function handle(): int
    {
        $input = Validator::make(
            $this->arguments(),
            [
                'owner_type' => ['required', 'string', 'max:255'],
                'owner_id'   => ['required', 'int', 'min:1'],
                'amount'     => ['required', 'billing_amount', 'min:0'],
            ],
        )->validate();

        /** @var Balance $balance */
        $balance = Balance::query()
                          ->where('owner_type', '=', $input['owner_type'])
                          ->where('owner_id', '=', $input['owner_id'])
                          ->first();

        if (! $balance) {
            $this->warn('Balance not found');
            return self::FAILURE;
        }

        $balanceBefore = $balance->amount;

        try {
            (new Increase(
                $balance,
                $input['amount'],
            ))->executeOrFail();
        } catch (BalanceException $e) {
            $this->error("Balance error : {$e->getMessage()}");
            return self::FAILURE;
        } catch (OperationException $e) {
            $this->error("Operation error : {$e->getMessage()}");
            return self::FAILURE;
        } catch (LaravelBillingException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info("Successful");
        $this->info("Balance before: {$balanceBefore} {$balance->currency->value}");
        $this->info("Balance after: {$balance->amount} {$balance->currency->value}");

        return self::SUCCESS;
    }
}
