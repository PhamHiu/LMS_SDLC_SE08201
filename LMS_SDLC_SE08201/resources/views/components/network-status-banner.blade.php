{{-- Global Network & Server Connection Status Banner --}}
<div id="globalNetworkBanner" class="fixed top-4 left-1/2 -translate-x-1/2 z-[100000] hidden w-[calc(100%-2rem)] max-w-xl rounded-lg border px-4 py-3 shadow-lg transition-all" role="alert" aria-live="assertive">
    <div class="flex items-center justify-between gap-3">
        <div class="flex items-center gap-2.5 min-w-0">
            <span id="globalNetworkIcon" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-rose-100 text-rose-700 font-bold text-sm">!</span>
            <div class="min-w-0">
                <p id="globalNetworkTitle" class="text-sm font-bold text-rose-900">Không thể kết nối với Server</p>
                <p id="globalNetworkDesc" class="text-xs text-rose-700">Không thể kết nối với Server. Vui lòng kiểm tra lại mạng!</p>
            </div>
        </div>
        <div class="flex items-center gap-1.5 shrink-0">
            <button type="button" id="globalNetworkRetryBtn" onclick="window.retryServerConnection()" class="rounded-md bg-rose-700 hover:bg-rose-800 px-3 py-1.5 text-xs font-semibold text-white transition">
                Thử lại
            </button>
            <button type="button" onclick="window.hideNetworkBanner()" class="rounded-md p-1.5 text-slate-500 hover:bg-black/5 transition" aria-label="Đóng">
                ✕
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    if (window.__lmsNetworkMonitorInit) return;
    window.__lmsNetworkMonitorInit = true;

    let hideTimer = null;

    function getBannerEls() {
        return {
            banner: document.getElementById('globalNetworkBanner'),
            icon: document.getElementById('globalNetworkIcon'),
            title: document.getElementById('globalNetworkTitle'),
            desc: document.getElementById('globalNetworkDesc'),
            retryBtn: document.getElementById('globalNetworkRetryBtn'),
        };
    }

    window.showOfflineBanner = function(customMessage) {
        const els = getBannerEls();
        if (!els.banner) return;
        if (hideTimer) { clearTimeout(hideTimer); hideTimer = null; }

        els.banner.classList.remove('hidden', 'border-emerald-300', 'bg-emerald-50');
        els.banner.classList.add('border-rose-300', 'bg-rose-50');
        if (els.icon) {
            els.icon.className = 'flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-rose-100 text-rose-700 font-bold text-sm';
            els.icon.textContent = '!';
        }
        if (els.title) {
            els.title.className = 'text-sm font-bold text-rose-900';
            els.title.textContent = 'Không thể kết nối với Server';
        }
        if (els.desc) {
            els.desc.className = 'text-xs text-rose-700';
            els.desc.textContent = customMessage || 'Không thể kết nối với Server. Vui lòng kiểm tra lại mạng!';
        }
        if (els.retryBtn) els.retryBtn.classList.remove('hidden');
    };

    window.showOnlineRestoredBanner = function() {
        const els = getBannerEls();
        if (!els.banner || els.banner.classList.contains('hidden')) return;

        els.banner.classList.remove('border-rose-300', 'bg-rose-50');
        els.banner.classList.add('border-emerald-300', 'bg-emerald-50');
        if (els.icon) {
            els.icon.className = 'flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-emerald-100 text-emerald-700 font-bold text-sm';
            els.icon.textContent = '✓';
        }
        if (els.title) {
            els.title.className = 'text-sm font-bold text-emerald-900';
            els.title.textContent = 'Đã kết nối lại với Server';
        }
        if (els.desc) {
            els.desc.className = 'text-xs text-emerald-700';
            els.desc.textContent = 'Kết nối mạng đã ổn định trở lại.';
        }
        if (els.retryBtn) els.retryBtn.classList.add('hidden');

        if (hideTimer) clearTimeout(hideTimer);
        hideTimer = setTimeout(window.hideNetworkBanner, 3000);
    };

    window.hideNetworkBanner = function() {
        const els = getBannerEls();
        if (els.banner) els.banner.classList.add('hidden');
    };

    window.retryServerConnection = async function() {
        if (!navigator.onLine) {
            window.showOfflineBanner('Thiết bị đang ngoại tuyến. Không thể kết nối với Server, vui lòng kiểm tra lại mạng!');
            return;
        }
        try {
            const resp = await originalFetch(window.location.href, { method: 'HEAD', cache: 'no-store' });
            if (resp) {
                window.showOnlineRestoredBanner();
            }
        } catch (e) {
            window.showOfflineBanner('Không thể kết nối với Server. Vui lòng kiểm tra lại mạng!');
        }
    };

    // 1. Lắng nghe sự kiện mất mạng / có mạng của trình duyệt
    window.addEventListener('offline', function() {
        window.showOfflineBanner('Không thể kết nối với Server. Vui lòng kiểm tra lại mạng!');
    });

    window.addEventListener('online', function() {
        window.retryServerConnection();
    });

    if (typeof navigator !== 'undefined' && navigator.onLine === false) {
        document.addEventListener('DOMContentLoaded', function() {
            window.showOfflineBanner('Không thể kết nối với Server. Vui lòng kiểm tra lại mạng!');
        });
    }

    // 2. Tự động bắt lỗi mất mạng trên mọi lệnh fetch() toàn hệ thống
    const originalFetch = window.fetch.bind(window);
    window.fetch = async function(...args) {
        if (typeof navigator !== 'undefined' && navigator.onLine === false) {
            window.showOfflineBanner('Không thể kết nối với Server. Vui lòng kiểm tra lại mạng!');
            throw new TypeError('Không thể kết nối với Server. Vui lòng kiểm tra lại mạng!');
        }
        try {
            const res = await originalFetch(...args);
            const els = getBannerEls();
            if (els.banner && !els.banner.classList.contains('hidden') && els.banner.classList.contains('bg-rose-50')) {
                window.showOnlineRestoredBanner();
            }
            return res;
        } catch (err) {
            if (err && err.name !== 'AbortError') {
                window.showOfflineBanner('Không thể kết nối với Server. Vui lòng kiểm tra lại mạng!');
            }
            throw err;
        }
    };
})();
</script>
