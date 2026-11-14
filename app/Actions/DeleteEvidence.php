<?php

namespace App\Actions;

use App\Actions\Concerns\LocksTrade;
use App\Enums\TradeStatus;
use App\Exceptions\DomainRuleViolation;
use App\Models\Evidence;
use App\Models\Trade;
use App\Models\User;

class DeleteEvidence
{
    use LocksTrade;

    public function handle(Evidence $evidence, User $actor): void
    {
        $this->withLockedTrade((int) $evidence->trade_id, $actor, 'deleteEvidence', function (Trade $trade) use ($evidence, $actor): void {
            $this->assertTradeStatus($trade, [TradeStatus::Draft, TradeStatus::Created, TradeStatus::Negotiating]);
            $lockedEvidence = Evidence::query()->whereKey($evidence->getKey())->lockForUpdate()->first();

            if (!$lockedEvidence || $lockedEvidence->trade_id !== $trade->id) {
                throw new DomainRuleViolation('الدليل لا يتبع هذه الصفقة.');
            }

            if ($lockedEvidence->uploaded_by_id !== $actor->id) {
                throw new DomainRuleViolation('لا يمكن حذف دليل رفعه طرف آخر.');
            }

            $lockedEvidence->delete();

            if (in_array($trade->status, [TradeStatus::Created, TradeStatus::Negotiating], true)) {
                $this->recordVersionedChange($trade, $actor);
            }
        });
    }
}
