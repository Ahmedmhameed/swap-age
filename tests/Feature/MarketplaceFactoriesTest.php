<?php

namespace Tests\Feature;

use App\Models\Evidence;
use App\Models\Product;
use App\Models\Review;
use App\Models\Trade;
use App\Models\TradeCancellation;
use App\Models\TradeItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceFactoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_marketplace_factories_create_valid_model_records(): void
    {
        $product = Product::factory()->create();
        $trade = Trade::factory()->create();
        $tradeItem = TradeItem::factory()->create();
        $evidence = Evidence::factory()->create();
        $cancellation = TradeCancellation::factory()->create();
        $review = Review::factory()->create();

        $this->assertModelExists($product);
        $this->assertModelExists($trade);
        $this->assertModelExists($tradeItem);
        $this->assertModelExists($evidence);
        $this->assertModelExists($cancellation);
        $this->assertModelExists($review);
        $this->assertNotSame($review->reviewer_id, $review->reviewed_id);
    }
}
