<?php

namespace App\Actions\Concerns;

use App\Enums\TradeStatus;
use App\Exceptions\DomainRuleViolation;
use App\Models\Trade;
use App\Models\User;
use App\Services\TradeTransitionService;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

trait LocksTrade
{
    protected function withLockedTrade(int|Trade $trade, User $actor, string $ability, Closure $operation): mixed
    {
        return DB::transaction(function () use ($trade, $actor, $ability, $operation): mixed {
            $lockedTrade = Trade::query()
                ->whereKey($trade instanceof Trade ? $trade->getKey() : $trade)
                ->lockForUpdate()
                ->firstOrFail();

            Gate::forUser($actor)->authorize($ability, $lockedTrade);

            return $operation($lockedTrade);
        });
    }

    /** @param list<TradeStatus> $allowed */
    protected function assertTradeStatus(Trade $trade, array $allowed): void
    {
        if (! in_array($trade->status, $allowed, true)) {
            throw new DomainRuleViolation('هذه العملية غير مسموحة في حالة الصفقة الحالية.');
        }
    }

    protected function transition(Trade $trade, TradeStatus $next, TradeTransitionService $transitions): void
    {
        $transitions->assertCanTransition($trade->status, $next);
        $trade->status = $next;
    }

    protected function recordVersionedChange(Trade $trade, User $actor): void
    {
        $trade->version++;
        $trade->last_changed_by_id = $actor->id;
        $trade->save();
    }
}
