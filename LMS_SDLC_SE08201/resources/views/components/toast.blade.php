<script>
    function showToast(message, type = 'success') {
        let container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'fixed top-4 right-0 z-[100] flex flex-col gap-2 p-4 pointer-events-none overflow-hidden';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = 'transform transition-all duration-300 translate-x-full opacity-0 max-w-sm w-auto min-w-[250px] bg-white shadow-xl rounded-xl border-l-4 p-4 pointer-events-auto flex items-center gap-3';
        
        if (type === 'success') {
            toast.classList.add('border-emerald-500');
            toast.innerHTML = `<div class="text-xl">✅</div><div class="text-sm font-semibold text-slate-800">${message}</div>`;
        } else {
            toast.classList.add('border-rose-500');
            toast.innerHTML = `<div class="text-xl">⚠️</div><div class="text-sm font-semibold text-slate-800">${message}</div>`;
        }

        container.appendChild(toast);

        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                toast.classList.remove('translate-x-full', 'opacity-0');
            });
        });

        setTimeout(() => {
            toast.classList.add('translate-x-full', 'opacity-0');
            toast.addEventListener('transitionend', () => {
                toast.remove();
            });
        }, 3000);
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Xóa thông báo lỗi tĩnh của Laravel nếu có để tránh trùng lặp
        const staticAlerts = document.querySelectorAll('.bg-emerald-50, .bg-rose-50');
        staticAlerts.forEach(alert => {
            if (alert.textContent.includes('✅') || alert.textContent.includes('⚠️')) {
                alert.style.display = 'none';
            }
        });

        // Tự động hiện Toast từ session của Laravel
        @if(session('success'))
            showToast("{!! addslashes(session('success')) !!}", 'success');
        @endif

        @if(session('error'))
            showToast("{!! addslashes(session('error')) !!}", 'error');
        @endif

        @if($errors->any())
            @foreach($errors->all() as $error)
                showToast("{!! addslashes($error) !!}", 'error');
            @endforeach
        @endif
    });
</script>
