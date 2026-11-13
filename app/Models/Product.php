<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['category', 'brand', 'name', 'model', 'version', 'specs', 'is_approved', 'created_by'])]
class Product extends Model
{
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
