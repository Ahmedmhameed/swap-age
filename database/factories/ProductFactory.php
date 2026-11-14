<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'brand' => fake()->company(),
            'name' => fake()->words(2, true),
            'model' => fake()->optional()->bothify('??-###'),
            'version' => fake()->optional()->numerify('#.#'),
            'specs' => ['color' => fake()->safeColorName()],
            'is_approved' => fake()->boolean(),
        ];
    }
}
