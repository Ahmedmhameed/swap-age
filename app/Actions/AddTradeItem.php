<?php

namespace App\Actions;

use App\Actions\Concerns\LocksTrade;
use App\Enums\EvidenceKind;
use App\Enums\TradeDirection;
use App\Enums\TradeStatus;
use App\Exceptions\DomainRuleViolation;
use App\Models\Product;
use App\Models\Trade;
use App\Models\TradeItem;
use App\Models\User;
use App\Services\EvidenceFileService;
use Illuminate\Http\UploadedFile;

class AddTradeItem
{
    use LocksTrade;

    public function handle(Trade $trade, User $actor, int $productId, TradeDirection $direction, int $quantity, string $condition, string $description, ?UploadedFile $productPhoto, EvidenceFileService $files): TradeItem
    {
        return $this->withLockedTrade($trade, $actor, 'participate', function (Trade $lockedTrade) use ($actor, $productId, $direction, $quantity, $condition, $description, $productPhoto, $files): TradeItem {
            $this->assertTradeStatus($lockedTrade, [TradeStatus::Draft, TradeStatus::Created, TradeStatus::Negotiating]);
            $product = Product::query()->find($productId);

            if (! $product?->is_approved) {
                throw new DomainRuleViolation('لا يمكن إضافة منتج غير معتمد إلى الصفقة.');
            }

            $item = $lockedTrade->items()->create([
                'product_id' => $productId,
                'direction' => $direction,
                'quantity' => $quantity,
                'condition' => $condition,
                'description' => $description,
                'added_during_negotiation' => $lockedTrade->status === TradeStatus::Negotiating,
            ]);

            if ($lockedTrade->status === TradeStatus::Negotiating) {
                if (! $productPhoto) {
                    throw new DomainRuleViolation('يجب إضافة صورة المنتج مع العنصر الجديد أثناء التفاوض.');
                }

                $files->store($lockedTrade, $item, EvidenceKind::ProductPhoto, $productPhoto, $actor);
            }

            if (in_array($lockedTrade->status, [TradeStatus::Created, TradeStatus::Negotiating], true)) {
                $this->recordVersionedChange($lockedTrade, $actor);
            }

            return $item;
        });
    }
}
