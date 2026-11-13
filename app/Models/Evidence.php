<?php

namespace App\Models;

use App\Enums\EvidenceKind;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['trade_id', 'trade_item_id', 'kind', 'file_path', 'mime_type', 'size', 'uploaded_by_id'])]
class Evidence extends Model
{
    public $timestamps = false;

    public function trade(): BelongsTo
    {
        return $this->belongsTo(Trade::class);
    }

    public function tradeItem(): BelongsTo
    {
        return $this->belongsTo(TradeItem::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }

    protected static function booted(): void
    {
        static::deleting(function (Evidence $evidence): void {
            Storage::disk('local')->delete($evidence->file_path);
        });
    }

    protected function casts(): array
    {
        return [
            'kind' => EvidenceKind::class,
            'size' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
