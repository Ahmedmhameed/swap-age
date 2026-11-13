<?php

namespace Database\Factories;

use App\Enums\EvidenceKind;
use App\Models\Evidence;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Evidence>
 */
class EvidenceFactory extends Factory
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
            'trade_item_id' => null,
            'kind' => fake()->randomElement(EvidenceKind::cases()),
            'file_path' => fake()->uuid() . '.jpg',
            'mime_type' => 'image/jpeg',
            'size' => fake()->numberBetween(1, 5000000),
            'uploaded_by_id' => User::factory(),
        ];
    }
}
