<?php

namespace Arhitov\LaravelBilling;

use Arhitov\LaravelBilling\Contracts\BillableInterface;
use Arhitov\LaravelBilling\Models\Balance;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RootBalance
{
    private array $setting;

    public function __construct(array $setting = null)
    {
        $this->setting = $setting ?? config('billing.root_owner');
    }

    /**
     * @return BillableInterface
     * @throws ModelNotFoundException<\Illuminate\Database\Eloquent\Model>
     */
    public function getOwner(): BillableInterface
    {
        $classOwner = $this->setting['owner_type'];
        $id = $this->setting['owner_id'];

        /** @var BillableInterface|null $owner */
        $owner = $classOwner::find($id);

        if (is_null($owner)) {
            if ($createModelData = $this->setting['create_model_data']) {
                $createModelData['id'] = $id;
                $owner = $classOwner::make($createModelData);
                $owner->saveOrFail();
            } else {
                throw (new ModelNotFoundException)->setModel(
                    $classOwner, $id
                );
            }
        }

        return $owner;
    }

    public function getBalance(): Balance
    {
        return $this->getOwner()->getBalanceOrCreate();
    }
}
