<?php

namespace App\Http\Controllers;

use App\Actions\CancelTrade;
use App\Enums\CancellationReason;
use App\Http\Requests\CancelTradeRequest;
use App\Models\Trade;
use App\Services\EvidenceFileService;
use App\Services\TradeDeadlineService;
use App\Services\TradeTransitionService;
use Illuminate\Http\RedirectResponse;

class CancellationController extends Controller
{
    public function store(CancelTradeRequest $request, Trade $trade, CancelTrade $action, TradeTransitionService $transitions, EvidenceFileService $files, TradeDeadlineService $deadlines): RedirectResponse
    {
        $data = $request->validated();
        $action->handle($trade, $request->user(), CancellationReason::from($data['reason']), $data['note'] ?? null, $data['cancellation_proof'] ?? null, $transitions, $files, $deadlines);

        return redirect()->route('trades.show', $trade)->with('status', 'تم إلغاء الصفقة.');
    }
}
