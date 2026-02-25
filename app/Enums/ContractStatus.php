<?php

namespace App\Enums;

enum ContractStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Expired = 'expired';
    case Terminated = 'terminated';
}
