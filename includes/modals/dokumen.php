<?php /* Modal: unggah dokumen dan LAKIN */ ?>
    <!-- Modal Tambah/Edit Dokumen (File OR Link) -->
    <div class="gdrive-modal-overlay" id="gdrivemodalOverlay">
        <div class="gdrive-modal">
            <div class="gdrive-modal-head">
                <h3 id="gdrivemodalTitle">Tambah Dokumen</h3>
                <button class="stat-modal-close" onclick="tutupGdriveModal()">✕</button>
            </div>
            <div class="gdrive-modal-body">
                <input type="hidden" id="gdrivemodalId">
                <input type="hidden" id="gdrivemodalMenuKey">
                <div class="form-group">
                    <label>Judul Dokumen *</label>
                    <input type="text" id="gdrivemodalJudul" placeholder="Contoh: Renstra 2025-2029">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Tahun</label>
                        <input type="number" id="gdrivemodalTahun" placeholder="2026" min="2000" max="2099">
                    </div>
                    <div class="form-group" id="fieldTriwulan" style="display:none">
                        <label>Triwulan</label>
                        <select id="gdrivemodalTriwulan">
                            <option value="">-- Pilih --</option>
                            <option value="TW1">Triwulan I</option>
                            <option value="TW2">Triwulan II</option>
                            <option value="TW3">Triwulan III</option>
                            <option value="TW4">Triwulan IV</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Kategori</label>
                    <input type="text" id="gdrivemodalSubkat" placeholder="Contoh: FRA, Renstra, dll">
                </div>
                
                <!-- Pilihan Metode Upload -->
                <div class="form-group" id="dokumenUploadMethodGroup">
                    <label>Pilih Metode Upload *</label>
                    <div class="upload-method-toggle">
                        <button type="button" class="method-btn" data-method="file" onclick="toggleDokumenUploadMethod('file')">
                            📁 Upload File
                        </button>
                        <button type="button" class="method-btn active" data-method="gdrive" onclick="toggleDokumenUploadMethod('gdrive')">
                            ☁️ Link Cloud Storage
                        </button>
                    </div>
                </div>
                
                <!-- Area Upload File -->
                <div class="form-group" id="dokumenFileUploadArea" style="display:none;">
                    <label>File (PDF/Word/Excel/Image, max 20MB) *</label>
                    <div class="file-input-wrapper">
                        <div class="file-input-display" id="dokumenFileDisplay">📎 Klik untuk pilih file</div>
                        <input type="file" id="gdrivemodalFile" accept=".pdf,.doc,.docx,.xlsx,.xls,.png,.jpg,.jpeg" onchange="updateDokumenFileDisplay()">
                    </div>
                </div>
                
                <!-- Area Link Cloud Storage -->
                <div class="form-group" id="dokumenGdriveUploadArea">
                    <label>URL Link *</label>
                    <input type="url" id="gdrivemodalUrl" placeholder="https://drive.google.com/ atau https://onedrive.live.com/ ...">
                    <small style="color:#666;display:block;margin-top:5px;">
                        💡 Pastikan file sudah di-share "Anyone with the link"
                    </small>
                </div>
                
                <div class="form-group">
                    <label>Keterangan</label>
                    <textarea id="gdrivemodalKet" rows="2" placeholder="Opsional"></textarea>
                </div>
            </div>
            <div class="gdrive-modal-foot">
                <button class="btn-cancel" onclick="tutupGdriveModal()">Batal</button>
                <button class="btn-save" onclick="simpanDokumen()">💾 Simpan</button>
            </div>
        </div>
    </div>

    <!-- Modal Upload LAKIN -->
    <div class="gdrive-modal-overlay" id="lakinUploadOverlay">
        <div class="gdrive-modal">
            <div class="gdrive-modal-head">
                <h3 id="lakinUploadTitle">Upload LAKIN Draft</h3>
                <button class="stat-modal-close" onclick="tutupUploadLakin()">✕</button>
            </div>
            <div class="gdrive-modal-body">
                <input type="hidden" id="lakinUploadTipe">
                <div class="form-group">
                    <label>Judul *</label>
                    <input type="text" id="lakinJudul" placeholder="Contoh: LAKIN 2025 Draft v1">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Tahun *</label>
                        <input type="number" id="lakinTahun" placeholder="2025" min="2000" max="2099">
                    </div>
                    <div class="form-group">
                        <label>Keterangan</label>
                        <input type="text" id="lakinKet" placeholder="Opsional">
                    </div>
                </div>
                
                <!-- Pilihan Metode Upload -->
                <div class="form-group">
                    <label>Pilih Metode Upload *</label>
                    <div class="upload-method-toggle">
                        <button type="button" class="method-btn active" data-method="file" onclick="toggleUploadMethod('file')">
                            📁 Upload File
                        </button>
                        <button type="button" class="method-btn" data-method="gdrive" onclick="toggleUploadMethod('gdrive')">
                            ☁️ Link Cloud Storage
                        </button>
                    </div>
                </div>
                
                <!-- Area Upload File -->
                <div class="form-group" id="fileUploadArea">
                    <label>File (PDF/Word/Excel, maks 20MB) *</label>
                    <div class="file-input-wrapper">
                        <div class="file-input-display" id="fileDisplay">📎 Klik untuk pilih file</div>
                        <input type="file" id="lakinFile" accept=".pdf,.doc,.docx,.xlsx,.xls" onchange="updateFileDisplay()">
                    </div>
                </div>
                
                <!-- Area Link Cloud Storage -->
                <div class="form-group" id="gdriveUploadArea" style="display:none;">
                    <label>Link Cloud Storage *</label>
                    <input type="url" id="lakinGdriveLink" placeholder="https://... (Google Drive, OneDrive, Dropbox, dll)">
                    <small style="color:#666;display:block;margin-top:5px;">
                        💡 Pastikan file sudah di-share "Anyone with the link"
                    </small>
                </div>
            </div>
            <div class="gdrive-modal-foot">
                <button class="btn-cancel" onclick="tutupUploadLakin()">Batal</button>
                <button class="btn-save" id="btnUploadLakin" onclick="uploadLakin()">⬆️ Upload</button>
            </div>
        </div>
    </div>
