<?php

namespace App\Models;

use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

#[Fillable(['trade_id', 'reviewer_id', 'reviewed_id', 'rating', 'comment_text'])]
class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory;

    public $timestamps = false;

    public function trade(): BelongsTo
    {
        return $this->belongsTo(Trade::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function reviewed(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_id');
    }

    protected static function booted(): void
    {
        static::saving(function (Review $review): void {
            if ($review->rating < 1 || $review->rating > 5) {
                throw new InvalidArgumentException('Review ratings must be between 1 and 5.');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
