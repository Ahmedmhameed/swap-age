<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['brand', 'name', 'model', 'version', 'specs', 'is_approved', 'created_by'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tradeItems(): HasMany
    {
        return $this->hasMany(TradeItem::class);
    }

    protected function casts(): array
    {
        return [
            'specs' => 'array',
            'is_approved' => 'boolean',
        ];
    }
}
