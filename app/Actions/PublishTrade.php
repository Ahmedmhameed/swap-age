<?php

namespace App\Actions;

use App\Actions\Concerns\LocksTrade;
use App\Enums\EvidenceKind;
use App\Enums\TradeDirection;
use App\Enums\TradeStatus;
use App\Exceptions\DomainRuleViolation;
use App\Models\Trade;
use App\Models\User;
use App\Services\TradeLimitService;
use App\Services\TradeTransitionService;

class PublishTrade
{
    use LocksTrade;

    public function handle(Trade $trade, User $actor, TradeLimitService $limits, TradeTransitionService $transitions): Trade
    {
        return $this->withLockedTrade($trade, $actor, 'publish', function (Trade $lockedTrade) use ($actor, $limits, $transitions): Trade {
            $this->transition($lockedTrade, TradeStatus::Created, $transitions);

            if (! $limits->canPublish($actor)) {
                throw new DomainRuleViolation('وصلت إلى الحد الأقصى للصفقات المنشورة.');
            }

            $items = $lockedTrade->items()->with('evidence')->get();

            if ($items->isEmpty() || ! $items->contains(fn ($item): bool => $item->direction === TradeDirection::OwnerGives)) {
                throw new DomainRuleViolation('يجب إضافة عنصر واحد على الأقل مما يقدمه مالك الصفقة.');
            }

            foreach ($items as $item) {
                if (blank($item->description) || ! $item->evidence->contains(fn ($evidence): bool => $evidence->kind === EvidenceKind::ProductPhoto)) {
                    throw new DomainRuleViolation('يجب أن يحتوي كل عنصر على وصف وصورة منتج واحدة على الأقل.');
                }
            }

            $lockedTrade->last_changed_by_id = $actor->id;
            $lockedTrade->save();

            return $lockedTrade;
        });
    }
}
