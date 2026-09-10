/* SOKAB — inti navigasi dashboard: showPage, submenu, pemuat konten.
   Nilai dari PHP dibaca lewat window.SOKAB yang disiapkan di dashboard.php. */

function toggleSubmenu(element) {
            const menuItem = element.parentElement;
            const wasOpen  = menuItem.classList.contains('open');

            document.querySelectorAll('.menu-item').forEach(i => i.classList.remove('open'));
            document.querySelectorAll('.menu-link').forEach(l => l.classList.remove('active'));

            if (!wasOpen) {
                menuItem.classList.add('open');
                element.classList.add('active');
            }
        }

        function toggleSubSubmenu(element, event) {
            if (event) event.stopPropagation();
            const wasOpen = element.classList.contains('open');

            const parent = element.closest('.submenu');
            if (parent) {
                parent.querySelectorAll('.submenu-item.has-sub').forEach(i => {
                    if (i !== element) i.classList.remove('open');
                });
            }

            wasOpen ? element.classList.remove('open') : element.classList.add('open');
        }

        function showPage(pageId) {
            document.querySelectorAll('.content-page').forEach(p => p.classList.remove('active'));
            const target = document.getElementById('page-' + pageId);
            if (target) target.classList.add('active');

            document.querySelectorAll('.sub-submenu-item').forEach(i => i.classList.remove('active'));
            document.querySelectorAll('.submenu-item:not(.has-sub)').forEach(i => i.classList.remove('active'));
            document.querySelectorAll('.menu-item.menu-sub .menu-link').forEach(l => l.classList.remove('active'));

            if (event && event.currentTarget) event.currentTarget.classList.add('active');
            window.scrollTo({ top: 0, behavior: 'smooth' });

            // Load konten dinamis
            loadPageContent(pageId);
        }

        // ── Minimize sidebar ────────────────────────────
        const SIDEBAR_KEY = 'sokab_sidebar_collapsed';

        function toggleSidebar() {
            const sidebar   = document.querySelector('.sidebar');
            const container = document.querySelector('.container');
            if (!sidebar || !container) return;

            const collapsed = sidebar.classList.toggle('collapsed');
            container.classList.toggle('sidebar-collapsed', collapsed);
            try { localStorage.setItem(SIDEBAR_KEY, collapsed ? '1' : '0'); } catch (e) { /* diamkan */ }
        }

        document.addEventListener('DOMContentLoaded', () => {
            try {
                if (localStorage.getItem(SIDEBAR_KEY) === '1') {
                    document.querySelector('.sidebar')?.classList.add('collapsed');
                    document.querySelector('.container')?.classList.add('sidebar-collapsed');
                }
            } catch (e) { /* diamkan */ }
        });

        function showHome() {
            document.querySelectorAll('.content-page').forEach(p => p.classList.remove('active'));
            document.getElementById('page-home').classList.add('active');

            document.querySelectorAll('.menu-item').forEach(i => i.classList.remove('open'));
            document.querySelectorAll('.submenu-item').forEach(i => i.classList.remove('open'));
            document.querySelectorAll('.menu-link').forEach(l => l.classList.remove('active'));

            document.querySelector('.menu-item:first-child .menu-link').classList.add('active');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // ═══════════════════════════════════════════════
        // SISTEM DOKUMEN GDRIVE
        // ═══════════════════════════════════════════════
        // isAdmin sudah dideklarasikan di atas

        // Map pageId ke menu_key API
        const PAGE_MENU_MAP = {
            'renstra':             'renstra',
            'perjanjian-kinerja':  'perjanjian_kinerja',
            'monitoring-kinerja':  'monitoring_kinerja',
            'monitoring-renstra':  'monitoring_renstra',
            'evaluasi-permindok':  'evaluasi_permindok',
            'materi-panduan':      'materi_panduan',
        };

        const LAKIN_PAGES = ['lakin-draft', 'lakin-final'];
        let dokumenCache = {};  // cache per menu_key
        let lakinCache   = {};  // cache per tipe

        // Panggil saat showPage
        function loadPageContent(pageId) {
            // IKSS page (monitoring-kinerja)
            if (pageId === 'monitoring-kinerja') {
                // Load IKSS dari ikss_functions.js
                if (typeof loadIKSSData === 'function') {
                    setTimeout(() => loadIKSSData('TW I'), 300);
                }
                // Juga load dokumen monitoring kinerja (kalau ada)
                loadGdriveContent(pageId, 'monitoring_kinerja');
                return;
            }
            
            // Cloud Link pages
            if (PAGE_MENU_MAP[pageId]) {
                loadGdriveContent(pageId, PAGE_MENU_MAP[pageId]);
            }
            // LAKIN pages
            if (pageId === 'lakin-draft')  loadLakinContent('draft');
            if (pageId === 'lakin-final')  loadLakinContent('final');
            if (pageId === 'capaian-kinerja') loadCapaianKinerja();
            // User management page
            if (pageId === 'kelola-user' && isAdmin) loadUsers();
        }

        // ── CAPAIAN KINERJA LOADER ──────────────────────
