<?php /* Halaman: lakin-draft */ ?>
            <div id="page-lakin-draft" class="content-page">
                <div class="page-header-bar">
                    <div class="page-header-icon"><svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12.5 22H18a2 2 0 0 0 2-2V7l-5-5H6a2 2 0 0 0-2 2v9.5" /><path d="M14 2v4a2 2 0 0 0 2 2h4" /><path d="M13.378 15.626a1 1 0 1 0-3.004-3.004l-5.01 5.012a2 2 0 0 0-.506.854l-.837 2.87a.5.5 0 0 0 .62.62l2.87-.837a2 2 0 0 0 .854-.506z" /></svg></div>
                    <div><h2 class="page-title">LAKIN — Draft</h2><p class="page-subtitle">Draft Laporan Akuntabilitas Kinerja Instansi Pemerintah</p></div>
<?php if($user_role==='admin'): ?>
                    <button class="btn-upload-lakin" onclick="bukaUploadLakin('draft')">⬆️ Upload File</button>
                    <?php endif; ?>
                </div>
                <div id="content-lakin-draft" class="lakin-container">
                    <div class="loading-state">Memuat file...</div>
                </div>
            </div>
