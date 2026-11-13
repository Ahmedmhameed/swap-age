<?php

namespace App\Http\Controllers;

use App\Actions\CancelNegotiation;
use App\Actions\StartNegotiation;
use App\Http\Requests\StartNegotiationRequest;
use App\Models\Trade;
use App\Services\TradeLimitService;
use App\Services\TradeTransitionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NegotiationController extends Controller
{
    public function start(StartNegotiationRequest $request, Trade $trade, StartNegotiation $action, TradeLimitService $limits, TradeTransitionService $transitions): RedirectResponse
    {
        $action->handle($trade, $request->user(), $limits, $transitions);

        return back()->with('status', 'بدأ التفاوض على الصفقة.');
    }

    public function cancel(Request $request, Trade $trade, CancelNegotiation $action, TradeTransitionService $transitions): RedirectResponse
    {
        $action->handle($trade, $request->user(), $transitions);

        return back()->with('status', 'أُلغي التفاوض وأصبحت الصفقة متاحة مجدداً.');
    }
}
