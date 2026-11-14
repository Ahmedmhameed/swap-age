<div>
    <!-- I have not failed. I've just found 10,000 ways that won't work. - Thomas Edison -->
    @extends('layouts.app')

    @section('title', 'تفاصيل الصفقة #' . $trade->id)

    @section('content')
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4 border-b border-stone-200 pb-5">
            <div>
                <p class="text-sm text-emerald-700">تفاصيل الصفقة</p>
                <h1 class="mt-1 text-3xl font-semibold">صفقة #{{ $trade->id }}</h1>
                <p class="mt-2 text-sm text-stone-600">الحالة: {{ $trade->status->value }} · النسخة: {{ $trade->version }}
                </p>
            </div>
            @can('canUpdate', $trade)<a href="{{ route('trades.edit', $trade) }}"
            class="border border-stone-300 px-4 py-2 text-sm hover:bg-white">تعديل</a>@endcan
        </div>
        <div class="grid gap-8 lg:grid-cols-[1fr_19rem]">
            <section>
                <section class="mb-6 border-y border-stone-200 py-4">
                    <h2 class="font-semibold">التصنيفات</h2>
                    <p class="mt-2 text-sm text-stone-600">{{ $trade->categories->pluck('name')->join('، ') ?: 'لا توجد تصنيفات.' }}</p>
                </section>
                <h2 class="mb-3 text-xl font-semibold">العناصر</h2>
                <div class="divide-y divide-stone-200 border-y border-stone-200">
                    @forelse ($trade->items as $item)
                        <article class="py-5">
                            <div class="flex justify-between gap-4">
                                <div>
                                    <h3 class="font-medium">{{ $item->product->brand }} {{ $item->product->name }}</h3>
                                    <p class="mt-1 text-sm text-stone-600">{{ $item->direction->value }} ·
                                        {{ $item->condition }} · الكمية {{ $item->quantity }}
                                    </p>
                                    <p class="mt-2 whitespace-pre-line text-sm">{{ $item->description }}</p>
                                </div>@can('removeItem', $trade)
                                    <form method="POST" action="{{ route('trade-items.destroy', $item) }}"
                                        onsubmit="return confirm('حذف هذا العنصر وأدلته؟')">@csrf @method('DELETE')<button
                                title="حذف العنصر" class="text-rose-700">حذف</button></form>@endcan
                            </div>
                            <div class="mt-3 flex flex-wrap gap-3">@foreach ($item->evidence as $evidence)<span
                                class="text-sm text-emerald-800"><a
                                    href="{{ route('evidence.download', $evidence) }}">{{ $evidence->kind->value }} ·
                            {{ basename($evidence->file_path) }}</a></span>@endforeach
                            </div>
                        </article>
                    @empty<p class="py-8 text-stone-500">لم تُضف عناصر بعد.</p>@endforelse
                </div>
                @foreach ($trade->evidence->whereNull('trade_item_id') as $evidence)
                    <p class="py-2 text-sm text-emerald-800"><a
                            href="{{ route('evidence.download', $evidence) }}">{{ $evidence->kind->value }} ·
                            {{ basename($evidence->file_path) }}</a></p>
                @endforeach
                @can('manageItems', $trade)
                    <form method="POST" enctype="multipart/form-data" action="{{ route('trades.items.store', $trade) }}"
                        class="mt-6 grid gap-3 border border-stone-200 bg-white p-5 sm:grid-cols-2">@csrf
                        <h2 class="sm:col-span-2 text-lg font-semibold">إضافة عنصر</h2>
                        <label class="text-sm">المنتج<select name="product_id" required
                                class="mt-1 block w-full border border-stone-300 px-3 py-2">@foreach ($products as $product)
                                    <option value="{{ $product->id }}">{{ $product->brand }} {{ $product->name }}</option>
                                @endforeach
                            </select></label>
                        <label class="text-sm">الاتجاه<select name="direction"
                                class="mt-1 block w-full border border-stone-300 px-3 py-2">
                                <option value="owner_gives">المالك يقدم</option>
                                <option value="owner_receives">المالك يستقبل</option>
                            </select></label>
                        <label class="text-sm">الكمية<input name="quantity" type="number" min="1" value="1" required
                                class="mt-1 block w-full border border-stone-300 px-3 py-2"></label>
                        <label class="text-sm">الحالة<input name="condition" required
                                class="mt-1 block w-full border border-stone-300 px-3 py-2"></label>
                        <label class="text-sm sm:col-span-2">الوصف<textarea name="description" required rows="3"
                                class="mt-1 block w-full border border-stone-300 px-3 py-2"></textarea></label>
                        @if ($trade->status === \App\Enums\TradeStatus::Negotiating)
                            <label class="text-sm sm:col-span-2">صورة المنتج (مطلوبة)<input name="product_photo" type="file"
                                    accept="image/jpeg,image/png,image/webp" required
                                    class="mt-1 block w-full border border-stone-300 px-3 py-2"></label>
                        @endif
                        <button class="justify-self-start bg-emerald-700 px-4 py-2 text-white">إضافة العنصر</button>
                    </form>
                    @can('canUploadEvidence', $trade)
                        <form method="POST" enctype="multipart/form-data" action="{{ route('trades.evidence.store', $trade) }}"
                            class="mt-4 grid gap-3 border border-stone-200 bg-white p-5 sm:grid-cols-2">@csrf
                            <h2 class="sm:col-span-2 text-lg font-semibold">رفع صورة أو مستند</h2>
                            <label class="text-sm">نوع الدليل<select name="kind"
                                    class="mt-1 block w-full border border-stone-300 px-3 py-2">
                                    @can('canUploadProductPhoto', $trade)
                                    <option value="product_photo">صورة منتج</option>@endcan
                                    <option value="document">مستند PDF</option>
                                    <option value="recording">تسجيل</option>
                                </select></label>
                            <label class="text-sm">العنصر<select name="trade_item_id"
                                    class="mt-1 block w-full border border-stone-300 px-3 py-2">
                                    <option value="">دليل للصفقة</option>@foreach ($trade->items as $item)
                                    <option value="{{ $item->id }}">{{ $item->product->name }}</option>@endforeach
                                </select></label>
                            <label class="text-sm sm:col-span-2">الملف<input name="file" type="file" required
                                    class="mt-1 block w-full border border-stone-300 px-3 py-2"></label>
                            <button class="justify-self-start border border-emerald-700 px-4 py-2 text-emerald-800">رفع
                                الدليل</button>
                        </form>
                    @endcan
                @endcan
            </section>
            <aside class="space-y-5">
                <section class="border-t-2 border-emerald-700 pt-4">
                    <h2 class="font-semibold">الأطراف</h2>
                    <p class="mt-2 text-sm">المالك: {{ $trade->owner->name }}</p>
                    <p class="mt-1 text-sm">الطرف الآخر: {{ $trade->counterparty?->name ?? 'غير محدد' }}</p>
                    <p class="mt-3 text-sm text-stone-600">{{ $trade->cash_currency }} ·
                        {{ $trade->cash_amount_owner ?? 0 }} / {{ $trade->cash_amount_counterparty ?? 0 }}
                    </p>
                </section>
                @can('canEditCash', $trade)
                    <form method="POST" action="{{ route('trades.cash.update', $trade) }}"
                        class="space-y-3 border-t border-stone-200 pt-4">
                        @csrf @method('PUT')
                        <h2 class="font-semibold">تعديل النقد</h2>
                        <label class="block text-sm">مبلغ المالك<input name="cash_amount_owner" type="number" min="0"
                                max="10000" step="0.01" value="{{ $trade->cash_amount_owner }}"
                                class="mt-1 w-full border border-stone-300 px-3 py-2"></label>
                        <label class="block text-sm">مبلغ الطرف الآخر<input name="cash_amount_counterparty" type="number"
                                min="0" max="10000" step="0.01" value="{{ $trade->cash_amount_counterparty }}"
                                class="mt-1 w-full border border-stone-300 px-3 py-2"></label>
                        <label class="block text-sm">العملة<input name="cash_currency" maxlength="3"
                                value="{{ $trade->cash_currency }}"
                                class="mt-1 w-full border border-stone-300 px-3 py-2"></label>
                        <button class="border border-emerald-700 px-4 py-2 text-sm text-emerald-800">حفظ النقد</button>
                    </form>
                @endcan
                <section class="space-y-2 border-t border-stone-200 pt-4">
                    @can('canPublish', $trade)
                        <form method="POST" action="{{ route('trades.publish', $trade) }}">@csrf<button
                                class="w-full bg-emerald-700 px-4 py-2 text-white">نشر الصفقة</button></form>
                    @endcan
                    @can('canNegotiate', $trade)
                        <form method="POST" action="{{ route('trades.negotiations.start', $trade) }}">@csrf<button
                                class="w-full border border-emerald-700 px-4 py-2 text-emerald-800">بدء التفاوض</button></form>
                    @endcan
                    @can('canAccept', $trade)
                        <form method="POST" action="{{ route('trades.accept', $trade) }}" class="space-y-2">@csrf<input
                                type="hidden" name="accepted_version" value="{{ $trade->version }}"><button
                                class="w-full bg-emerald-700 px-4 py-2 text-white">قبول النسخة الحالية</button></form>
                    @endcan
                    @can('canConfirm', $trade)
                        <form method="POST" action="{{ route('trades.confirm', $trade) }}">@csrf<button
                                class="w-full bg-emerald-700 px-4 py-2 text-white">تأكيد الإتمام</button></form>
                    @endcan
                    @can('canCancel', $trade)
                        <form method="POST" enctype="multipart/form-data" action="{{ route('trades.cancel', $trade) }}"
                            class="space-y-2">@csrf<select name="reason" required
                                class="w-full border border-stone-300 px-3 py-2">@foreach (\App\Enums\CancellationReason::cases() as $reason)
                                <option value="{{ $reason->value }}">{{ $reason->value }}</option>@endforeach
                            </select><textarea name="note" placeholder="ملاحظة اختيارية"
                                class="w-full border border-stone-300 px-3 py-2"></textarea><label class="block text-sm">دليل
                                الإلغاء عند عدم التطابق<input name="cancellation_proof" type="file"
                                    accept="image/jpeg,image/png,image/webp,application/pdf,audio/*"
                                    class="mt-1 block w-full border border-stone-300 px-3 py-2"></label><button
                                class="w-full border border-rose-700 px-4 py-2 text-rose-800"
                                onclick="return confirm('تأكيد إلغاء الصفقة؟')">إلغاء الصفقة</button></form>
                    @endcan
                    @can('canCancelNegotiation', $trade)
                        <form method="POST" action="{{ route('trades.negotiations.cancel', $trade) }}">@csrf
                            @method('DELETE')<button class="w-full border border-stone-400 px-4 py-2">إنهاء التفاوض</button>
                        </form>
                    @endcan
                    @can('canHide', $trade)
                        <form method="POST" action="{{ route('trades.hide', $trade) }}">@csrf @method('DELETE')<button
                                class="w-full text-sm text-stone-600">إخفاء من قائمتي</button></form>
                    @endcan
                </section>
                <section class="border-t border-stone-200 pt-4">
                    <h2 class="font-semibold">التقييمات</h2>@forelse ($trade->reviews as $review)
                    <p class="mt-2 text-sm">{{ $review->rating }} / 5 · {{ $review->reviewer->name }}</p>@empty<p
                    class="mt-2 text-sm text-stone-500">لا توجد تقييمات.</p>@endforelse
                    @can('canReview', $trade)
                        <form method="POST" action="{{ route('trades.reviews.store', $trade) }}" class="mt-3 space-y-2">
                            @csrf<select name="rating"
                                class="w-full border border-stone-300 px-3 py-2">@for ($rating = 1; $rating <= 5; $rating++)
                                <option value="{{ $rating }}">{{ $rating }} نجوم</option>@endfor
                            </select><textarea name="comment_text" class="w-full border border-stone-300 px-3 py-2"
                                placeholder="تعليق اختياري"></textarea><button class="border border-stone-400 px-4 py-2">إرسال
                                تقييم</button></form>
                    @endcan
                </section>
            </aside>
        </div>
    @endsection