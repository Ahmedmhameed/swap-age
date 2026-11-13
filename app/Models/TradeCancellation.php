<?php

namespace App\Models;

use App\Enums\CancellationReason;
use Database\Factories\TradeCancellationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['trade_id', 'cancelled_by_id', 'reason', 'note', 'previous_status'])]
class TradeCancellation extends Model
{
    /** @use HasFactory<TradeCancellationFactory> */
    use HasFactory;

    public $timestamps = false;

    public function trade(): BelongsTo
    {
        return $this->belongsTo(Trade::class);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by_id');
    }

    protected function casts(): array
    {
        return [
            'reason' => CancellationReason::class,
            'created_at' => 'datetime',
        ];
    }
}
