<?php

namespace Tests\Feature;

use App\Enums\TradeStatus;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class TradeTest extends TestCase
{
    use RefreshDatabase;

    public function test_force_delete_throws_and_preserves_the_trade(): void
    {
        $trade = Trade::factory()->create();

        try {
            $trade->forceDelete();
            $this->fail('Force deleting a trade should throw an exception.');
        } catch (LogicException $exception) {
            $this->assertSame('Trades cannot be permanently deleted.', $exception->getMessage());
        }

        $this->assertModelExists($trade);
    }

    public function test_delete_soft_deletes_the_trade_without_removing_it(): void
    {
        $trade = Trade::factory()->create();

        $trade->delete();

        $this->assertSoftDeleted($trade);
    }

    public function test_published_by_scope_returns_only_created_trades_owned_by_user(): void
    {
        $owner = User::factory()->create();
        $publishedTrade = Trade::factory()->create([
            'owner_id' => $owner->id,
            'status' => TradeStatus::Created,
        ]);
        Trade::factory()->create([
            'owner_id' => $owner->id,
            'status' => TradeStatus::Draft,
        ]);
        Trade::factory()->create(['status' => TradeStatus::Created]);

        $publishedTradeIds = Trade::publishedBy($owner)->pluck('id')->all();

        $this->assertSame([$publishedTrade->id], $publishedTradeIds);
    }

    public function test_active_as_counterparty_scope_returns_negotiating_and_accepted_trades(): void
    {
        $counterparty = User::factory()->create();
        $negotiatingTrade = Trade::factory()->create([
            'counterparty_id' => $counterparty->id,
            'status' => TradeStatus::Negotiating,
        ]);
        $acceptedTrade = Trade::factory()->create([
            'counterparty_id' => $counterparty->id,
            'status' => TradeStatus::Accepted,
        ]);
        Trade::factory()->create([
            'counterparty_id' => $counterparty->id,
            'status' => TradeStatus::Created,
        ]);
        Trade::factory()->create(['status' => TradeStatus::Accepted]);

        $activeTradeIds = Trade::activeAsCounterparty($counterparty)->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$negotiatingTrade->id, $acceptedTrade->id], $activeTradeIds);
    }
}
