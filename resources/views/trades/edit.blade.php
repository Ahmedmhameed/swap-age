<div>
    <!-- If you do not have a consistent goal in life, you can not live it in a consistent way. - Marcus Aurelius -->
    @extends('layouts.app')

    @section('title', 'تعديل الصفقة')

    @section('content')
        <h1 class="mb-6 text-3xl font-semibold">تعديل الصفقة #{{ $trade->id }}</h1>
        <form method="POST" action="{{ route('trades.update', $trade) }}"
            class="max-w-2xl space-y-5 border border-stone-200 bg-white p-6">
            @csrf @method('PUT')
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="block text-sm">مبلغ المالك<input name="cash_amount_owner" type="number" min="0" max="10000"
                        step="0.01" value="{{ $trade->cash_amount_owner }}"
                        class="mt-1 block w-full border border-stone-300 px-3 py-2"></label>
                <label class="block text-sm">مبلغ الطرف الآخر<input name="cash_amount_counterparty" type="number" min="0"
                        max="10000" step="0.01" value="{{ $trade->cash_amount_counterparty }}"
                        class="mt-1 block w-full border border-stone-300 px-3 py-2"></label>
            </div>
            <label class="block text-sm">العملة<input name="cash_currency" maxlength="3" value="{{ $trade->cash_currency }}"
                    class="mt-1 block w-full border border-stone-300 px-3 py-2"></label>
            <button class="bg-emerald-700 px-5 py-2.5 font-medium text-white hover:bg-emerald-800">حفظ التعديلات</button>
        </form>
        <section class="mt-8 max-w-2xl">
            <h2 class="mb-4 text-xl font-semibold">عناصر الصفقة</h2>
            @forelse ($trade->items as $item)
                <div class="mb-4 space-y-4 border border-stone-200 bg-white p-5">
                    <form method="POST" action="{{ route('trade-items.update', $item) }}" class="grid gap-3 sm:grid-cols-2">
                        @csrf @method('PUT')
                        <h3 class="font-medium sm:col-span-2">{{ $item->product->brand }} {{ $item->product->name }}</h3>
                        <label class="text-sm">الكمية<input name="quantity" type="number" min="1" value="{{ $item->quantity }}"
                                class="mt-1 w-full border border-stone-300 px-3 py-2"></label>
                        <label class="text-sm">الحالة<input name="condition" value="{{ $item->condition }}"
                                class="mt-1 w-full border border-stone-300 px-3 py-2"></label>
                        <label class="text-sm sm:col-span-2">الوصف<textarea name="description" rows="3"
                                class="mt-1 w-full border border-stone-300 px-3 py-2">{{ $item->description }}</textarea></label>
                        <button class="justify-self-start border border-emerald-700 px-4 py-2 text-sm text-emerald-800">حفظ
                            العنصر</button>
                    </form>
                    <form method="POST" enctype="multipart/form-data" action="{{ route('trades.evidence.store', $trade) }}"
                        class="flex flex-wrap items-end gap-3 border-t border-stone-100 pt-4">@csrf
                        <input type="hidden" name="kind" value="product_photo"><input type="hidden" name="trade_item_id"
                            value="{{ $item->id }}">
                        <label class="text-sm">إضافة صورة للعنصر<input type="file" name="file"
                                accept="image/jpeg,image/png,image/webp" required
                                class="mt-1 block w-full border border-stone-300 px-3 py-2"></label>
                        <button class="border border-stone-300 px-4 py-2 text-sm">رفع الصورة</button>
                    </form>
                </div>
            @empty
                <p class="border-y border-stone-200 py-6 text-stone-500">لا توجد عناصر في المسودة بعد.</p>
            @endforelse
        </section>
    @endsection