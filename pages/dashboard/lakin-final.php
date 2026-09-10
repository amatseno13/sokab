<?php /* Halaman: lakin-final */ ?>
            <div id="page-lakin-final" class="content-page">
                <div class="page-header-bar">
                    <div class="page-header-icon" style="background:#c6f6d5">✅</div>
                    <div><h2 class="page-title">LAKIN — Final</h2><p class="page-subtitle">LAKIN Final yang telah disahkan</p></div>
<?php if($user_role==='admin'): ?>
                    <button class="btn-upload-lakin" onclick="bukaUploadLakin('final')">⬆️ Upload File</button>
                    <?php endif; ?>
                </div>
                <div id="content-lakin-final" class="lakin-container">
                    <div class="loading-state">Memuat file...</div>
                </div>
            </div>
