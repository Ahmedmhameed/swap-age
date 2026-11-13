<?php

namespace App\Models;

use App\Enums\TradeStatus;
use App\Models\Builders\TradeBuilder;
use Database\Factories\TradeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as QueryBuilder;
use LogicException;

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
    /** @use HasFactory<TradeFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @param  QueryBuilder  $query
     */
    public function newEloquentBuilder($query): TradeBuilder
    {
        return new TradeBuilder($query);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function counterparty(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counterparty_id');
    }

    public function lastChangedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_changed_by_id');
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

    public function forceDelete(): ?bool
    {
        throw new LogicException('Trades cannot be permanently deleted.');
    }

    /**
     * @param  Builder<Trade>  $query
     * @return Builder<Trade>
     */
    public function scopePublishedBy(Builder $query, User $user): Builder
    {
        return $query
            ->where('owner_id', $user->getKey())
            ->where('status', TradeStatus::Created->value);
    }

    /**
     * @param  Builder<Trade>  $query
     * @return Builder<Trade>
     */
    public function scopeActiveAsCounterparty(Builder $query, User $user): Builder
    {
        return $query
            ->where('counterparty_id', $user->getKey())
            ->whereIn('status', [
                TradeStatus::Negotiating->value,
                TradeStatus::Accepted->value,
            ]);
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
