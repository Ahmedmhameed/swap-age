<?php

namespace App\Actions;

use App\Actions\Concerns\LocksTrade;
use App\Enums\TradeStatus;
use App\Exceptions\DomainRuleViolation;
use App\Models\Trade;
use App\Models\User;

class UpdateTradeCash
{
    use LocksTrade;

    /** @param array{cash_amount_owner?: ?float, cash_amount_counterparty?: ?float, cash_currency?: ?string} $data */
    public function handle(Trade $trade, User $actor, array $data): Trade
    {
        return $this->withLockedTrade($trade, $actor, 'participate', function (Trade $lockedTrade) use ($actor, $data): Trade {
            $this->assertTradeStatus($lockedTrade, [TradeStatus::Created, TradeStatus::Negotiating]);

            if ($data === []) {
                throw new DomainRuleViolation('أدخل مبلغاً أو عملة لتحديث شروط النقد.');
            }

            foreach (['cash_amount_owner', 'cash_amount_counterparty', 'cash_currency'] as $field) {
                if (array_key_exists($field, $data)) {
                    $lockedTrade->{$field} = $data[$field];
                }
            }

            $this->recordVersionedChange($lockedTrade, $actor);

            return $lockedTrade;
        });
    }
}
