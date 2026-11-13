<?php

namespace Tests\Feature;

use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    public static function invalidRatings(): array
    {
        return [
            'below minimum' => [0],
            'above maximum' => [6],
        ];
    }

    #[DataProvider('invalidRatings')]
    public function test_saving_a_review_rejects_ratings_outside_the_allowed_range(int $rating): void
    {
        $this->expectException(InvalidArgumentException::class);

        Review::factory()->create(['rating' => $rating]);
    }
}
