<?php

namespace Arhitov\LaravelBilling\Enums;

enum OperationStateEnum: string
{
    case Created = 'created';
    case Pending = 'pending';
    case WaitingForCapture = 'waiting_for_capture';
    case Succeeded = 'succeeded';
    case Canceled = 'canceled';
    case Errored = 'errored';
    case Refund = 'refund';

    public function isActive(): bool
    {
        return in_array($this->value, [
            OperationStateEnum::Created->value,
            OperationStateEnum::Pending->value,
            OperationStateEnum::WaitingForCapture->value,
        ]);
    }

    public function isPaid(): bool
    {
        return in_array($this->value, [
            OperationStateEnum::WaitingForCapture->value,
            OperationStateEnum::Succeeded->value,
        ]);
    }

    public function isSucceeded(): bool
    {
        return $this->value === OperationStateEnum::Succeeded->value;
    }
}
