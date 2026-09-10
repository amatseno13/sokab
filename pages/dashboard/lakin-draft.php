<?php /* Halaman: lakin-draft */ ?>
            <div id="page-lakin-draft" class="content-page">
                <div class="page-header-bar">
                    <div class="page-header-icon" style="background:#fefcbf">📝</div>
                    <div><h2 class="page-title">LAKIN — Draft</h2><p class="page-subtitle">Draft Laporan Akuntabilitas Kinerja Instansi Pemerintah</p></div>
<?php if($user_role==='admin'): ?>
                    <button class="btn-upload-lakin" onclick="bukaUploadLakin('draft')">⬆️ Upload File</button>
                    <?php endif; ?>
                </div>
                <div id="content-lakin-draft" class="lakin-container">
                    <div class="loading-state">Memuat file...</div>
                </div>
            </div>
