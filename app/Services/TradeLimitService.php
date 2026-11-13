<?php

namespace App\Services;

use App\Enums\SubscriptionTier;
use App\Models\Trade;
use App\Models\User;

class TradeLimitService
{
    public function canPublish(User $user): bool
    {
        $limit = $user->subscription_tier === SubscriptionTier::Subscriber ? 4 : 2;

        return Trade::publishedBy($user)->count() < $limit;
    }

    public function canParticipate(User $user, ?Trade $excludingTrade = null): bool
    {
        $limit = $user->subscription_tier === SubscriptionTier::Subscriber ? 4 : 2;
        $trades = Trade::activeAsCounterparty($user);

        if ($excludingTrade !== null) {
            $trades->whereKeyNot($excludingTrade->getKey());
        }

        return $trades->count() < $limit;
    }
}
