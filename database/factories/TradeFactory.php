<?php

namespace Database\Factories;

use App\Enums\TradeStatus;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trade>
 */
class TradeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'counterparty_id' => null,
            'status' => fake()->randomElement(TradeStatus::cases()),
            'version' => 1,
            'cash_amount_owner' => fake()->randomFloat(2, 0, 5000),
            'cash_amount_counterparty' => fake()->randomFloat(2, 0, 5000),
            'cash_currency' => 'USD',
            'confirmed_by_owner_at' => null,
            'confirmed_by_counterparty_at' => null,
            'deadline_at' => null,
            'completed_type' => null,
            'cancelled_at' => null,
        ];
    }
}
