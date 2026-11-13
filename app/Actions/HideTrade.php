<?php

namespace App\Actions;

use App\Actions\Concerns\LocksTrade;
use App\Enums\TradeStatus;
use App\Exceptions\DomainRuleViolation;
use App\Models\Trade;
use App\Models\User;

class HideTrade
{
    use LocksTrade;

    public function handle(Trade $trade, User $actor): void
    {
        $this->withLockedTrade($trade, $actor, 'delete', function (Trade $lockedTrade): void {
            if (! in_array($lockedTrade->status, [TradeStatus::Draft, TradeStatus::Completed, TradeStatus::Cancelled], true)) {
                throw new DomainRuleViolation('ألغِ الصفقة النشطة قبل إخفائها.');
            }

            $lockedTrade->delete();
        });
    }
}
