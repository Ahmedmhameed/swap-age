<div>
    <!-- Well begun is half done. - Aristotle -->
    @extends('layouts.app')

    @section('title', 'المنتجات | سوق المبادلة')

    @section('content')
        <div class="mb-6">
            <p class="text-sm font-medium text-emerald-700">كتالوج مشترك</p>
            <h1 class="mt-1 text-3xl font-semibold">المنتجات</h1>
        </div>
        <div class="grid gap-6 lg:grid-cols-[1fr_20rem]">
            <section class="divide-y divide-stone-200 border-y border-stone-200 bg-white">
                @forelse ($products as $product)
                    <article class="flex items-center justify-between gap-4 px-4 py-4">
                        <div>
                            <h2 class="font-medium">{{ $product->brand }} {{ $product->name }}</h2>
                            <p class="mt-1 text-sm text-stone-500">@if ($product->model) ·
                            {{ $product->model }} @endif
                            </p>
                        </div>@if (!$product->is_approved)<span class="text-xs text-amber-800">قيد المراجعة</span>@endif
                        @can('approve', $product) @if (!$product->is_approved)
                            <form method="POST" action="{{ route('products.approve', $product) }}">@csrf<button
                                    class="border border-emerald-700 px-3 py-1.5 text-sm text-emerald-800">اعتماد</button></form>
                        @endif @endcan
                    </article>
                @empty<p class="px-4 py-10 text-center text-stone-500">لا توجد منتجات.</p>@endforelse
            </section>
            @auth<form method="POST" action="{{ route('products.store') }}"
                class="h-fit space-y-3 border border-stone-200 bg-white p-5">@csrf<h2 class="text-lg font-semibold">طلب منتج
                    جديد</h2><label class="block text-sm">العلامة
                    التجارية<input name="brand" required maxlength="100"
                        class="mt-1 w-full border border-stone-300 px-3 py-2"></label><label
                    class="block text-sm">الاسم<input name="name" required maxlength="150"
                        class="mt-1 w-full border border-stone-300 px-3 py-2"></label><label
                    class="block text-sm">الموديل<input name="model" maxlength="150"
                        class="mt-1 w-full border border-stone-300 px-3 py-2"></label><button
            class="bg-emerald-700 px-4 py-2 text-white">إرسال الطلب</button></form>@endauth
        </div>
        <div class="mt-5">{{ $products->links() }}</div>
    @endsection