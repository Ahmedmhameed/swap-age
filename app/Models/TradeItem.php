<?php

namespace App\Models;

use App\Enums\TradeDirection;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['trade_id', 'product_id', 'direction', 'quantity', 'condition', 'description'])]
class TradeItem extends Model
{
    public function trade(): BelongsTo
    {
        return $this->belongsTo(Trade::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(Evidence::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (TradeItem $tradeItem): void {
            foreach ($tradeItem->evidence as $evidence) {
                $evidence->delete();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'direction' => TradeDirection::class,
            'quantity' => 'integer',
        ];
    }
}
