/* SOKAB — LAKIN draft & final: daftar, unggah, hapus. */

function loadLakinContent(tipe) {
            const container = document.getElementById('content-lakin-' + tipe);
            if (!container) {
                console.error('LAKIN container not found:', 'content-lakin-' + tipe);
                return;
            }
            container.innerHTML = '<div class="loading-state">⏳ Memuat file...</div>';

            console.log(`📥 Loading LAKIN ${tipe}...`);
            fetch(`api/lakin.php?tipe=${tipe}`)
                .then(r => {
                    console.log('LAKIN response status:', r.status);
                    return r.json();
                })
                .then(res => {
                    console.log('LAKIN data:', res);
                    if (res.success) {
                        lakinCache[tipe] = res.data;
                        console.log(`✅ LAKIN ${tipe}: ${res.data.length} files loaded`);
                        renderLakinList(tipe, res.data);
                    } else {
                        console.error('LAKIN error:', res.message);
                        container.innerHTML = `<div class="loading-state">⚠️ Gagal memuat: ${escHtml(res.message || 'Unknown error')}</div>`;
                    }
                })
                .catch(err => {
                    console.error('LAKIN fetch error:', err);
                    container.innerHTML = `<div class="loading-state">⚠️ Koneksi gagal. Cek Console (F12) untuk detail.</div>`;
                });
        }

        function renderLakinList(tipe, data) {
            const container = document.getElementById('content-lakin-' + tipe);
            if (!container) return;

            // Filter tahun
            const tahunSet = [...new Set(data.map(d => d.tahun))].sort((a,b) => b-a);
            const toolbar = `
                <div class="gdrive-toolbar">
                    <div class="gdrive-filter" id="lakin-filter-${tipe}">
                        <button class="filter-tahun active" onclick="filterLakinTahun('${tipe}', null, this)">Semua</button>
                        ${tahunSet.map(t => `<button class="filter-tahun" onclick="filterLakinTahun('${tipe}', ${t}, this)">${t}</button>`).join('')}
                    </div>
                </div>`;

            const list = `<div id="lakin-list-${tipe}" class="lakin-container"></div>`;
            container.innerHTML = toolbar + list;
            renderLakinItems(tipe, data, null);
        }

        function filterLakinTahun(tipe, tahun, btn) {
            btn.closest('.gdrive-filter').querySelectorAll('.filter-tahun').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const data = (lakinCache[tipe] || []).filter(d => tahun ? d.tahun == tahun : true);
            renderLakinItems(tipe, data, tahun);
        }

        function renderLakinItems(tipe, data, tahun) {
            const el = document.getElementById('lakin-list-' + tipe);
            if (!el) return;
            if (!data.length) {
                el.innerHTML = `<div class="empty-dok"><div class="empty-icon">📄</div><div>Belum ada file ${tipe}</div></div>`;
                return;
            }
            const extIcon = {pdf:'📕', doc:'📘', docx:'📘', xlsx:'📗', xls:'📗', gdrive:'☁️'};
            el.innerHTML = data.map(f => {
                const uploadMethod = f.upload_method || 'file';
                const ext = uploadMethod === 'gdrive' ? 'gdrive' : f.original_name.split('.').pop().toLowerCase();
                const icon = extIcon[ext] || '📄';
                const methodBadge = uploadMethod === 'gdrive' 
                    ? '<span class="badge-gdrive">☁️ Link</span>' 
                    : '<span class="badge-file">📁 File</span>';
                return `
                <div class="lakin-card">
                    <div class="lakin-file-icon">${icon}</div>
                    <div class="lakin-file-body">
                        <div class="lakin-file-title">${escHtml(f.judul)} ${methodBadge}</div>
                        <div class="lakin-file-meta">
                            ${f.tahun} · ${f.original_name} ${uploadMethod === 'file' ? '· ' + f.filesize_human : ''}
                            ${f.keterangan ? ' · ' + escHtml(f.keterangan) : ''}
                        </div>
                    </div>
                    <div class="lakin-file-actions">
                        <a href="api/lakin.php?action=download&id=${f.id}" class="btn-download" target="_blank">
                            ${uploadMethod === 'gdrive' ? '🔗 Buka' : '⬇️ Download'}
                        </a>
                        ${isAdmin ? `<button class="btn-hapus-lakin" onclick="hapusLakin(${f.id},'${tipe}')" title="Hapus">🗑️</button>` : ''}
                    </div>
                </div>`;
            }).join('');
        }

        function bukaUploadLakin(tipe) {
            document.getElementById('lakinUploadTipe').value = tipe;
            document.getElementById('lakinUploadTitle').textContent = 'Upload LAKIN ' + (tipe === 'draft' ? 'Draft' : 'Final');
            document.getElementById('lakinJudul').value = '';
            document.getElementById('lakinTahun').value = new Date().getFullYear();
            document.getElementById('lakinKet').value   = '';
            document.getElementById('lakinFile').value  = '';
            document.getElementById('fileDisplay').textContent = '📎 Klik untuk pilih file';
            document.getElementById('fileDisplay').classList.remove('has-file');
            document.getElementById('lakinUploadOverlay').classList.add('show');
        }

        function tutupUploadLakin() {
            document.getElementById('lakinUploadOverlay').classList.remove('show');
        }

        function updateFileDisplay() {
            const file = document.getElementById('lakinFile').files[0];
            const disp = document.getElementById('fileDisplay');
            if (file) {
                disp.textContent = '✅ ' + file.name;
                disp.classList.add('has-file');
            } else {
                disp.textContent = '📎 Klik untuk pilih file';
                disp.classList.remove('has-file');
            }
        }

        function uploadLakin() {
            const tipe   = document.getElementById('lakinUploadTipe').value;
            const judul  = document.getElementById('lakinJudul').value.trim();
            const tahun  = document.getElementById('lakinTahun').value;
            const ket    = document.getElementById('lakinKet').value.trim();
            const uploadMethod = document.querySelector('.method-btn.active').dataset.method;

            if (!judul || !tahun) { 
                showToast('Judul dan tahun wajib diisi', 'error'); 
                return; 
            }

            // Validasi berdasarkan metode
            if (uploadMethod === 'file') {
                const file = document.getElementById('lakinFile').files[0];
                if (!file) { 
                    showToast('File harus dipilih', 'error'); 
                    return; 
                }
            } else {
                const gdriveLink = document.getElementById('lakinGdriveLink').value.trim();
                if (!gdriveLink) { 
                    showToast('Link Cloud Storage harus diisi', 'error'); 
                    return; 
                }
                if (false && !gdriveLink.includes('drive.google.com')) {
                    showToast('Link tidak valid', 'error'); 
                    return;
                }
            }

            const btn = document.getElementById('btnUploadLakin');
            btn.textContent = '⏳ Mengupload...';
            btn.disabled = true;

            const fd = new FormData();
            fd.append('action', 'upload');
            fd.append('judul', judul);
            fd.append('tahun', tahun);
            fd.append('tipe', tipe);
            fd.append('keterangan', ket);
            fd.append('upload_method', uploadMethod);

            if (uploadMethod === 'file') {
                const file = document.getElementById('lakinFile').files[0];
                fd.append('file', file);
            } else {
                const gdriveLink = document.getElementById('lakinGdriveLink').value.trim();
                fd.append('gdrive_link', gdriveLink);
            }

            fetch('api/lakin.php', { method:'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    btn.textContent = '⬆️ Upload';
                    btn.disabled = false;
                    if (res.success) {
                        showToast('LAKIN berhasil diupload!', 'success');
                        tutupUploadLakin();
                        lakinCache[tipe] = null;
                        loadLakinContent(tipe);
                    } else { showToast(res.message, 'error'); }
                })
                .catch(() => { btn.textContent = '⬆️ Upload'; btn.disabled = false; showToast('Upload gagal', 'error'); });
        }

        function toggleUploadMethod(method) {
            // Toggle active button
            document.querySelectorAll('.method-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.method === method);
            });
            
            // Toggle upload areas
            const fileArea = document.getElementById('fileUploadArea');
            const gdriveArea = document.getElementById('gdriveUploadArea');
            
            if (method === 'file') {
                fileArea.style.display = 'block';
                gdriveArea.style.display = 'none';
                document.getElementById('lakinGdriveLink').value = '';
            } else {
                fileArea.style.display = 'none';
                gdriveArea.style.display = 'block';
                document.getElementById('lakinFile').value = '';
                document.getElementById('fileDisplay').textContent = '📎 Klik untuk pilih file';
            }
        }

        function hapusLakin(id, tipe) {
            if (!confirm('Yakin hapus file ini?')) return;
            fetch('api/lakin.php?action=hapus', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id}) })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        showToast(res.message, 'success');
                        lakinCache[tipe] = null;
                        loadLakinContent(tipe);
                    } else { showToast(res.message, 'error'); }
                });
        }

        // Tutup modal klik overlay (with safety check)
        const gdrivemodalOverlay = document.getElementById('gdrivemodalOverlay');
        if (gdrivemodalOverlay) {
            gdrivemodalOverlay.addEventListener('click', function(e) {
                if (e.target === this) tutupGdriveModal();
            });
        }
        
        const lakinUploadOverlay = document.getElementById('lakinUploadOverlay');
        if (lakinUploadOverlay) {
            lakinUploadOverlay.addEventListener('click', function(e) {
                if (e.target === this) tutupUploadLakin();
            });
        }
