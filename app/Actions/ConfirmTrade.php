<?php

namespace App\Actions;

use App\Actions\Concerns\LocksTrade;
use App\Enums\TradeStatus;
use App\Exceptions\DomainRuleViolation;
use App\Models\Trade;
use App\Models\User;
use App\Services\TradeDeadlineService;
use App\Services\TradeTransitionService;
use Illuminate\Support\Carbon;

class ConfirmTrade
{
    use LocksTrade;

    public function handle(Trade $trade, User $actor, TradeDeadlineService $deadlines, TradeTransitionService $transitions): Trade
    {
        return $this->withLockedTrade($trade, $actor, 'confirm', function (Trade $lockedTrade) use ($actor, $deadlines, $transitions): Trade {
            $this->assertTradeStatus($lockedTrade, [TradeStatus::Accepted]);

            if ($lockedTrade->owner_id === $actor->id) {
                if ($lockedTrade->confirmed_by_owner_at !== null) {
                    throw new DomainRuleViolation('سبق أن أكدت إتمام هذه الصفقة.');
                }

                $lockedTrade->confirmed_by_owner_at = Carbon::now();
            } elseif ($lockedTrade->counterparty_id === $actor->id) {
                if ($lockedTrade->confirmed_by_counterparty_at !== null) {
                    throw new DomainRuleViolation('سبق أن أكدت إتمام هذه الصفقة.');
                }

                $lockedTrade->confirmed_by_counterparty_at = Carbon::now();
            } else {
                throw new DomainRuleViolation('يجب أن تكون طرفاً في الصفقة لتأكيد إتمامها.');
            }

            if ($lockedTrade->confirmed_by_owner_at && $lockedTrade->confirmed_by_counterparty_at) {
                $this->transition($lockedTrade, TradeStatus::Completed, $transitions);
                $lockedTrade->completed_type = 'confirmed_by_both';
                $lockedTrade->deadline_at = null;
            } elseif ($lockedTrade->deadline_at === null) {
                $lockedTrade->deadline_at = $deadlines->deadlineFromNow();
            }

            $lockedTrade->save();

            return $lockedTrade;
        });
    }
}
