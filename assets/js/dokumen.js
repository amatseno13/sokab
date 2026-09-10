/* SOKAB — dokumen (Drive/tautan): daftar, filter, unggah, hapus.
   Termasuk pemuat iframe halaman Entry Capaian Kinerja. */

function loadCapaianKinerja() {
            var container = document.getElementById('content-capaian-kinerja');
            if (container.querySelector('iframe')) return;   // sudah dimuat

            // Kosongkan container SEBELUM iframe dipasang. Jangan pernah memanggil
            // container.innerHTML = '' di dalam onload: iframe adalah anak container,
            // jadi ia ikut terlepas lalu terpasang ulang, dan browser memuat ulang
            // isinya dari nol — onload menyala lagi, berulang, area jadi kosong.
            container.innerHTML = '';
            var loading = document.createElement('div');
            loading.className = 'loading-state';
            loading.textContent = 'Memuat...';
            container.appendChild(loading);

            var iframe = document.createElement('iframe');
            iframe.src = 'pages/capaian/index.php';
            iframe.setAttribute('scrolling', 'no');
            iframe.style.cssText = 'width:100%;height:400px;border:none;display:none;overflow:hidden;';
            if (window.pasangAutoTinggi) window.pasangAutoTinggi(iframe, 400);
            iframe.onload = function() {
                if (loading.parentNode) loading.parentNode.removeChild(loading);
                iframe.style.display = 'block';
            };
            iframe.onerror = function() {
                loading.textContent = 'Gagal memuat halaman Capaian Kinerja';
            };
            container.appendChild(iframe);
        }
        

        // ── GDRIVE LOADER ──────────────────────────────
        function loadGdriveContent(pageId, menuKey, forceReload) {
            const container = document.getElementById('content-' + pageId);
            if (!container) return;

            if (dokumenCache[menuKey] && !forceReload) {
                renderGdrive(container, menuKey, dokumenCache[menuKey]);
                return;
            }

            container.innerHTML = '<div class="loading-state">⏳ Memuat dokumen...</div>';

            fetch('api/dokumen.php?menu=' + menuKey)
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        dokumenCache[menuKey] = res.data;
                        renderGdrive(container, menuKey, res.data);
                    } else {
                        container.innerHTML = '<div class="loading-state">⚠️ Gagal memuat: ' + escHtml(res.message) + '</div>';
                    }
                })
                .catch(() => container.innerHTML = '<div class="loading-state">⚠️ Koneksi gagal</div>');
        }

        // State filter aktif per menuKey
        const filterState = {};

        function renderGdrive(container, menuKey, data) {
            const tahunSet = [...new Set(data.map(d => d.tahun).filter(Boolean))].sort((a,b) => b-a);
            const isMonKin = (menuKey === 'monitoring_kinerja');

            // Baris filter triwulan (khusus monitoring_kinerja)
            const twRow = isMonKin ? `
                <div class="gdrive-filter tw-filter" id="filter-tw-${menuKey}" style="margin-top:0.5rem">
                    <button class="filter-tahun active" onclick="filterTriwulan('${menuKey}', null, this)">Semua TW</button>
                    <button class="filter-tahun" onclick="filterTriwulan('${menuKey}', 'TW1', this)">TW I</button>
                    <button class="filter-tahun" onclick="filterTriwulan('${menuKey}', 'TW2', this)">TW II</button>
                    <button class="filter-tahun" onclick="filterTriwulan('${menuKey}', 'TW3', this)">TW III</button>
                    <button class="filter-tahun" onclick="filterTriwulan('${menuKey}', 'TW4', this)">TW IV</button>
                </div>` : '';

            const toolbar = `
                <div class="gdrive-toolbar" style="flex-direction:column;align-items:flex-start;gap:0.5rem">
                    <div style="display:flex;justify-content:space-between;align-items:center;width:100%">
                        <div class="gdrive-filter" id="filter-${menuKey}">
                            <button class="filter-tahun active" onclick="filterTahun('${menuKey}', null, this)">Semua</button>
                            ${tahunSet.map(t => `<button class="filter-tahun" onclick="filterTahun('${menuKey}', ${t}, this)">${t}</button>`).join('')}
                        </div>
                        ${isAdmin ? `<button class="btn-tambah-dok" onclick="bukaGdriveModal('${menuKey}')">+ Tambah</button>` : ''}
                    </div>
                    ${twRow}
                </div>`;

            const grid = `<div class="dok-grid" id="grid-${menuKey}"></div>`;
            container.innerHTML = toolbar + grid;

            // Reset state filter
            filterState[menuKey] = { tahun: null, tw: null };
            applyFilter(menuKey);
        }

        function filterTahun(menuKey, tahun, btn) {
            btn.closest('.gdrive-filter').querySelectorAll('.filter-tahun').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            if (!filterState[menuKey]) filterState[menuKey] = {};
            filterState[menuKey].tahun = tahun;
            applyFilter(menuKey);
        }

        function filterTriwulan(menuKey, tw, btn) {
            btn.closest('.tw-filter').querySelectorAll('.filter-tahun').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            if (!filterState[menuKey]) filterState[menuKey] = {};
            filterState[menuKey].tw = tw;
            applyFilter(menuKey);
        }

        function applyFilter(menuKey) {
            const state  = filterState[menuKey] || {};
            const data   = dokumenCache[menuKey] || [];
            let filtered = data;

            if (state.tahun) filtered = filtered.filter(d => d.tahun == state.tahun);
            if (state.tw)    filtered = filtered.filter(d => (d.sub_kategori || '').startsWith(state.tw));

            renderDokGrid(menuKey, filtered, state.tahun);
        }

        function renderDokGrid(menuKey, data, tahun) {
            const grid = document.getElementById('grid-' + menuKey);
            if (!grid) return;

            if (!data.length) {
                grid.innerHTML = `<div class="empty-dok"><div class="empty-icon">📂</div><div>Belum ada dokumen${tahun ? ' untuk tahun ' + tahun : ''}</div>
                    ${isAdmin ? `<br><button class="btn-tambah-dok" onclick="bukaGdriveModal('${menuKey}')">+ Tambah Dokumen</button>` : ''}</div>`;
                return;
            }

            grid.innerHTML = data.map(d => {
                const uploadMethod = d.upload_method || 'gdrive';
                const methodBadge = uploadMethod === 'file' 
                    ? '<span class="badge-file">📁 File</span>' 
                    : '<span class="badge-gdrive">☁️ Link</span>';
                const btnAction = uploadMethod === 'file'
                    ? `<a href="api/dokumen.php?action=download&id=${d.id}" class="btn-buka-gdrive" target="_blank">⬇️ Download</a>`
                    : `<a href="${escHtml(d.url_gdrive || '')}" target="_blank" class="btn-buka-gdrive">🔗 Buka</a>`;
                
                return `
                <div class="dok-card">
                    <div class="dok-card-header">
                        <div class="dok-card-title">${escHtml(d.judul)} ${methodBadge}</div>
                        ${d.tahun ? `<span class="dok-card-tahun">${d.tahun}</span>` : ''}
                    </div>
                    ${d.sub_kategori ? (() => {
                        if (menuKey === 'monitoring_kinerja') {
                            const parts = d.sub_kategori.split('|');
                            const tw  = parts[0] ? `<span style="background:#ebf8ff;color:#1e3a8a;padding:2px 8px;border-radius:10px;font-size:0.7rem;font-weight:700;margin-right:4px">${escHtml(parts[0])}</span>` : '';
                            const kat = parts[1] ? `<span style="font-size:0.74rem;color:#0ea5e9;font-weight:600">${escHtml(parts[1])}</span>` : '';
                            return `<div style="display:flex;align-items:center;gap:4px;flex-wrap:wrap">${tw}${kat}</div>`;
                        }
                        return `<div style="font-size:0.74rem;color:#0ea5e9;font-weight:600">${escHtml(d.sub_kategori)}</div>`;
                    })() : ''}
                    ${d.keterangan ? `<div class="dok-card-ket">${escHtml(d.keterangan)}</div>` : ''}
                    ${uploadMethod === 'file' && d.filesize_human ? `<div style="font-size:0.75rem;color:#666;margin-top:4px">📊 ${d.filesize_human}</div>` : ''}
                    <div class="dok-card-footer">
                        ${btnAction}
                        ${isAdmin ? `<div class="dok-admin-actions">
                            <button class="btn-dok-edit" onclick="editDokumen(${d.id},'${menuKey}')" title="Edit">✏️</button>
                            <button class="btn-dok-hapus" onclick="hapusDokumen(${d.id},'${menuKey}')" title="Hapus">🗑️</button>
                        </div>` : ''}
                    </div>
                </div>`;
            }).join('');
        }

        // ── MODAL GDRIVE ────────────────────────────────
        let currentMenuKey = null;

        function bukaGdriveModal(menuKey, editData) {
            currentMenuKey = menuKey;
            document.getElementById('gdrivemodalMenuKey').value = menuKey;
            document.getElementById('gdrivemodalId').value = editData ? editData.id : '';
            document.getElementById('gdrivemodalTitle').textContent = editData ? 'Edit Dokumen' : 'Tambah Dokumen';
            document.getElementById('gdrivemodalJudul').value  = editData ? editData.judul : '';
            document.getElementById('gdrivemodalTahun').value  = editData ? (editData.tahun || '') : new Date().getFullYear();
            document.getElementById('gdrivemodalUrl').value    = editData ? (editData.url_gdrive || '') : '';
            document.getElementById('gdrivemodalKet').value    = editData ? (editData.keterangan || '') : '';

            // Tampilkan field Triwulan hanya untuk monitoring_kinerja
            const isMonKinerja = (menuKey === 'monitoring_kinerja');
            document.getElementById('fieldTriwulan').style.display = isMonKinerja ? '' : 'none';

            if (isMonKinerja) {
                // sub_kategori format: "TW1|Nama Kategori" atau hanya triwulan
                const parts = editData ? (editData.sub_kategori || '').split('|') : ['', ''];
                document.getElementById('gdrivemodalTriwulan').value = parts[0] || '';
                document.getElementById('gdrivemodalSubkat').value   = parts[1] || '';
            } else {
                document.getElementById('gdrivemodalTriwulan').value = '';
                document.getElementById('gdrivemodalSubkat').value   = editData ? (editData.sub_kategori || '') : '';
            }

            // Reset upload method toggle & hide saat edit (tidak bisa ganti metode saat edit)
            const uploadMethodGroup = document.getElementById('dokumenUploadMethodGroup');
            if (editData) {
                // Hide upload method selection saat edit
                uploadMethodGroup.style.display = 'none';
                document.getElementById('dokumenFileUploadArea').style.display = 'none';
                document.getElementById('dokumenGdriveUploadArea').style.display = 'none';
            } else {
                // Show upload method selection & reset ke Link default
                uploadMethodGroup.style.display = 'block';
                toggleDokumenUploadMethod('gdrive');
            }

            document.getElementById('gdrivemodalOverlay').classList.add('show');
        }

        function tutupGdriveModal() {
            document.getElementById('gdrivemodalOverlay').classList.remove('show');
        }

        function simpanDokumen() {
            const id = document.getElementById('gdrivemodalId').value;
            const menuKey = document.getElementById('gdrivemodalMenuKey').value;
            const judul = document.getElementById('gdrivemodalJudul').value.trim();
            const uploadMethod = document.querySelector('#dokumenUploadMethodGroup .method-btn.active')?.dataset.method || 'gdrive';

            if (!judul) { 
                showToast('Judul wajib diisi', 'error'); 
                return; 
            }

            // Validasi berdasarkan metode
            if (uploadMethod === 'file') {
                const file = document.getElementById('gdrivemodalFile').files[0];
                if (!file && !id) { // Jika tambah baru (bukan edit)
                    showToast('File harus dipilih', 'error'); 
                    return; 
                }
            } else {
                const url = document.getElementById('gdrivemodalUrl').value.trim();
                if (!url) { 
                    showToast('URL Link harus diisi', 'error'); 
                    return; 
                }
                if (false && !url.includes('drive.google.com')) {
                    showToast('Link tidak valid', 'error'); 
                    return;
                }
            }

            // Gabungkan triwulan + kategori jika monitoring kinerja
            let subKat = document.getElementById('gdrivemodalSubkat').value.trim();
            if (menuKey === 'monitoring_kinerja') {
                const tw = document.getElementById('gdrivemodalTriwulan').value;
                subKat = tw ? tw + (subKat ? '|' + subKat : '') : subKat;
            }

            // Jika edit, gunakan JSON (tidak upload file ulang)
            if (id) {
                const payload = {
                    id, 
                    judul,
                    tahun: parseInt(document.getElementById('gdrivemodalTahun').value) || null,
                    sub_kategori: subKat,
                    keterangan: document.getElementById('gdrivemodalKet').value.trim(),
                };

                fetch('api/dokumen.php?action=edit', { 
                    method:'POST', 
                    headers:{'Content-Type':'application/json'}, 
                    body: JSON.stringify(payload) 
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        showToast(res.message, 'success');
                        tutupGdriveModal();
                        delete dokumenCache[menuKey];
                        const pageId = Object.keys(PAGE_MENU_MAP).find(k => PAGE_MENU_MAP[k] === menuKey);
                        if (pageId) loadGdriveContent(pageId, menuKey, true);
                    } else { showToast(res.message, 'error'); }
                });
                return;
            }

            // Jika tambah baru, gunakan FormData
            const fd = new FormData();
            fd.append('action', 'tambah');
            fd.append('menu_key', menuKey);
            fd.append('judul', judul);
            fd.append('tahun', document.getElementById('gdrivemodalTahun').value || '');
            fd.append('sub_kategori', subKat);
            fd.append('keterangan', document.getElementById('gdrivemodalKet').value.trim());
            fd.append('upload_method', uploadMethod);

            if (uploadMethod === 'file') {
                const file = document.getElementById('gdrivemodalFile').files[0];
                fd.append('file', file);
            } else {
                const url = document.getElementById('gdrivemodalUrl').value.trim();
                fd.append('url_gdrive', url);
            }

            fetch('api/dokumen.php', { method:'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        showToast(res.message, 'success');
                        tutupGdriveModal();
                        delete dokumenCache[menuKey];
                        const pageId = Object.keys(PAGE_MENU_MAP).find(k => PAGE_MENU_MAP[k] === menuKey);
                        if (pageId) loadGdriveContent(pageId, menuKey, true);
                    } else { showToast(res.message, 'error'); }
                });
        }

        function toggleDokumenUploadMethod(method) {
            // Toggle active button
            document.querySelectorAll('#dokumenUploadMethodGroup .method-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.method === method);
            });
            
            // Toggle upload areas
            const fileArea = document.getElementById('dokumenFileUploadArea');
            const gdriveArea = document.getElementById('dokumenGdriveUploadArea');
            
            if (method === 'file') {
                fileArea.style.display = 'block';
                gdriveArea.style.display = 'none';
                document.getElementById('gdrivemodalUrl').value = '';
            } else {
                fileArea.style.display = 'none';
                gdriveArea.style.display = 'block';
                document.getElementById('gdrivemodalFile').value = '';
                document.getElementById('dokumenFileDisplay').textContent = '📎 Klik untuk pilih file';
            }
        }

        function updateDokumenFileDisplay() {
            const input = document.getElementById('gdrivemodalFile');
            const display = document.getElementById('dokumenFileDisplay');
            if (input.files && input.files[0]) {
                const fileName = input.files[0].name;
                const fileSize = (input.files[0].size / 1024 / 1024).toFixed(2); // MB
                display.textContent = `📎 ${fileName} (${fileSize} MB)`;
            } else {
                display.textContent = '📎 Klik untuk pilih file';
            }
        }

        function editDokumen(id, menuKey) {
            const item = (dokumenCache[menuKey] || []).find(d => d.id == id);
            if (item) bukaGdriveModal(menuKey, item);
        }

        function hapusDokumen(id, menuKey) {
            if (!confirm('Yakin hapus dokumen ini?')) return;
            fetch('api/dokumen.php?action=hapus', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id}) })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        showToast(res.message, 'success');
                        delete dokumenCache[menuKey];
                        const pageId = Object.keys(PAGE_MENU_MAP).find(k => PAGE_MENU_MAP[k] === menuKey);
                        if (pageId) loadGdriveContent(pageId, menuKey, true);
                    } else { showToast(res.message, 'error'); }
                });
        }

        // ── LAKIN UPLOAD ────────────────────────────────
