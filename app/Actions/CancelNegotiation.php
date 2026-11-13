<?php

namespace App\Actions;

use App\Actions\Concerns\LocksTrade;
use App\Enums\TradeStatus;
use App\Models\Trade;
use App\Models\User;
use App\Services\TradeTransitionService;

class CancelNegotiation
{
    use LocksTrade;

    public function handle(Trade $trade, User $actor, TradeTransitionService $transitions): Trade
    {
        return $this->withLockedTrade($trade, $actor, 'participate', function (Trade $lockedTrade) use ($actor, $transitions): Trade {
            $this->transition($lockedTrade, TradeStatus::Created, $transitions);
            $lockedTrade->counterparty_id = null;
            $this->recordVersionedChange($lockedTrade, $actor);

            return $lockedTrade;
        });
    }
}
