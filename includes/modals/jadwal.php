<?php /* Modal: tambah/edit jadwal, detail jadwal, popup deadline */ ?>
    <!-- Modal Tambah/Edit Jadwal -->
    <div class="modal-overlay" id="modalTambah">
        <div class="modal-box">
            <div class="modal-header">
                <h2 id="modalJadwalTitle">📅 Tambah Jadwal SAKIP</h2>
                <button class="modal-close" onclick="tutupModal()">✕</button>
            </div>
            <form class="modal-form" id="formTambahJadwal">
                <input type="hidden" id="inp_jadwal_id">
                <div class="form-group">
                    <label>Judul Jadwal *</label>
                    <input type="text" id="inp_judul" placeholder="Contoh: FRA TW1 2026" required>
                </div>
                <div class="form-group">
                    <label>Kategori *</label>
                    <select id="inp_kategori">
                        <option value="FRA">FRA</option>
                        <option value="KKPK">KKPK</option>
                        <option value="POK">POK</option>
                        <option value="PKPT">PKPT</option>
                        <option value="Renstra">Renstra</option>
                        <option value="LAKIN">LAKIN</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Tanggal Mulai *</label>
                        <input type="date" id="inp_mulai" required>
                    </div>
                    <div class="form-group">
                        <label>Tanggal Selesai *</label>
                        <input type="date" id="inp_selesai" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea id="inp_deskripsi" rows="3" placeholder="Keterangan tambahan..."></textarea>
                </div>
                <button type="submit" class="btn-submit-modal" id="btnSimpanJadwal">💾 Simpan Jadwal</button>
            </form>
        </div>
    </div>

    <!-- Modal Detail Jadwal (klik event kalender) -->
    <div class="modal-overlay" id="modalDetail">
        <div class="modal-box">
            <div class="modal-header">
                <h2>📋 Detail Jadwal</h2>
                <button class="modal-close" onclick="document.getElementById('modalDetail').classList.remove('show')">✕</button>
            </div>
            <div id="detailKonten"></div>
        </div>
    </div>

    <!-- Popup Deadline -->
    <div class="popup-deadline" id="popupDeadline">
        <div class="popup-header">
            <h4>🚨 Deadline Dalam 30 Hari!</h4>
            <button class="popup-close" onclick="tutupPopup()">✕</button>
        </div>
        <div id="popupList"></div>
        <div class="popup-footer">
            <button class="popup-dismiss" onclick="tutupPopup()">Saya Mengerti</button>
        </div>
    </div>
