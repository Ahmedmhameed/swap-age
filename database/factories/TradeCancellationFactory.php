<?php

namespace Database\Factories;

use App\Enums\CancellationReason;
use App\Enums\TradeStatus;
use App\Models\Trade;
use App\Models\TradeCancellation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TradeCancellation>
 */
class TradeCancellationFactory extends Factory
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
            'cancelled_by_id' => User::factory(),
            'reason' => fake()->randomElement(CancellationReason::cases()),
            'note' => fake()->optional()->sentence(),
            'previous_status' => fake()->randomElement(TradeStatus::cases())->value,
        ];
    }
}
