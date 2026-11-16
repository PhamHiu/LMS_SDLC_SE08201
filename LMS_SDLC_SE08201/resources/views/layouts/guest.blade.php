<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'LMS') — Hệ thống Smart LMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' };</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Be Vietnam Pro', sans-serif; }
        input:focus { outline: none; border-color: #1e4f8f !important; box-shadow: 0 0 0 3px rgba(30, 79, 143, .12) !important; }
    </style>
</head>
<body class="min-h-screen bg-[#f3f4f6] text-slate-800 flex items-center justify-center p-4">

    <div class="w-full max-w-md">
        <div class="mb-6 flex items-center justify-center gap-2.5">
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-md bg-[#1b2a41] text-white text-lg font-bold">L</span>
            <span class="text-2xl font-semibold text-slate-800">Smart LMS</span>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-6 sm:p-8 shadow-sm">
            @if (session('success'))
                <div class="mb-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-5 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')
        </div>

        <p class="mt-6 text-center text-xs text-slate-400">© {{ date('Y') }} Smart LMS</p>
    </div>

    <script>
        // Làm mới trang nếu trình duyệt khôi phục từ bộ nhớ đệm BFCache (bấm nút Back sau khi đăng xuất)
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                window.location.reload();
            }
        });

        // Chống bấm đúp gửi 2 request liên tiếp làm mất hiệu lực session/CSRF token (lỗi 419)
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
                    btn.classList.add('opacity-75', 'cursor-wait');
                }
            });
        });
    </script>
</body>
</html>