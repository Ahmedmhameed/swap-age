<?php

namespace App\Policies;

use App\Enums\TradeStatus;
use App\Models\Trade;
use App\Models\User;

class TradePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, Trade $trade): bool
    {
        return $trade->status === TradeStatus::Created
            || ($user !== null && ($trade->owner_id === $user->id || $trade->counterparty_id === $user->id));
    }

    public function viewEvidence(User $user, Trade $trade): bool
    {
        return $this->isParty($user, $trade);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Trade $trade): bool
    {
        return $trade->owner_id === $user->id;
    }

    public function canUpdate(User $user, Trade $trade): bool
    {
        return $trade->owner_id === $user->id && $trade->status === TradeStatus::Draft;
    }

    public function updateCategories(User $user, Trade $trade): bool
    {
        return $this->canUpdate($user, $trade);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Trade $trade): bool
    {
        return $trade->owner_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function participate(User $user, Trade $trade): bool
    {
        return $trade->owner_id === $user->id || $trade->counterparty_id === $user->id;
    }

    public function manageItems(User $user, Trade $trade): bool
    {
        return $this->participate($user, $trade)
            && in_array($trade->status, [TradeStatus::Draft, TradeStatus::Created, TradeStatus::Negotiating], true);
    }

    public function canEditCash(User $user, Trade $trade): bool
    {
        return $this->participate($user, $trade)
            && in_array($trade->status, [TradeStatus::Created, TradeStatus::Negotiating], true);
    }

    public function removeItem(User $user, Trade $trade): bool
    {
        return $this->participate($user, $trade)
            && in_array($trade->status, [TradeStatus::Created, TradeStatus::Negotiating], true);
    }

    public function negotiate(User $user, Trade $trade): bool
    {
        return $trade->owner_id !== $user->id;
    }

    public function publish(User $user, Trade $trade): bool
    {
        return $trade->owner_id === $user->id;
    }

    public function canPublish(User $user, Trade $trade): bool
    {
        return $trade->owner_id === $user->id && $trade->status === TradeStatus::Draft;
    }

    public function canNegotiate(User $user, Trade $trade): bool
    {
        return $trade->owner_id !== $user->id && $trade->status === TradeStatus::Created;
    }

    public function accept(User $user, Trade $trade): bool
    {
        return $this->isParty($user, $trade)
            || ($trade->counterparty_id === null && $trade->owner_id !== $user->id);
    }

    public function canAccept(User $user, Trade $trade): bool
    {
        if (!in_array($trade->status, [TradeStatus::Created, TradeStatus::Negotiating], true)) {
            return false;
        }

        if ($trade->status === TradeStatus::Created && $trade->counterparty_id === null) {
            return $trade->owner_id !== $user->id;
        }

        return $this->isParty($user, $trade)
            && ($trade->last_changed_by_id === null || $trade->last_changed_by_id !== $user->id);
    }

    public function confirm(User $user, Trade $trade): bool
    {
        return $this->isParty($user, $trade);
    }

    public function canConfirm(User $user, Trade $trade): bool
    {
        if ($trade->status !== TradeStatus::Accepted || !$this->isParty($user, $trade)) {
            return false;
        }

        return $trade->owner_id === $user->id
            ? $trade->confirmed_by_owner_at === null
            : $trade->confirmed_by_counterparty_at === null;
    }

    public function cancel(User $user, Trade $trade): bool
    {
        return $this->isParty($user, $trade);
    }

    public function canCancel(User $user, Trade $trade): bool
    {
        return $this->isParty($user, $trade)
            && in_array($trade->status, [TradeStatus::Created, TradeStatus::Negotiating, TradeStatus::Accepted], true);
    }

    public function canCancelNegotiation(User $user, Trade $trade): bool
    {
        return $this->participate($user, $trade) && $trade->status === TradeStatus::Negotiating;
    }

    public function canHide(User $user, Trade $trade): bool
    {
        return $trade->owner_id === $user->id
            && in_array($trade->status, [TradeStatus::Draft, TradeStatus::Completed, TradeStatus::Cancelled], true);
    }

    public function review(User $user, Trade $trade): bool
    {
        return $this->isParty($user, $trade);
    }

    public function canReview(User $user, Trade $trade): bool
    {
        return $this->isParty($user, $trade)
            && in_array($trade->status, [TradeStatus::Completed, TradeStatus::Cancelled], true)
            && !$trade->reviews()->where('reviewer_id', $user->id)->exists();
    }

    public function uploadEvidence(User $user, Trade $trade): bool
    {
        return $this->isParty($user, $trade);
    }

    public function canUploadEvidence(User $user, Trade $trade): bool
    {
        return $this->isParty($user, $trade)
            && in_array($trade->status, [TradeStatus::Draft, TradeStatus::Created, TradeStatus::Negotiating], true);
    }

    public function canUploadProductPhoto(User $user, Trade $trade): bool
    {
        return $this->isParty($user, $trade)
            && in_array($trade->status, [TradeStatus::Draft, TradeStatus::Created], true);
    }

    public function deleteEvidence(User $user, Trade $trade): bool
    {
        return $this->isParty($user, $trade);
    }

    private function isParty(User $user, Trade $trade): bool
    {
        return $trade->owner_id === $user->id || $trade->counterparty_id === $user->id;
    }
}
