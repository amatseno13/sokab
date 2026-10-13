<?php /* Halaman: lakin-final */ ?>
            <div id="page-lakin-final" class="content-page">
                <div class="page-header-bar">
                    <div class="page-header-icon"><svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10" /><path d="m9 12 2 2 4-4" /></svg></div>
                    <div><h2 class="page-title">LAKIN — Final</h2><p class="page-subtitle">LAKIN Final yang telah disahkan</p></div>
<?php if($user_role==='admin'): ?>
                    <button class="btn-upload-lakin" onclick="bukaUploadLakin('final')">⬆️ Upload File</button>
                    <?php endif; ?>
                </div>
                <div id="content-lakin-final" class="lakin-container">
                    <div class="loading-state">Memuat file...</div>
                </div>
            </div>
