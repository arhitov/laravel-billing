<?php

namespace Arhitov\LaravelBilling\Console\Commands;

use Arhitov\LaravelBilling\Models\Balance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

#[\Symfony\Component\Console\Attribute\AsCommand(name: 'billing:get-balance')]
class GetBalanceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:get-balance {owner_type} {owner_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Getting balance for the owner';

    /**
     * @return int
     * @throws \Illuminate\Validation\ValidationException
     */
    public function handle(): int
    {
        $input = Validator::make(
            $this->arguments(),
            [
                'owner_type' => ['required', 'string', 'max:255'],
                'owner_id'   => ['required', 'int', 'min:1'],
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

        $this->info("Balance: {$balance->amount} {$balance->currency->value}");

        return self::SUCCESS;
    }
}
