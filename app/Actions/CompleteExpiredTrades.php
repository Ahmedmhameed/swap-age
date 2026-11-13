<?php

namespace App\Actions;

use App\Enums\TradeStatus;
use App\Models\Trade;
use App\Models\User;
use App\Services\TradeDeadlineService;
use App\Services\TradeTransitionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CompleteExpiredTrades
{
    public function handle(TradeDeadlineService $deadlines, TradeTransitionService $transitions): int
    {
        $tradeIds = Trade::query()
            ->whereNotNull('deadline_at')
            ->where('deadline_at', '<=', now())
            ->pluck('id');
        $tradeIds = Trade::query()->pluck('id');
        $completed = 0;

        foreach ($tradeIds as $tradeId) {
            DB::transaction(function () use ($tradeId, $deadlines, $transitions, &$completed): void {
                $lockedTrade = Trade::query()->whereKey($tradeId)->lockForUpdate()->first();

                if (! $lockedTrade) {
                    return;
                }

                if ($lockedTrade->status !== TradeStatus::Accepted || ! $deadlines->hasExpired($lockedTrade)) {
                    return;
                }

                $owner = User::query()->findOrFail($lockedTrade->owner_id);
                Gate::forUser($owner)->authorize('completeExpired', $lockedTrade);
                $this->transition($lockedTrade, TradeStatus::Completed, $transitions);
                $lockedTrade->completed_type = 'auto_after_timeout';
                $lockedTrade->save();
                $completed++;
            });
        }

        return $completed;
    }

    private function transition(Trade $trade, TradeStatus $next, TradeTransitionService $transitions): void
    {
        $transitions->assertCanTransition($trade->status, $next);
        $trade->status = $next;
    }
}
