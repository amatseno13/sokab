/* SOKAB — kalender & jadwal SAKIP, termasuk notifikasi deadline. */

// ===== DATA & STATE =====
        let semuaJadwal = [];
        let kalender;

        // ===== INIT =====
        document.addEventListener('DOMContentLoaded', function() {
            initKalender();
            loadSemuaJadwal();
            loadUpcoming();
            setTimeout(cekPopupDeadline, 1500);
        });

        // ===== KALENDER =====
        function initKalender() {
            const el = document.getElementById('kalender');
            kalender = new FullCalendar.Calendar(el, {
                initialView: 'dayGridMonth',
                locale: 'id',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,listWeek'
                },
                buttonText: { today: 'Hari Ini', month: 'Bulan', list: 'Daftar' },
                height: 480,
                events: function(info, success, fail) {
                    fetch('api/jadwal.php?action=kalender')
                        .then(r => r.json())
                        .then(res => {
                            if (res.success) {
                                success(res.data.map(j => ({
                                    id: j.id,
                                    title: j.judul,
                                    start: j.start,
                                    end: j.end,
                                    color: j.color,
                                    extendedProps: { status: j.status, kategori: j.kategori }
                                })));
                            }
                        }).catch(fail);
                },
                eventClick: function(info) {
                    const e = info.event;
                    tampilDetail(e.id, e.title, e.startStr, e.endStr, e.extendedProps.status, e.extendedProps.kategori, e.backgroundColor);
                },
                eventMouseEnter: function(info) {
                    info.el.style.opacity = '0.85';
                    info.el.title = info.event.title;
                },
                eventMouseLeave: function(info) {
                    info.el.style.opacity = '1';
                }
            });
            kalender.render();
        }

        // ===== LOAD SEMUA JADWAL =====
        function loadSemuaJadwal() {
            fetch('api/jadwal.php')
                .then(r => r.json())
                .then(res => {
                    if (!res.success) return;
                    semuaJadwal = res.data;
                    updateStats(res.data);
                    if (isAdmin) renderListJadwal(res.data);
                });
        }

        // ===== UPDATE STATS =====
        function updateStats(data) {
            const belum   = data.filter(j => j.status === 'belum').length;
            const proses  = data.filter(j => j.status === 'proses').length;
            const selesai = data.filter(j => j.status === 'selesai').length;
            const now     = new Date();
            const in30    = new Date(); in30.setDate(in30.getDate() + 30);
            const deadline = data.filter(j => {
                const end = new Date(j.tanggal_selesai);
                return end >= now && end <= in30 && j.status !== 'selesai';
            }).length;
            document.getElementById('statBelum').textContent   = belum;
            document.getElementById('statProses').textContent  = proses;
            document.getElementById('statSelesai').textContent = selesai;
            document.getElementById('statDeadline').textContent = deadline;
        }

        // ===== RENDER LIST JADWAL (admin) =====
        function renderListJadwal(data) {
            const el = document.getElementById('listSemuaJadwal');
            if (!data.length) {
                el.innerHTML = '<div style="text-align:center;padding:1rem;color:#718096;font-size:0.85rem">Belum ada jadwal</div>';
                return;
            }
            el.innerHTML = data.map(j => `
                <div class="jadwal-item" style="border-left-color:${j.warna}">
                    <div class="jadwal-dot" style="background:${j.warna}"></div>
                    <div class="jadwal-info">
                        <div class="judul">${escHtml(j.judul)}</div>
                        <div class="tanggal">
                            <span class="kat-badge">${j.kategori}</span>
                            ${formatTgl(j.tanggal_mulai)} – ${formatTgl(j.tanggal_selesai)}
                        </div>
                    </div>
                    <div class="jadwal-actions">
                        <button class="btn-status btn-status-${j.status}" onclick="gantiStatus(${j.id}, '${j.status}')" title="Ganti status">
                            ${labelStatus(j.status)}
                        </button>
                        <button class="btn-edit-item" onclick="editJadwal(${j.id})" title="Edit">✏️</button>
                        <button class="btn-hapus-item" onclick="hapusJadwal(${j.id})" title="Hapus">🗑️</button>
                    </div>
                </div>
            `).join('');
        }

        // ===== LOAD UPCOMING =====
        function loadUpcoming() {
            fetch('api/jadwal.php?action=upcoming')
                .then(r => r.json())
                .then(res => {
                    const el = document.getElementById('listUpcoming');
                    if (!res.success || !res.data.length) {
                        el.innerHTML = '<div style="text-align:center;padding:1rem;color:#48bb78;font-size:0.85rem">✅ Tidak ada deadline dalam 30 hari ke depan</div>';
                        return;
                    }
                    const now = new Date();
                    el.innerHTML = res.data.map(j => {
                        const sisa = Math.ceil((new Date(j.tanggal_selesai) - now) / 86400000);
                        const cls  = sisa <= 7 ? 'sisa-danger' : 'sisa-warning';
                        return `
                        <div class="jadwal-item" style="border-left-color:${j.warna}">
                            <div class="jadwal-dot" style="background:${j.warna}"></div>
                            <div class="jadwal-info">
                                <div class="judul">${escHtml(j.judul)}</div>
                                <div class="tanggal">
                                    <span class="kat-badge">${j.kategori}</span>
                                    Deadline: ${formatTgl(j.tanggal_selesai)}
                                </div>
                                <div class="psisa ${cls}">⏰ ${sisa} hari lagi</div>
                            </div>
                            <span class="status-badge status-${j.status}">${labelStatus(j.status)}</span>
                        </div>`;
                    }).join('');
                });
        }

        // ===== POPUP DEADLINE =====
        function cekPopupDeadline() {
            if (sessionStorage.getItem('sokab_popup_dismissed')) return;
            fetch('api/jadwal.php?action=upcoming')
                .then(r => r.json())
                .then(res => {
                    if (!res.success || !res.data.length) return;
                    const now = new Date();
                    const list = document.getElementById('popupList');
                    list.innerHTML = res.data.slice(0, 4).map(j => {
                        const sisa = Math.ceil((new Date(j.tanggal_selesai) - now) / 86400000);
                        const cls  = sisa <= 7 ? 'sisa-danger' : 'sisa-warning';
                        return `
                        <div class="popup-item">
                            <div class="popup-item-dot" style="background:${j.warna}"></div>
                            <div class="popup-item-info">
                                <div class="pjudul">${escHtml(j.judul)}</div>
                                <div class="pdate">Deadline: ${formatTgl(j.tanggal_selesai)}</div>
                                <div class="psisa ${cls}">⏰ ${sisa} hari lagi</div>
                            </div>
                        </div>`;
                    }).join('');
                    document.getElementById('popupDeadline').classList.add('show');
                });
        }

        function tutupPopup() {
            document.getElementById('popupDeadline').classList.remove('show');
            sessionStorage.setItem('sokab_popup_dismissed', '1');
        }

        // ===== MODAL TAMBAH =====
        function bukaModalTambah() {
            // Reset form untuk mode tambah baru
            document.getElementById('formTambahJadwal').reset();
            document.getElementById('inp_jadwal_id').value = '';
            document.getElementById('modalJadwalTitle').textContent = '📅 Tambah Jadwal SAKIP';
            document.getElementById('btnSimpanJadwal').textContent = '💾 Simpan Jadwal';
            document.getElementById('modalTambah').classList.add('show');
        }

        function editJadwal(id) {
            // Cari data jadwal dari array semuaJadwal
            const jadwal = semuaJadwal.find(j => j.id == id);
            if (!jadwal) {
                showToast('❌ Data jadwal tidak ditemukan', 'error');
                return;
            }

            // Isi form dengan data yang akan diedit
            document.getElementById('inp_jadwal_id').value = jadwal.id;
            document.getElementById('inp_judul').value = jadwal.judul;
            document.getElementById('inp_kategori').value = jadwal.kategori;
            document.getElementById('inp_mulai').value = jadwal.tanggal_mulai;
            document.getElementById('inp_selesai').value = jadwal.tanggal_selesai;
            document.getElementById('inp_deskripsi').value = jadwal.deskripsi || '';
            
            // Ubah judul modal dan text button
            document.getElementById('modalJadwalTitle').textContent = '✏️ Edit Jadwal SAKIP';
            document.getElementById('btnSimpanJadwal').textContent = '💾 Update Jadwal';
            
            // Buka modal
            document.getElementById('modalTambah').classList.add('show');
        }

        function tutupModal() {
            document.getElementById('modalTambah').classList.remove('show');
            document.getElementById('formTambahJadwal').reset();
            document.getElementById('inp_jadwal_id').value = '';
        }

        document.getElementById('formTambahJadwal').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const id = document.getElementById('inp_jadwal_id').value;
            const isEdit = !!id; // true jika ada id (mode edit)
            
            const payload = {
                judul:          document.getElementById('inp_judul').value.trim(),
                kategori:       document.getElementById('inp_kategori').value,
                tanggal_mulai:  document.getElementById('inp_mulai').value,
                tanggal_selesai:document.getElementById('inp_selesai').value,
                deskripsi:      document.getElementById('inp_deskripsi').value.trim(),
            };

            // Validasi tanggal
            if (payload.tanggal_selesai < payload.tanggal_mulai) {
                showToast('❌ Tanggal selesai tidak boleh lebih awal dari tanggal mulai', 'error');
                return;
            }
            
            // Jika edit, tambahkan id ke payload
            if (isEdit) {
                payload.id = parseInt(id);
            }
            
            const action = isEdit ? 'edit' : 'tambah';
            const successMsg = isEdit ? '✅ Jadwal berhasil diperbarui!' : '✅ Jadwal berhasil ditambahkan!';
            
            fetch(`api/jadwal.php?action=${action}`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    tutupModal();
                    kalender.refetchEvents();
                    loadSemuaJadwal();
                    loadUpcoming();
                    showToast(successMsg, 'success');
                } else {
                    showToast('❌ ' + res.message, 'error');
                }
            })
            .catch(err => {
                showToast('❌ Terjadi kesalahan: ' + err.message, 'error');
            });
        });

        // ===== GANTI STATUS =====
        function gantiStatus(id, statusSaat) {
            const urutan = ['belum', 'proses', 'selesai'];
            const next   = urutan[(urutan.indexOf(statusSaat) + 1) % urutan.length];
            fetch('api/jadwal.php?action=update_status', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({id, status: next})
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    kalender.refetchEvents();
                    loadSemuaJadwal();
                    loadUpcoming();
                    showToast(`🔄 Status diubah ke: ${labelStatus(next)}`, 'success');
                }
            });
        }

        // ===== HAPUS JADWAL =====
        function hapusJadwal(id) {
            if (!confirm('Yakin ingin menghapus jadwal ini?')) return;
            fetch('api/jadwal.php?action=hapus', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({id})
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    kalender.refetchEvents();
                    loadSemuaJadwal();
                    loadUpcoming();
                    showToast('🗑️ Jadwal dihapus', 'success');
                }
            });
        }

        // ===== DETAIL JADWAL =====
        function tampilDetail(id, judul, start, end, status, kategori, warna) {
            document.getElementById('detailKonten').innerHTML = `
                <div style="border-left:5px solid ${warna};padding:1rem;background:#f7fafc;border-radius:10px;margin-bottom:1rem">
                    <div style="font-size:1rem;font-weight:700;color:#2d3748;margin-bottom:6px">${escHtml(judul)}</div>
                    <div style="font-size:0.82rem;color:#718096">
                        <span class="kat-badge">${kategori}</span>
                        📅 ${formatTgl(start)} – ${formatTgl(end || start)}
                    </div>
                    <div style="margin-top:8px"><span class="status-badge status-${status}">${labelStatus(status)}</span></div>
                </div>
                ${isAdmin ? `
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:0.5rem;margin-top:0.5rem">
                    ${['belum','proses','selesai'].map(s => `
                    <button onclick="gantiStatus(${id},'${s==='selesai'?'proses':s==='proses'?'belum':'selesai'}'); document.getElementById('modalDetail').classList.remove('show')"
                        style="padding:8px;border:none;border-radius:8px;cursor:pointer;font-family:inherit;font-size:0.8rem;font-weight:600;
                        background:${s==='belum'?'#fed7d7':s==='proses'?'#fef3c7':'#c6f6d5'};
                        color:${s==='belum'?'#c53030':s==='proses'?'#92400e':'#276749'}">
                        ${labelStatus(s)}
                    </button>`).join('')}
                </div>` : ''}
            `;
            document.getElementById('modalDetail').classList.add('show');
        }

        // ===== TOAST =====
        function showToast(msg, type) {
            const t = document.createElement('div');
            t.style.cssText = `position:fixed;bottom:80px;right:24px;padding:10px 18px;border-radius:10px;font-size:0.85rem;font-weight:600;z-index:9999;animation:popupIn 0.3s ease;
                background:${type==='success'?'#c6f6d5':'#fed7d7'};color:${type==='success'?'#276749':'#c53030'};box-shadow:0 4px 15px rgba(0,0,0,0.15)`;
            t.textContent = msg;
            document.body.appendChild(t);
            setTimeout(() => t.remove(), 3000);
        }

        // ===== UTILS =====
        function escHtml(s) {
            return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        }

        function formatTgl(tgl) {
            if (!tgl) return '-';
            const d = new Date(tgl);
            return d.toLocaleDateString('id-ID', {day:'numeric', month:'short', year:'numeric'});
        }

        function labelStatus(s) {
            return {belum:'Belum', proses:'Proses', selesai:'Selesai'}[s] || s;
        }

        // Tutup modal klik overlay
        document.querySelectorAll('.modal-overlay').forEach(el => {
            el.addEventListener('click', function(e) {
                if (e.target === el) el.classList.remove('show');
            });
        });

        // ===== FUNGSI SIDEBAR =====

        // Show detail jadwal (popup saat klik event)
        function showDetailJadwal(jadwal) {
            const modal = document.getElementById('modalDetail');
            if (!modal) {
                console.error('Modal detail tidak ditemukan');
                return;
            }
            
            const konten = document.getElementById('detailKonten');
            if (!konten) {
                console.error('Konten detail tidak ditemukan');
                return;
            }
            
            const mulai = new Date(jadwal.tanggal_mulai);
            const selesai = new Date(jadwal.tanggal_selesai);
            const now = new Date();
            
            let statusBadge = '';
            if (now < mulai) {
                statusBadge = '<span style="background:#fbd38d;color:#744210;padding:4px 12px;border-radius:12px;font-size:0.85rem;font-weight:600">⏳ Belum Mulai</span>';
            } else if (now >= mulai && now <= selesai) {
                statusBadge = '<span style="background:#9ae6b4;color:#22543d;padding:4px 12px;border-radius:12px;font-size:0.85rem;font-weight:600">🔄 Sedang Berjalan</span>';
            } else {
                statusBadge = '<span style="background:#fc8181;color:#742a2a;padding:4px 12px;border-radius:12px;font-size:0.85rem;font-weight:600">✅ Selesai</span>';
            }
            
            konten.innerHTML = `
                <div style="padding:1.5rem">
                    <h3 style="margin-bottom:1rem;color:#1e293b">${escHtml(jadwal.judul)}</h3>
                    ${statusBadge}
                    <div style="margin-top:1.5rem">
                        <p style="margin-bottom:0.5rem"><strong>Kategori:</strong> ${escHtml(jadwal.kategori)}</p>
                        <p style="margin-bottom:0.5rem"><strong>Tanggal Mulai:</strong> ${formatTgl(jadwal.tanggal_mulai)}</p>
                        <p style="margin-bottom:0.5rem"><strong>Tanggal Selesai:</strong> ${formatTgl(jadwal.tanggal_selesai)}</p>
                        <p style="margin-bottom:0.5rem"><strong>Dibuat oleh:</strong> ${escHtml(jadwal.created_by || 'Admin')}</p>
                        ${jadwal.catatan ? `<p style="margin-top:1rem"><strong>Catatan:</strong><br>${escHtml(jadwal.catatan)}</p>` : ''}
                    </div>
                </div>
            `;
            
            modal.classList.add('show');
        }

    
        // ==================== IKSS FUNCTIONS ====================
        
        // IKSS Functions sudah dipindahkan ke assets/js/ikss_functions.js
