<?php

namespace App\Console\Commands;

use App\Actions\CompleteExpiredTrades;
use App\Services\TradeDeadlineService;
use App\Services\TradeTransitionService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('trades:complete-expired')]
#[Description('Complete accepted trades whose confirmation deadline has expired')]
class CompleteExpiredTradesCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(CompleteExpiredTrades $action): int
    {
        $count = $action->handle(app(TradeDeadlineService::class), app(TradeTransitionService::class));
        $this->info("Completed {$count} expired trades.");

        return self::SUCCESS;
    }
}
