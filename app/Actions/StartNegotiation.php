<?php

namespace App\Actions;

use App\Actions\Concerns\LocksTrade;
use App\Enums\TradeStatus;
use App\Exceptions\DomainRuleViolation;
use App\Models\Trade;
use App\Models\User;
use App\Services\TradeLimitService;
use App\Services\TradeTransitionService;

class StartNegotiation
{
    use LocksTrade;

    public function handle(Trade $trade, User $counterparty, TradeLimitService $limits, TradeTransitionService $transitions): Trade
    {
        return $this->withLockedTrade($trade, $counterparty, 'negotiate', function (Trade $lockedTrade) use ($counterparty, $limits, $transitions): Trade {
            $this->transition($lockedTrade, TradeStatus::Negotiating, $transitions);

            if ($lockedTrade->owner_id === $counterparty->id) {
                throw new DomainRuleViolation('لا يمكن لمالك الصفقة بدء التفاوض عليها بنفسه.');
            }

            if ($lockedTrade->counterparty_id !== null && $lockedTrade->counterparty_id !== $counterparty->id) {
                throw new DomainRuleViolation('هذه الصفقة مرتبطة بطرف آخر بالفعل.');
            }

            if (! $limits->canParticipate($counterparty)) {
                throw new DomainRuleViolation('وصلت إلى الحد الأقصى للصفقات النشطة.');
            }

            $lockedTrade->counterparty_id = $counterparty->id;
            $lockedTrade->last_changed_by_id = $counterparty->id;
            $lockedTrade->save();

            return $lockedTrade;
        });
    }
}
