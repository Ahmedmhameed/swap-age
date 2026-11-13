@extends('layouts.app')

@section('title', 'الصفقات | سوق المبادلة')

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-medium text-emerald-700">السوق</p>
            <h1 class="mt-1 text-3xl font-semibold">الصفقات</h1>
        </div>
        <form method="GET" action="{{ route('trades.index') }}" class="flex items-center gap-2">
            <label for="status" class="text-sm text-stone-600">الحالة</label>
            <select id="status" name="status" class="border border-stone-300 bg-white px-3 py-2">
                <option value="">الكل</option>
                @foreach (\App\Enums\TradeStatus::cases() as $tradeStatus)
                    <option value="{{ $tradeStatus->value }}" @selected($status === $tradeStatus->value)>{{ $tradeStatus->value }}
                    </option>
                @endforeach
            </select>
            <button class="border border-stone-300 px-3 py-2 hover:bg-stone-100">تصفية</button>
        </form>
    </div>
    <div class="overflow-hidden border border-stone-200 bg-white">
        @forelse ($trades as $trade)
            <a href="{{ route('trades.show', $trade) }}"
                class="grid gap-2 border-b border-stone-100 px-5 py-4 last:border-0 hover:bg-emerald-50/40 md:grid-cols-[1fr_auto_auto] md:items-center">
                <span><span class="font-medium">صفقة #{{ $trade->id }}</span><span
                        class="mr-3 text-sm text-stone-500">{{ $trade->owner->name }}</span></span>
                <span class="text-sm text-stone-600">{{ $trade->items_count ?? $trade->items()->count() }} عناصر</span>
                <span class="text-sm font-medium text-emerald-800">{{ $trade->status->value }}</span>
            </a>
        @empty
            <p class="px-5 py-12 text-center text-stone-500">لا توجد صفقات لعرضها.</p>
        @endforelse
    </div>
    <div class="mt-5">{{ $trades->withQueryString()->links() }}</div>
@endsection