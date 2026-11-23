<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Đăng nhập') — Smart LMS</title>
    <script>
        const _warn = console.warn;
        console.warn = function(msg) {
            if(typeof msg === 'string' && msg.includes('cdn.tailwindcss.com should not be used in production')) return;
            _warn.apply(console, arguments);
        };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', 'Be Vietnam Pro', -apple-system, sans-serif;
            background-color: #fafafa;
            background-image: radial-gradient(#e4e4e7 1px, transparent 1px);
            background-size: 20px 20px;
            -webkit-font-smoothing: antialiased;
        }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        input:focus {
            outline: none;
            border-color: #09090b !important;
            box-shadow: 0 0 0 2px rgba(9, 9, 11, 0.10) !important;
        }
    </style>
</head>
<body class="min-h-screen text-zinc-900 flex items-center justify-center p-4">
    <div class="w-full max-w-[400px]">
        <div class="mb-6 flex items-center justify-center gap-2.5">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-zinc-950 text-white text-xs font-bold font-mono tracking-tight">SL</span>
            <div class="leading-none">
                <span class="text-lg font-semibold tracking-tight text-zinc-950">Smart LMS</span>
                <span class="block text-[10px] font-mono text-zinc-500 mt-0.5">ACADEMIC PLATFORM</span>
            </div>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-6 sm:p-7 shadow-[0_1px_2px_rgba(9,9,11,0.04)]">
            @if (session('success'))
                <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-3.5 py-2.5 text-xs font-medium text-emerald-900">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-md border border-rose-200 bg-rose-50 px-3.5 py-2.5 text-xs font-medium text-rose-900">
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')
        </div>

        <p class="mt-5 text-center font-mono text-[11px] text-zinc-400">&copy; {{ date('Y') }} SMART LMS</p>
    </div>

    <script>
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) window.location.reload();
        });

        document.querySelectorAll('form[method="POST"]').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                if (form.dataset.submitting === 'true') {
                    e.preventDefault();
                    return;
                }
                form.dataset.submitting = 'true';
                const btn = form.querySelector('button[type="submit"]');
                if (btn) {
                    btn.disabled = true;
                    btn.classList.add('opacity-70', 'cursor-wait');
                }
            });
        });
    </script>
    @include('components.network-status-banner')
</body>
</html>
