<?php

namespace Database\Factories;

use App\Models\Review;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
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
            'reviewer_id' => User::factory(),
            'reviewed_id' => User::factory(),
            'rating' => fake()->numberBetween(1, 5),
            'comment_text' => fake()->optional()->sentence(),
        ];
    }
}
