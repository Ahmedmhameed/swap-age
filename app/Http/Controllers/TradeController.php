<?php

namespace App\Http\Controllers;

use App\Actions\CreateDraftTrade;
use App\Actions\HideTrade;
use App\Actions\PublishTrade;
use App\Actions\SyncTradeCategories;
use App\Actions\UpdateDraftTrade;
use App\Actions\UpdateTradeCash;
use App\Enums\TradeStatus;
use App\Http\Requests\CreateDraftTradeRequest;
use App\Http\Requests\UpdateDraftTradeRequest;
use App\Http\Requests\UpdateTradeCashRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Trade;
use App\Services\TradeLimitService;
use App\Services\TradeTransitionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TradeController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Trade::class);
        $status = $request->query('status');
        $userId = $request->user()?->id;
        $trades = Trade::query()->where(function ($query) use ($userId): void {
            $query->where('status', TradeStatus::Created->value);

            if ($userId !== null) {
                $query->orWhere('owner_id', $userId)->orWhere('counterparty_id', $userId);
            }
        })
            ->when($status && TradeStatus::tryFrom($status), fn($query) => $query->where('status', $status))
            ->with(['owner', 'counterparty'])
            ->withCount('items')
            ->latest('id')
            ->paginate(15);

        return view('trades.index', compact('trades', 'status'));
    }

    public function create(): View
    {
        return view('trades.create', ['products' => Product::query()->where('is_approved', true)->orderBy('name')->get()]);
    }

    public function store(CreateDraftTradeRequest $request, CreateDraftTrade $action): RedirectResponse
    {
        $trade = $action->handle($request->user(), $request->validated());

        return redirect()->route('trades.show', $trade)->with('status', 'تم إنشاء مسودة الصفقة.');
    }

    public function show(Trade $trade): View
    {
        Gate::authorize('view', $trade);
        $trade->load(['owner', 'counterparty', 'items.product', 'items.evidence', 'evidence', 'reviews.reviewer', 'reviews.reviewed', 'cancellation']);
        $trade->load('categories');
        $products = Product::query()->where('is_approved', true)->orderBy('name')->get();

        return view('trades.show', compact('trade', 'products'));
    }

    public function edit(Trade $trade): View
    {
        Gate::authorize('update', $trade);
        $trade->load('items.product', 'items.evidence');

        return view('trades.edit', [
            'trade' => $trade,
            'products' => Product::query()->where('is_approved', true)->orderBy('name')->get(),
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function updateCategories(Request $request, Trade $trade, SyncTradeCategories $action): RedirectResponse
    {
        $data = $request->validate([
            'category_ids' => ['sometimes', 'array'],
            'category_ids.*' => ['integer', 'distinct', 'exists:categories,id'],
        ]);
        $action->handle($trade, $request->user(), $data['category_ids'] ?? []);

        return back()->with('status', 'تم تحديث تصنيفات الصفقة.');
    }

    public function update(UpdateDraftTradeRequest $request, Trade $trade, UpdateDraftTrade $action): RedirectResponse
    {
        $action->handle($trade, $request->user(), $request->validated());

        return redirect()->route('trades.show', $trade)->with('status', 'تم تحديث الصفقة.');
    }

    public function updateCash(UpdateTradeCashRequest $request, Trade $trade, UpdateTradeCash $action): RedirectResponse
    {
        $action->handle($trade, $request->user(), $request->validated());

        return back()->with('status', 'تم تحديث المبالغ النقدية.');
    }

    public function publish(Request $request, Trade $trade, PublishTrade $action, TradeLimitService $limits, TradeTransitionService $transitions): RedirectResponse
    {
        $action->handle($trade, $request->user(), $limits, $transitions);

        return back()->with('status', 'تم نشر الصفقة.');
    }

    public function hide(Request $request, Trade $trade, HideTrade $action): RedirectResponse
    {
        $action->handle($trade, $request->user());

        return redirect()->route('trades.index')->with('status', 'تم إخفاء الصفقة.');
    }
}
