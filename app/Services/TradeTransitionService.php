<?php

namespace App\Services;

use App\Enums\TradeStatus;
use App\Exceptions\DomainRuleViolation;

class TradeTransitionService
{
    /** @var array<string, list<TradeStatus>> */
    private const TRANSITIONS = [
        'draft' => [TradeStatus::Created],
        'created' => [TradeStatus::Negotiating, TradeStatus::Accepted, TradeStatus::Cancelled],
        'negotiating' => [TradeStatus::Created, TradeStatus::Accepted, TradeStatus::Cancelled],
        'accepted' => [TradeStatus::Completed, TradeStatus::Cancelled],
        'completed' => [],
        'cancelled' => [],
    ];

    public function assertCanTransition(TradeStatus $current, TradeStatus $next): void
    {
        if (! in_array($next, self::TRANSITIONS[$current->value] ?? [], true)) {
            throw new DomainRuleViolation("لا يمكن نقل الصفقة من حالة {$current->value} إلى {$next->value}.");
        }
    }
}
