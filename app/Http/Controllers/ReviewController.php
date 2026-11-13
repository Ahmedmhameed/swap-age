<?php

namespace App\Http\Controllers;

use App\Actions\ReviewTrade;
use App\Http\Requests\ReviewTradeRequest;
use App\Models\Trade;
use Illuminate\Http\RedirectResponse;

class ReviewController extends Controller
{
    public function store(ReviewTradeRequest $request, Trade $trade, ReviewTrade $action): RedirectResponse
    {
        $data = $request->validated();
        $action->handle($trade, $request->user(), $data['rating'], $data['comment_text'] ?? null);

        return back()->with('status', 'تم إرسال تقييمك.');
    }
}
