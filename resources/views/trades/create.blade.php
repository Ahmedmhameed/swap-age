<div>
    <!-- No surplus words or unnecessary actions. - Marcus Aurelius -->
    @extends('layouts.app')

    @section('title', 'إنشاء صفقة')

    @section('content')
        <h1 class="mb-6 text-3xl font-semibold">إنشاء مسودة صفقة</h1>
        <form method="POST" action="{{ route('trades.store') }}"
            class="max-w-2xl space-y-5 border border-stone-200 bg-white p-6">
            @csrf
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="block text-sm">مبلغ يضيفه المالك<input name="cash_amount_owner" type="number" min="0"
                        max="10000" step="0.01" class="mt-1 block w-full border border-stone-300 px-3 py-2"></label>
                <label class="block text-sm">مبلغ يضيفه الطرف الآخر<input name="cash_amount_counterparty" type="number"
                        min="0" max="10000" step="0.01" class="mt-1 block w-full border border-stone-300 px-3 py-2"></label>
            </div>
            <label class="block text-sm">العملة<input name="cash_currency" maxlength="3" placeholder="USD"
                    class="mt-1 block w-full border border-stone-300 px-3 py-2"></label>
            <button class="bg-emerald-700 px-5 py-2.5 font-medium text-white hover:bg-emerald-800">إنشاء المسودة</button>
        </form>
    @endsection