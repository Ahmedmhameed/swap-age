<?php

namespace App\Actions;

use App\Actions\Concerns\LocksTrade;
use App\Enums\CancellationReason;
use App\Enums\EvidenceKind;
use App\Enums\TradeStatus;
use App\Exceptions\DomainRuleViolation;
use App\Models\Trade;
use App\Models\TradeCancellation;
use App\Models\User;
use App\Services\EvidenceFileService;
use App\Services\TradeDeadlineService;
use App\Services\TradeTransitionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;

class CancelTrade
{
    use LocksTrade;

    public function handle(Trade $trade, User $actor, CancellationReason $reason, ?string $note, ?UploadedFile $proof, TradeTransitionService $transitions, EvidenceFileService $files, TradeDeadlineService $deadlines): TradeCancellation
    {
        return $this->withLockedTrade($trade, $actor, 'cancel', function (Trade $lockedTrade) use ($actor, $reason, $note, $proof, $transitions, $files, $deadlines): TradeCancellation {
            $previousStatus = $lockedTrade->status;
            $this->transition($lockedTrade, TradeStatus::Cancelled, $transitions);

            if ($reason === CancellationReason::ItemMismatch && $previousStatus !== TradeStatus::Accepted) {
                throw new DomainRuleViolation('سبب عدم تطابق العنصر متاح بعد قبول الصفقة فقط.');
            }

            if ($reason === CancellationReason::ItemMismatch && $deadlines->hasExpired($lockedTrade)) {
                throw new DomainRuleViolation('انتهت مهلة الاعتراض على عدم تطابق العنصر.');
            }

            if ($reason === CancellationReason::ItemMismatch && $previousStatus === TradeStatus::Accepted && $proof === null) {
                throw new DomainRuleViolation('يلزم إرفاق دليل عند الإلغاء بسبب عدم تطابق العنصر.');
            }

            if ($proof) {
                $files->store($lockedTrade, null, EvidenceKind::CancellationProof, $proof, $actor);
            }

            $lockedTrade->cancelled_at = Carbon::now();
            $lockedTrade->save();

            return TradeCancellation::create([
                'trade_id' => $lockedTrade->id,
                'cancelled_by_id' => $actor->id,
                'reason' => $reason,
                'note' => $note,
                'previous_status' => $previousStatus->value,
            ]);
        });
    }
}
