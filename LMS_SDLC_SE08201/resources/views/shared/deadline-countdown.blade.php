{{--
    Đếm ngược deadline dùng chung.
    Cách dùng: đặt class "deadline-countdown" + thuộc tính data-deadline="{{ $x->due_date->toIso8601String() }}"
    lên 1 phần tử inline (span). Script sẽ cập nhật nội dung mỗi giây và tô màu cảnh báo:
      • Dưới 2 giờ  → đỏ + nhấp nháy
      • Dưới 24 giờ → cam
      • Còn lại     → hổ phách
--}}
<script>
(function () {
    var els = [].slice.call(document.querySelectorAll('.deadline-countdown'));
    if (!els.length) return;

    var WARN = ['bg-rose-100', 'text-rose-700', 'bg-orange-100', 'text-orange-700',
                'bg-amber-100', 'text-amber-700', 'bg-slate-100', 'text-slate-500', 'animate-pulse'];

    function fmt(ms) {
        var s = Math.floor(ms / 1000);
        var d = Math.floor(s / 86400); s -= d * 86400;
        var h = Math.floor(s / 3600);  s -= h * 3600;
        var m = Math.floor(s / 60);    s -= m * 60;
        if (d > 0) return d + ' ngày ' + h + ' giờ';
        if (h > 0) return h + ' giờ ' + m + ' phút';
        if (m > 0) return m + ' phút ' + s + ' giây';
        return s + ' giây';
    }

    function tick() {
        var now = Date.now();
        els.forEach(function (el) {
            var due = new Date(el.dataset.deadline).getTime();
            var left = due - now;
            el.classList.remove.apply(el.classList, WARN);

            if (left <= 0) {
                el.textContent = '⌛ Đã hết hạn';
                el.classList.add('bg-slate-100', 'text-slate-500');
                return;
            }
            var hours = left / 3600000;
            if (hours < 2) {
                el.textContent = '🔴 Còn ' + fmt(left);
                el.classList.add('bg-rose-100', 'text-rose-700', 'animate-pulse');
            } else if (hours < 24) {
                el.textContent = '🟠 Còn ' + fmt(left);
                el.classList.add('bg-orange-100', 'text-orange-700');
            } else {
                el.textContent = '⏰ Còn ' + fmt(left);
                el.classList.add('bg-amber-100', 'text-amber-700');
            }
        });
    }

    tick();
    setInterval(tick, 1000);
})();
</script>
