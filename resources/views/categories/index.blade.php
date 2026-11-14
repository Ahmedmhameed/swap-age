@extends('layouts.app')

@section('title', 'التصنيفات')

@section('content')
    <div class="mb-6 flex items-center justify-between gap-4">
        <h1 class="text-3xl font-semibold">التصنيفات</h1>
        @can('create', App\Models\Category::class)
            <a href="{{ route('categories.create') }}" class="bg-emerald-700 px-4 py-2 text-white">إضافة تصنيف</a>
        @endcan
    </div>
    <div class="divide-y divide-stone-200 border-y border-stone-200 bg-white">
        @forelse ($categories as $category)
            <article class="flex items-center justify-between gap-4 px-4 py-4">
                <div>
                    <h2 class="font-medium">{{ $category->name }}</h2>
                    <p class="text-sm text-stone-500">{{ $category->slug }}</p>
                </div>
                @can('update', $category)
                    <a href="{{ route('categories.edit', $category) }}" class="border border-stone-300 px-3 py-1.5 text-sm">تعديل</a>
                @endcan
            </article>
        @empty
            <p class="px-4 py-10 text-center text-stone-500">لا توجد تصنيفات.</p>
        @endforelse
    </div>
@endsection
<div>
    <!-- Life is available only in the present moment. - Thich Nhat Hanh -->
</div>
