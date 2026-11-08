<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'LMS') — Hệ thống Smart LMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        
        @keyframes gradient-flow {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .auth-mesh-bg {
            background: linear-gradient(135deg, #030407 0%, #081226 35%, #030407 70%, #0c1a3b 100%);
            background-size: 300% 300%;
            animation: gradient-flow 12s ease infinite;
        }

        .glass-auth-card {
            background: rgba(10, 14, 24, 0.82);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.8), 0 0 35px rgba(21, 88, 201, 0.25);
            border-radius: 1.75rem;
        }

        /* 3D Floating Animations */
        @keyframes orb-float-1 {
            0%, 100% { transform: translate(0px, 0px) scale(1); }
            50% { transform: translate(40px, -30px) scale(1.1); }
        }
        @keyframes orb-float-2 {
            0%, 100% { transform: translate(0px, 0px) scale(1); }
            50% { transform: translate(-30px, 40px) scale(1.05); }
        }
        .orb-1 { animation: orb-float-1 8s ease-in-out infinite; }
        .orb-2 { animation: orb-float-2 10s ease-in-out infinite; }
    </style>
</head>
<body class="min-h-screen auth-mesh-bg text-slate-100 flex items-center justify-center p-4 relative overflow-hidden selection:bg-[#D4FF24] selection:text-[#030407]">
    
    {{-- 3D Glowing Ambient Spheres --}}
    <div class="absolute -top-24 -left-24 w-96 h-96 bg-[#1558C9]/35 rounded-full blur-3xl pointer-events-none orb-1"></div>
    <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-[#D4FF24]/15 rounded-full blur-3xl pointer-events-none orb-2"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-[#1558C9]/20 rounded-full blur-[140px] pointer-events-none"></div>

    <div class="w-full max-w-md relative z-10">
        <div class="text-center mb-8">
            <div class="inline-flex items-center gap-3">
                <div class="h-14 w-14 rounded-2xl bg-gradient-to-tr from-[#1558C9] via-[#2563eb] to-[#D4FF24] flex items-center justify-center text-[#030407] font-black text-2xl shadow-xl shadow-[#1558C9]/40 rotate-3 hover:rotate-12 transition duration-300">
                    L
                </div>
                <span class="text-4xl font-black tracking-tight text-white drop-shadow-md">SMART<span class="text-[#D4FF24]">LMS</span></span>
            </div>
            <p class="mt-2 text-slate-400 text-xs font-semibold uppercase tracking-widest">Hệ sinh thái Quản trị & Trợ lý AI Thông minh</p>
        </div>

        <div class="glass-auth-card p-8 sm:p-10">
            @if (session('success'))
                <div class="mb-5 rounded-xl bg-[#D4FF24]/10 border border-[#D4FF24]/40 p-4 text-xs font-bold text-[#D4FF24] flex items-center gap-2">
                    <span>✅</span>
                    <span>{{ session('success') }}</span>
                </div>
            @endif
            @if (session('error'))
                <div class="mb-5 rounded-xl bg-rose-500/10 border border-rose-400/30 p-4 text-xs font-bold text-rose-300 flex items-center gap-2">
                    <span>⚠️</span>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @yield('content')
        </div>

        <p class="mt-8 text-center text-xs text-slate-500 tracking-wider">© {{ date('Y') }} SMART LMS — POWERED BY NEXT-GEN AI</p>
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