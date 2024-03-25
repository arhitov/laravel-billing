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
}
