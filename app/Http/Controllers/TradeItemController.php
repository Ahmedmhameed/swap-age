<?php

namespace App\Http\Controllers;

use App\Actions\AddTradeItem;
use App\Actions\RemoveTradeItem;
use App\Actions\UpdateTradeItem;
use App\Enums\TradeDirection;
use App\Http\Requests\AddTradeItemRequest;
use App\Http\Requests\UpdateTradeItemRequest;
use App\Models\Trade;
use App\Models\TradeItem;
use App\Services\EvidenceFileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TradeItemController extends Controller
{
    public function store(AddTradeItemRequest $request, Trade $trade, AddTradeItem $action, EvidenceFileService $files): RedirectResponse
    {
        $data = $request->validated();
        $action->handle($trade, $request->user(), $data['product_id'], TradeDirection::from($data['direction']), $data['quantity'], $data['condition'], $data['description'], $data['product_photo'] ?? null, $files);

        return back()->with('status', 'تمت إضافة العنصر.');
    }

    public function update(UpdateTradeItemRequest $request, TradeItem $tradeItem, UpdateTradeItem $action): RedirectResponse
    {
        $action->handle($tradeItem, $request->user(), $request->validated());

        return back()->with('status', 'تم تحديث العنصر.');
    }

    public function destroy(Request $request, TradeItem $tradeItem, RemoveTradeItem $action): RedirectResponse
    {
        $action->handle($tradeItem, $request->user());

        return back()->with('status', 'تم حذف العنصر.');
    }
}
