<?php

namespace App\Actions;

use App\Enums\TradeStatus;
use App\Models\Trade;
use App\Services\TradeDeadlineService;
use App\Services\TradeTransitionService;
use Illuminate\Support\Facades\DB;

class CompleteExpiredTrades
{
    public function handle(TradeDeadlineService $deadlines, TradeTransitionService $transitions): int
    {
        $completed = 0;

        Trade::query()
            ->where('status', TradeStatus::Accepted)
            ->whereNotNull('deadline_at')
            ->where('deadline_at', '<=', now())
            ->chunkById(100, function ($trades) use ($deadlines, $transitions, &$completed): void {
                foreach ($trades as $trade) {
                    DB::transaction(function () use ($trade, $deadlines, $transitions, &$completed): void {
                        $lockedTrade = Trade::query()->whereKey($trade->id)->lockForUpdate()->first();

                        if (!$lockedTrade) {
                            return;
                        }

                        if ($lockedTrade->status !== TradeStatus::Accepted || !$deadlines->hasExpired($lockedTrade)) {
                            return;
                        }

                        $this->transition($lockedTrade, TradeStatus::Completed, $transitions);
                        $lockedTrade->completed_type = 'auto_after_timeout';
                        $lockedTrade->save();
                        $completed++;
                    });
                }
            });

        return $completed;
    }

    private function transition(Trade $trade, TradeStatus $next, TradeTransitionService $transitions): void
    {
        $transitions->assertCanTransition($trade->status, $next);
        $trade->status = $next;
    }
}
