<?php

namespace App\Models;

use App\Enums\TradeStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'owner_id',
    'counterparty_id',
    'status',
    'version',
    'cash_amount_owner',
    'cash_amount_counterparty',
    'cash_currency',
    'confirmed_by_owner_at',
    'confirmed_by_counterparty_at',
    'deadline_at',
    'completed_type',
    'cancelled_at',
])]
class Trade extends Model
{
    use SoftDeletes;

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function counterparty(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counterparty_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TradeItem::class);
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(Evidence::class);
    }

    public function cancellation(): HasOne
    {
        return $this->hasOne(TradeCancellation::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    protected static function booted(): void
    {
        static::forceDeleting(function (Trade $trade): void {
            foreach (Evidence::where('trade_id', $trade->getKey())->get() as $evidence) {
                $evidence->delete();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'status' => TradeStatus::class,
            'version' => 'integer',
            'cash_amount_owner' => 'decimal:2',
            'cash_amount_counterparty' => 'decimal:2',
            'confirmed_by_owner_at' => 'datetime',
            'confirmed_by_counterparty_at' => 'datetime',
            'deadline_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }
}
