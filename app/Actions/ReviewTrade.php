<?php

namespace App\Actions;

use App\Actions\Concerns\LocksTrade;
use App\Enums\TradeStatus;
use App\Exceptions\DomainRuleViolation;
use App\Models\Review;
use App\Models\Trade;
use App\Models\User;

class ReviewTrade
{
    use LocksTrade;

    public function handle(Trade $trade, User $reviewer, int $rating, ?string $commentText): Review
    {
        return $this->withLockedTrade($trade, $reviewer, 'review', function (Trade $lockedTrade) use ($reviewer, $rating, $commentText): Review {
            $this->assertTradeStatus($lockedTrade, [TradeStatus::Completed, TradeStatus::Cancelled]);
            $reviewedId = match ($reviewer->id) {
                $lockedTrade->owner_id => $lockedTrade->counterparty_id,
                $lockedTrade->counterparty_id => $lockedTrade->owner_id,
                default => null,
            };

            if (! $reviewedId) {
                throw new DomainRuleViolation('يجب أن تكون طرفاً في الصفقة لتقييم الطرف الآخر.');
            }

            if ($lockedTrade->reviews()->where('reviewer_id', $reviewer->id)->exists()) {
                throw new DomainRuleViolation('سبق أن أرسلت تقييماً لهذه الصفقة.');
            }

            return $lockedTrade->reviews()->create([
                'reviewer_id' => $reviewer->id,
                'reviewed_id' => $reviewedId,
                'rating' => $rating,
                'comment_text' => $commentText,
            ]);
        });
    }
}
