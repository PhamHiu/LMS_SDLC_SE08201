<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'LMS') — Hệ thống quản lý học tập</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Be Vietnam Pro', sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-indigo-600 via-purple-600 to-indigo-800 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-6">
            <div class="inline-flex items-center gap-2 text-white">
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-white/20 backdrop-blur text-2xl font-extrabold">L</span>
                <span class="text-3xl font-extrabold tracking-tight">LMS<span class="text-indigo-200">.edu</span></span>
            </div>
            <p class="mt-2 text-indigo-100 text-sm">Hệ thống quản lý học tập trực tuyến</p>
        </div>

        <div class="bg-white rounded-2xl shadow-2xl p-8">
            @if (session('success'))
                <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-lg bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-700">
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')
        </div>

        <p class="mt-6 text-center text-xs text-indigo-200">© {{ date('Y') }} LMS — Laravel {{ app()->version() }}</p>
    </div>
</body>
</html>
