@extends('layouts.app')

@section('title', $category->exists ? 'تعديل التصنيف' : 'إضافة تصنيف')

@section('content')
    <h1 class="mb-6 text-3xl font-semibold">{{ $category->exists ? 'تعديل التصنيف' : 'إضافة تصنيف' }}</h1>
    <form method="POST" action="{{ $category->exists ? route('categories.update', $category) : route('categories.store') }}"
        class="max-w-xl space-y-4 border border-stone-200 bg-white p-6">
        @csrf
        @if ($category->exists)
            @method('PUT')
        @endif
        <label class="block text-sm">الاسم<input name="name" required maxlength="255"
                value="{{ old('name', $category->name) }}"
                class="mt-1 block w-full border border-stone-300 px-3 py-2"></label>
        <label class="block text-sm">المعرّف<input name="slug" required maxlength="255"
                value="{{ old('slug', $category->slug) }}"
                class="mt-1 block w-full border border-stone-300 px-3 py-2"></label>
        <button class="bg-emerald-700 px-5 py-2.5 font-medium text-white">حفظ</button>
    </form>
    @if ($category->exists)
        <form method="POST" action="{{ route('categories.destroy', $category) }}" class="mt-4">
            @csrf
            @method('DELETE')
            <button class="border border-rose-700 px-4 py-2 text-rose-800">حذف التصنيف</button>
        </form>
    @endif
@endsection
<div>
    <!-- The whole future lies in uncertainty: live immediately. - Seneca -->
</div>