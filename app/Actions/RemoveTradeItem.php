<?php

namespace App\Actions;

use App\Actions\Concerns\LocksTrade;
use App\Enums\TradeStatus;
use App\Exceptions\DomainRuleViolation;
use App\Models\Trade;
use App\Models\TradeItem;
use App\Models\User;

class RemoveTradeItem
{
    use LocksTrade;

    public function handle(TradeItem $item, User $actor): void
    {
        $this->withLockedTrade((int) $item->trade_id, $actor, 'participate', function (Trade $trade) use ($item, $actor): void {
            $this->assertTradeStatus($trade, [TradeStatus::Created, TradeStatus::Negotiating]);
            $lockedItem = TradeItem::query()
                ->where('trade_id', $trade->id)
                ->whereKey($item->getKey())
                ->lockForUpdate()
                ->first();

            if (! $lockedItem) {
                throw new DomainRuleViolation('العنصر لا يتبع هذه الصفقة.');
            }

            $lockedItem->delete();
            $this->recordVersionedChange($trade, $actor);
        });
    }
}
