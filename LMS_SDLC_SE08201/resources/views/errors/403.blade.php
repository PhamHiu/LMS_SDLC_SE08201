<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 — Giới hạn quyền truy cập</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .surreal-bg {
            background-color: #0c1222;
            background-image: 
                radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.22) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(244, 63, 94, 0.18) 0px, transparent 50%),
                radial-gradient(at 50% 50%, rgba(14, 165, 233, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(168, 85, 247, 0.2) 0px, transparent 50%);
            background-attachment: fixed;
        }
        .glass-card {
            background: rgba(19, 27, 46, 0.85);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.7), 0 0 40px rgba(99, 102, 241, 0.15);
            border-radius: 1.75rem;
        }
    </style>
</head>
<body class="min-h-screen surreal-bg text-slate-100 flex items-center justify-center p-4 selection:bg-rose-500 selection:text-white">
    <div class="glass-card max-w-lg w-full p-8 sm:p-10 text-center relative overflow-hidden">
        {{-- Glow Accent --}}
        <div class="absolute -top-20 -right-20 w-48 h-48 bg-rose-500/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-20 -left-20 w-48 h-48 bg-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10">
            {{-- Icon Shield / Lock --}}
            <div class="inline-flex h-20 w-20 items-center justify-center rounded-3xl bg-gradient-to-tr from-rose-500/20 via-amber-500/20 to-orange-500/20 border border-rose-400/30 text-rose-400 shadow-[0_0_30px_rgba(244,63,94,0.3)] mb-6">
                <svg class="w-10 h-10 filter drop-shadow-[0_0_10px_rgba(244,63,94,0.8)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>

            <div class="inline-block px-3 py-1 rounded-full bg-rose-500/20 border border-rose-400/30 text-xs font-black uppercase tracking-widest text-rose-300 mb-3">
                MÃ LỖI 403 · FORBIDDEN
            </div>

            <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight drop-shadow-md">
                Giới hạn quyền truy cập
            </h1>

            <p class="text-sm text-slate-300 mt-3 leading-relaxed">
                {{ $exception->getMessage() ?: 'Tài khoản của bạn hiện không có quyền truy cập vào chức năng hoặc liên kết này. Vui lòng chuyển về đúng trang theo vai trò của bạn.' }}
            </p>

            @if(auth()->check())
                <div class="mt-4 p-3 rounded-xl bg-white/5 border border-white/10 text-xs text-slate-300">
                    Đang đăng nhập: <strong class="text-white">{{ auth()->user()->full_name }}</strong> · Vai trò: <span class="font-bold text-amber-300">{{ auth()->user()->roleLabel() }}</span>
                </div>
            @endif

            <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
                <button type="button" onclick="window.history.back()" class="w-full sm:w-auto px-5 py-3 rounded-xl border border-white/15 bg-white/10 hover:bg-white/20 text-xs font-bold text-slate-200 transition-all shadow-sm">
                    ← Quay lại trang trước
                </button>

                @php
                    $homeUrl = auth()->check() ? app(\App\Services\AuthService::class)->homeRouteFor(auth()->user()) : route('login');
                @endphp
                <a href="{{ $homeUrl }}" class="w-full sm:w-auto px-6 py-3 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-xs font-black text-white transition-all shadow-lg shadow-indigo-500/30">
                    Về Dashboard của tôi →
                </a>
            </div>

            <div class="mt-6 pt-5 border-t border-white/10 text-center">
                <form method="POST" action="{{ route('logout') }}" class="inline-block">
                    @csrf
                    <button type="submit" class="text-xs text-slate-400 hover:text-rose-300 transition underline underline-offset-4 font-semibold">
                        Đăng xuất để đổi tài khoản khác
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>

