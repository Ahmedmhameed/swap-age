<?php

namespace Database\Factories;

use App\Enums\TradeDirection;
use App\Models\Product;
use App\Models\Trade;
use App\Models\TradeItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TradeItem>
 */
class TradeItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trade_id' => Trade::factory(),
            'product_id' => Product::factory(),
            'direction' => fake()->randomElement(TradeDirection::cases()),
            'quantity' => fake()->numberBetween(1, 5),
            'condition' => fake()->randomElement(['new', 'good', 'fair', 'poor']),
            'description' => fake()->paragraph(),
        ];
    }
}
