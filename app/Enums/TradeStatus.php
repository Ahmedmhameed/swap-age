<?php

namespace App\Enums;

enum TradeStatus: string
{
    case Draft = 'draft';
    case Created = 'created';
    case Negotiating = 'negotiating';
    case Accepted = 'accepted';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
