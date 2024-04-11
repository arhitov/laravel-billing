<?php

namespace Arhitov\LaravelBilling\Enums;

enum CreditCardStateEnum: string
{
    case Created = 'created';
    case Active = 'active';
    case Inactive = 'inactive';
    case Insolvent = 'insolvent';
    case Invalid = 'invalid';
    case Locked = 'locked';
}
