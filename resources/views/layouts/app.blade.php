<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'سوق المبادلة')</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>

<body class="min-h-screen bg-stone-50 text-stone-900">
    <header class="border-b border-stone-200 bg-white">
        <nav class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
            <a href="{{ route('trades.index') }}" class="text-xl font-semibold text-emerald-800">سوق المبادلة</a>
            <div class="flex items-center gap-5 text-sm">
                <a href="{{ route('trades.index') }}" class="hover:text-emerald-700">الصفقات</a>
                <a href="{{ route('products.index') }}" class="hover:text-emerald-700">المنتجات</a>
                @auth<a href="{{ route('trades.create') }}"
                    class="rounded-sm bg-emerald-700 px-4 py-2 text-white hover:bg-emerald-800">صفقة جديدة</a>
                @endauth
            </div>
        </nav>
    </header>
    <main class="mx-auto max-w-6xl px-4 py-8">
        @if (session('status'))
            <div class="mb-5 border-r-4 border-emerald-600 bg-emerald-50 px-4 py-3 text-emerald-900">{{ session('status') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="mb-5 border-r-4 border-rose-600 bg-rose-50 px-4 py-3 text-rose-900">
                @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>@endforeach
            </div>
        @endif
        @yield('content')
    </main>
</body>

</html>