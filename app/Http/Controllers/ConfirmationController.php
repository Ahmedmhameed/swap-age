<?php

namespace App\Http\Controllers;

use App\Actions\AcceptTrade;
use App\Actions\ConfirmTrade;
use App\Http\Requests\AcceptTradeRequest;
use App\Models\Trade;
use App\Services\TradeDeadlineService;
use App\Services\TradeLimitService;
use App\Services\TradeTransitionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ConfirmationController extends Controller
{
    public function accept(AcceptTradeRequest $request, Trade $trade, AcceptTrade $action, TradeLimitService $limits, TradeTransitionService $transitions): RedirectResponse
    {
        $action->handle($trade, $request->user(), $request->integer('accepted_version'), $limits, $transitions);

        return back()->with('status', 'تم قبول شروط الصفقة.');
    }

    public function confirm(Request $request, Trade $trade, ConfirmTrade $action, TradeDeadlineService $deadlines, TradeTransitionService $transitions): RedirectResponse
    {
        $action->handle($trade, $request->user(), $deadlines, $transitions);

        return back()->with('status', 'تم تسجيل تأكيدك.');
    }
}
