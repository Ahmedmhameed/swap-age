<?php

namespace App\Actions;

use App\Actions\Concerns\LocksTrade;
use App\Enums\TradeStatus;
use App\Models\Trade;
use App\Models\User;

class SyncTradeCategories
{
    use LocksTrade;

    /** @param list<int> $categoryIds */
    public function handle(Trade $trade, User $actor, array $categoryIds): Trade
    {
        return $this->withLockedTrade($trade, $actor, 'updateCategories', function (Trade $lockedTrade) use ($categoryIds): Trade {
            $this->assertTradeStatus($lockedTrade, [TradeStatus::Draft]);
            $lockedTrade->categories()->sync($categoryIds);

            return $lockedTrade;
        });
    }
}
