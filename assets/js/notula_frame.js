/* SOKAB — pemuat iframe halaman Generate Notula, tinggi mengikuti isi. */

(function () {
    /**
     * Samakan tinggi iframe dengan tinggi isinya, supaya tidak ada dua area
     * gulir bertumpuk. Halaman di dalam iframe satu origin dengan dashboard,
     * jadi tingginya bisa dibaca langsung tanpa postMessage.
     */
    window.pasangAutoTinggi = function (iframe, tinggiMin) {
        tinggiMin = tinggiMin || 400;
        var ro = null;

        function ukur() {
            try {
                var d = iframe.contentDocument;
                if (!d || !d.body) return;
                var h = Math.max(
                    d.body.scrollHeight, d.body.offsetHeight,
                    d.documentElement.scrollHeight, d.documentElement.offsetHeight
                );
                if (h < tinggiMin) h = tinggiMin;
                if (Math.abs(parseInt(iframe.style.height || '0', 10) - h) > 2) {
                    iframe.style.height = h + 'px';
                }
            } catch (e) { /* beda origin — biarkan tinggi tetap */ }
        }

        iframe.addEventListener('load', function () {
            try {
                var d = iframe.contentDocument;
                // matikan scrollbar dalam; tinggi diatur dari luar
                d.documentElement.style.overflowY = 'hidden';
                d.body.style.overflowY = 'hidden';

                if (ro) ro.disconnect();
                if (window.ResizeObserver) {
                    ro = new ResizeObserver(ukur);
                    ro.observe(d.body);
                }
                // konten yang muncul belakangan (fetch, gambar) ikut terukur
                d.addEventListener('click', function () { setTimeout(ukur, 150); });
            } catch (e) { /* abaikan */ }

            ukur();
            [50, 200, 500, 1000, 2000].forEach(function (t) { setTimeout(ukur, t); });
        });

        // jaring pengaman kalau ResizeObserver tidak tersedia
        setInterval(ukur, 1000);
    };

    function loadNotulaKinerja() {
        var container = document.getElementById('content-notula-kinerja');
        if (!container || container.querySelector('iframe')) return;

        container.innerHTML = '';
        var loading = document.createElement('div');
        loading.className = 'loading-state';
        loading.textContent = 'Memuat...';
        container.appendChild(loading);

        var iframe = document.createElement('iframe');
        iframe.src = 'pages/capaian/notula.php';
        iframe.setAttribute('scrolling', 'no');
        iframe.style.cssText = 'width:100%;height:400px;border:none;display:none;overflow:hidden;';
        window.pasangAutoTinggi(iframe, 400);
        iframe.onload = function () {
            if (loading.parentNode) loading.parentNode.removeChild(loading);
            iframe.style.display = 'block';
        };
        iframe.onerror = function () {
            loading.textContent = 'Gagal memuat halaman Generate Notula';
        };
        container.appendChild(iframe);
    }

    function pasangHook() {
        if (typeof window.showPage !== 'function') {
            console.warn('[notula] showPage belum tersedia, hook dilewati');
            return;
        }
        var asli = window.showPage;
        window.showPage = function (pageId) {
            var hasil = asli.apply(this, arguments);
            if (pageId === 'notula-kinerja') loadNotulaKinerja();
            return hasil;
        };
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', pasangHook);
    } else {
        pasangHook();
    }
})();
