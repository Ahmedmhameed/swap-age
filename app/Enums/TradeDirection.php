<?php

namespace App\Enums;

enum TradeDirection: string
{
    case OwnerGives = 'owner_gives';
    case OwnerReceives = 'owner_receives';
}
