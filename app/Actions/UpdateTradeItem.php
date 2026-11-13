<?php

namespace App\Actions;

use App\Actions\Concerns\LocksTrade;
use App\Enums\TradeStatus;
use App\Exceptions\DomainRuleViolation;
use App\Models\Trade;
use App\Models\TradeItem;
use App\Models\User;

class UpdateTradeItem
{
    use LocksTrade;

    /** @param array{quantity?: int, condition?: string, description?: string} $data */
    public function handle(TradeItem $item, User $actor, array $data): TradeItem
    {
        return $this->withLockedTrade((int) $item->trade_id, $actor, 'participate', function (Trade $trade) use ($item, $data, $actor): TradeItem {
            $this->assertTradeStatus($trade, [TradeStatus::Draft, TradeStatus::Created]);
            $lockedItem = TradeItem::query()
                ->where('trade_id', $trade->id)
                ->whereKey($item->getKey())
                ->lockForUpdate()
                ->first();

            if (! $lockedItem) {
                throw new DomainRuleViolation('العنصر لا يتبع هذه الصفقة.');
            }

            $lockedItem->fill($data)->save();

            if ($trade->status === TradeStatus::Created) {
                $this->recordVersionedChange($trade, $actor);
            }

            return $lockedItem;
        });
    }
}
