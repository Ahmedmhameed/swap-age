<?php

namespace App\Actions;

use App\Enums\TradeStatus;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CreateDraftTrade
{
    /** @param array<string, mixed> $data */
    public function handle(User $owner, array $data = []): Trade
    {
        return DB::transaction(function () use ($owner, $data): Trade {
            Gate::forUser($owner)->authorize('create', Trade::class);

            return Trade::create([
                'owner_id' => $owner->id,
                'status' => TradeStatus::Draft,
                'version' => 1,
                'cash_amount_owner' => $data['cash_amount_owner'] ?? null,
                'cash_amount_counterparty' => $data['cash_amount_counterparty'] ?? null,
                'cash_currency' => $data['cash_currency'] ?? null,
            ]);
        });
    }
}
