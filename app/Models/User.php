<?php

namespace App\Models;

use App\Enums\SubscriptionTier;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'phone', 'gender', 'password', 'subscription_tier'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    public function ownedTrades(): HasMany
    {
        return $this->hasMany(Trade::class, 'owner_id');
    }

    public function counterpartyTrades(): HasMany
    {
        return $this->hasMany(Trade::class, 'counterparty_id');
    }

    public function createdProducts(): HasMany
    {
        return $this->hasMany(Product::class, 'created_by');
    }

    public function uploadedEvidence(): HasMany
    {
        return $this->hasMany(Evidence::class, 'uploaded_by_id');
    }

    public function writtenReviews(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    public function receivedReviews(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewed_id');
    }

    public function cancellations(): HasMany
    {
        return $this->hasMany(TradeCancellation::class, 'cancelled_by_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'subscription_tier' => SubscriptionTier::class,
            'is_admin' => 'boolean',
        ];
    }
}
