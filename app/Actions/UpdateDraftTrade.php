<?php

namespace App\Actions;

use App\Actions\Concerns\LocksTrade;
use App\Enums\TradeStatus;
use App\Exceptions\DomainRuleViolation;
use App\Models\Product;
use App\Models\Trade;
use App\Models\TradeItem;
use App\Models\User;
use Illuminate\Support\Arr;

class UpdateDraftTrade
{
    use LocksTrade;

    /** @param array<string, mixed> $data */
    public function handle(Trade $trade, User $actor, array $data): Trade
    {
        return $this->withLockedTrade($trade, $actor, 'update', function (Trade $lockedTrade) use ($data): Trade {
            $this->assertTradeStatus($lockedTrade, [TradeStatus::Draft]);

            foreach (['cash_amount_owner', 'cash_amount_counterparty', 'cash_currency'] as $field) {
                if (array_key_exists($field, $data)) {
                    $lockedTrade->{$field} = $data[$field];
                }
            }

            if (isset($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    if (! isset($itemData['product_id'], $itemData['direction'], $itemData['condition'], $itemData['description'])) {
                        throw new DomainRuleViolation('بيانات العنصر غير مكتملة.');
                    }

                    if (! Product::query()->whereKey($itemData['product_id'])->where('is_approved', true)->exists()) {
                        throw new DomainRuleViolation('لا يمكن إضافة منتج غير معتمد إلى الصفقة.');
                    }

                    if (isset($itemData['id'])) {
                        $item = TradeItem::query()
                            ->where('trade_id', $lockedTrade->id)
                            ->whereKey($itemData['id'])
                            ->lockForUpdate()
                            ->first();

                        if (! $item) {
                            throw new DomainRuleViolation('العنصر لا يتبع هذه الصفقة.');
                        }

                        $item->fill(Arr::only($itemData, ['product_id', 'direction', 'quantity', 'condition', 'description']))->save();
                    } else {
                        $lockedTrade->items()->create(Arr::only($itemData, ['product_id', 'direction', 'quantity', 'condition', 'description']));
                    }
                }
            }

            $lockedTrade->save();

            return $lockedTrade;
        });
    }
}
