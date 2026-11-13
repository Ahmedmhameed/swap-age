<?php

namespace Tests\Feature;

use App\Enums\TradeStatus;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_a_draft_through_the_trade_controller(): void
    {
        $owner = User::factory()->create();
        $response = $this->actingAs($owner)->post(route('trades.store'), ['cash_currency' => 'USD']);

        $trade = Trade::query()->sole();
        $response->assertRedirect(route('trades.show', $trade));
        $this->assertSame(TradeStatus::Draft, $trade->status);
    }

    public function test_trade_detail_page_renders_for_a_party(): void
    {
        $trade = Trade::factory()->create(['status' => TradeStatus::Draft]);

        $response = $this->actingAs($trade->owner)->get(route('trades.show', $trade));

        $response->assertOk()->assertSee("صفقة #{$trade->id}");
    }

    public function test_domain_rule_violation_returns_a_validation_error_redirect(): void
    {
        $owner = User::factory()->create();
        $trade = Trade::factory()->create(['owner_id' => $owner->id, 'status' => TradeStatus::Draft]);

        $response = $this->actingAs($owner)->post(route('trades.publish', $trade));

        $response->assertSessionHasErrors('trade');
        $this->assertSame(TradeStatus::Draft, $trade->fresh()->status);
    }
}
