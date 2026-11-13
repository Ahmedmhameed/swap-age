<?php

namespace App\Services;

use App\Models\Trade;
use Illuminate\Support\Carbon;

class TradeDeadlineService
{
    public function deadlineFromNow(): Carbon
    {
        return Carbon::now()->addDays(10);
    }

    public function hasExpired(Trade $trade): bool
    {
        return $trade->deadline_at !== null && $trade->deadline_at->lessThanOrEqualTo(now());
    }
}
