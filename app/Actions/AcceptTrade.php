<?php

namespace App\Actions;

use App\Actions\Concerns\LocksTrade;
use App\Enums\EvidenceKind;
use App\Enums\TradeStatus;
use App\Exceptions\DomainRuleViolation;
use App\Models\Trade;
use App\Models\User;
use App\Services\TradeLimitService;
use App\Services\TradeTransitionService;

class AcceptTrade
{
    use LocksTrade;

    public function handle(Trade $trade, User $actor, int $acceptedVersion, TradeLimitService $limits, TradeTransitionService $transitions): Trade
    {
        return $this->withLockedTrade($trade, $actor, 'accept', function (Trade $lockedTrade) use ($actor, $acceptedVersion, $limits, $transitions): Trade {
            if (! in_array($lockedTrade->status, [TradeStatus::Created, TradeStatus::Negotiating], true)) {
                throw new DomainRuleViolation('لا يمكن قبول الصفقة في حالتها الحالية.');
            }

            if ($lockedTrade->version !== $acceptedVersion) {
                throw new DomainRuleViolation('تغيرت نسخة الصفقة؛ راجع الشروط الحالية قبل القبول.');
            }

            $isFirstCounterparty = $lockedTrade->status === TradeStatus::Created && $lockedTrade->counterparty_id === null;
            $expectedAcceptorId = $lockedTrade->last_changed_by_id === $lockedTrade->owner_id
                ? $lockedTrade->counterparty_id
                : ($lockedTrade->last_changed_by_id === null ? $lockedTrade->counterparty_id : $lockedTrade->owner_id);

            if (
                ($isFirstCounterparty && $actor->id === $lockedTrade->owner_id)
                || (! $isFirstCounterparty && $actor->id !== $expectedAcceptorId)
            ) {
                throw new DomainRuleViolation('يجب أن يقبل الطرف الآخر آخر تعديل على الصفقة.');
            }

            $counterparty = $lockedTrade->owner_id === $actor->id ? $lockedTrade->counterparty : $actor;

            if (! $counterparty || ! $limits->canParticipate($counterparty, $lockedTrade)) {
                throw new DomainRuleViolation('وصل الطرف الآخر إلى الحد الأقصى للصفقات النشطة.');
            }

            $items = $lockedTrade->items()->with('evidence')->get();

            if ($items->isEmpty()) {
                throw new DomainRuleViolation('لا يمكن قبول صفقة بلا عناصر.');
            }

            foreach ($items as $item) {
                if (! $item->evidence->contains(fn ($evidence): bool => $evidence->kind === EvidenceKind::ProductPhoto)) {
                    throw new DomainRuleViolation('يجب إضافة صورة منتج لكل عنصر قبل قبول الصفقة.');
                }
            }

            $this->transition($lockedTrade, TradeStatus::Accepted, $transitions);
            $lockedTrade->counterparty_id = $counterparty->id;
            $lockedTrade->save();

            return $lockedTrade;
        });
    }
}
