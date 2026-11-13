<?php

namespace App\Actions;

use App\Actions\Concerns\LocksTrade;
use App\Enums\EvidenceKind;
use App\Enums\TradeStatus;
use App\Exceptions\DomainRuleViolation;
use App\Models\Evidence;
use App\Models\Trade;
use App\Models\TradeItem;
use App\Models\User;
use App\Services\EvidenceFileService;
use Illuminate\Http\UploadedFile;

class UploadEvidence
{
    use LocksTrade;

    public function handle(Trade $trade, User $actor, EvidenceKind $kind, UploadedFile $file, EvidenceFileService $files, ?TradeItem $item = null): Evidence
    {
        return $this->withLockedTrade($trade, $actor, 'uploadEvidence', function (Trade $lockedTrade) use ($actor, $kind, $file, $files, $item): Evidence {
            $allowedStatuses = match ($kind) {
                EvidenceKind::ProductPhoto => [TradeStatus::Draft, TradeStatus::Created],
                EvidenceKind::Document, EvidenceKind::Recording => [TradeStatus::Draft, TradeStatus::Created, TradeStatus::Negotiating],
                EvidenceKind::CancellationProof => [],
            };
            $this->assertTradeStatus($lockedTrade, $allowedStatuses);

            if ($item !== null && $item->trade_id !== $lockedTrade->id) {
                throw new DomainRuleViolation('العنصر لا يتبع هذه الصفقة.');
            }

            if ($kind === EvidenceKind::ProductPhoto && (! $item || $item->trade_id !== $lockedTrade->id)) {
                throw new DomainRuleViolation('صورة المنتج يجب أن ترتبط بعنصر من الصفقة.');
            }

            $evidence = $files->store($lockedTrade, $item, $kind, $file, $actor);

            if (in_array($lockedTrade->status, [TradeStatus::Created, TradeStatus::Negotiating], true)) {
                $this->recordVersionedChange($lockedTrade, $actor);
            }

            return $evidence;
        });
    }
}
