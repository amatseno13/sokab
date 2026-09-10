<?php /* Modal: IKSS — edit tautan, kelola, form */ ?>
    <!-- Modal Edit Link IKSS -->
    <div class="gdrive-modal-overlay" id="modalIKSS">
        <div class="gdrive-modal">
            <div class="gdrive-modal-head">
                <h3 id="modalIKSSTitle">Edit Link IKSS</h3>
                <button class="stat-modal-close" onclick="tutupModalIKSS()">✕</button>
            </div>
            <div class="gdrive-modal-body">
                <input type="hidden" id="ikssIdEdit">
                
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">IKSS:</label>
                    <div id="ikssNamaDisplay" style="padding: 0.75rem; background: #f8fafc; border-radius: 8px; color: #475569; font-size: 0.9rem;"></div>
                </div>
                
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Link Dokumen Sumber</label>
                    <input type="url" id="ikssLinkDokumen" 
                           placeholder="https://... (Google Drive, OneDrive, Dropbox, dll)"
                           style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem;">
                    <small style="color: #64748b; font-size: 0.85rem; display: block; margin-top: 0.25rem;">
                        Terima semua link cloud storage
                    </small>
                </div>
                
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Link Tindak Lanjut TW Sebelumnya</label>
                    <input type="url" id="ikssLinkTindakLanjut" 
                           placeholder="https://... (Google Drive, OneDrive, Dropbox, dll)"
                           style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem;">
                    <small style="color: #64748b; font-size: 0.85rem; display: block; margin-top: 0.25rem;">
                        Terima semua link cloud storage
                    </small>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button onclick="tutupModalIKSS()" 
                            style="padding: 0.75rem 1.5rem; border: 1px solid #e2e8f0; background: white; border-radius: 8px; cursor: pointer; font-weight: 500;">
                        Batal
                    </button>
                    <button onclick="simpanLinkIKSS()" 
                            style="padding: 0.75rem 1.5rem; background: #1e40af; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500;">
                        💾 Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════ MODAL: EDIT LINK DOKUMEN SUMBER ═══════════════ -->
    <div id="modalEditLinkDokumen" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9998; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 12px; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
            <div style="padding: 1.5rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 1.25rem; font-weight: 600; color: #1e293b; margin: 0;">✏️ Edit Link Dokumen Sumber</h3>
                <button onclick="tutupModalEditDokumen()" style="background: none; border: none; font-size: 1.5rem; color: #64748b; cursor: pointer; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">×</button>
            </div>
            <div style="padding: 1.5rem;">
                <input type="hidden" id="editDokIkssId">
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 500; color: #475569; margin-bottom: 0.5rem; font-size: 0.9rem;">IKSS:</label>
                    <div id="editDokSasaran" style="padding: 0.75rem; background: #f8fafc; border-radius: 6px; color: #1e293b; font-size: 0.9rem; line-height: 1.5;"></div>
                </div>
                <div style="margin-bottom: 1.5rem;">
                    <label for="editDokLink" style="display: block; font-weight: 500; color: #475569; margin-bottom: 0.5rem; font-size: 0.9rem;">Link Dokumen Sumber:</label>
                    <input type="url" id="editDokLink" placeholder="https://drive.google.com/... atau https://onedrive.com/..." 
                           style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem;">
                    <p style="margin-top: 0.5rem; font-size: 0.8rem; color: #64748b;">Terima semua link cloud storage (Google Drive, OneDrive, Dropbox, dll)</p>
                </div>
                <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                    <button onclick="tutupModalEditDokumen()" 
                            style="padding: 0.75rem 1.5rem; background: #e2e8f0; color: #475569; border: none; border-radius: 8px; cursor: pointer; font-weight: 500;">
                        Batal
                    </button>
                    <button onclick="simpanLinkDokumen()" 
                            style="padding: 0.75rem 1.5rem; background: #1e40af; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500;">
                        💾 Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════ MODAL: EDIT LINK TINDAK LANJUT ═══════════════ -->
    <div id="modalEditLinkTindakLanjut" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9998; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 12px; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
            <div style="padding: 1.5rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 1.25rem; font-weight: 600; color: #1e293b; margin: 0;">✏️ Edit Link Tindak Lanjut TW Sebelumnya</h3>
                <button onclick="tutupModalEditTindakLanjut()" style="background: none; border: none; font-size: 1.5rem; color: #64748b; cursor: pointer; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">×</button>
            </div>
            <div style="padding: 1.5rem;">
                <input type="hidden" id="editTLIkssId">
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 500; color: #475569; margin-bottom: 0.5rem; font-size: 0.9rem;">IKSS:</label>
                    <div id="editTLSasaran" style="padding: 0.75rem; background: #f8fafc; border-radius: 6px; color: #1e293b; font-size: 0.9rem; line-height: 1.5;"></div>
                </div>
                <div style="margin-bottom: 1.5rem;">
                    <label for="editTLLink" style="display: block; font-weight: 500; color: #475569; margin-bottom: 0.5rem; font-size: 0.9rem;">Link Tindak Lanjut:</label>
                    <input type="url" id="editTLLink" placeholder="https://drive.google.com/... atau https://onedrive.com/..." 
                           style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem;">
                    <p style="margin-top: 0.5rem; font-size: 0.8rem; color: #64748b;">Terima semua link cloud storage (Google Drive, OneDrive, Dropbox, dll)</p>
                </div>
                <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                    <button onclick="tutupModalEditTindakLanjut()" 
                            style="padding: 0.75rem 1.5rem; background: #e2e8f0; color: #475569; border: none; border-radius: 8px; cursor: pointer; font-weight: 500;">
                        Batal
                    </button>
                    <button onclick="simpanLinkTindakLanjut()" 
                            style="padding: 0.75rem 1.5rem; background: #1e40af; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500;">
                        💾 Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════ MODAL: KELOLA IKSS (ADMIN) ═══════════════ -->
    <div id="modalKelolaIKSS" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9998; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 12px; width: 95%; max-width: 1200px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
            <div style="padding: 1.5rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 1.25rem; font-weight: 600; color: #1e293b; margin: 0;">⚙️ Kelola IKSS Master</h3>
                <button onclick="tutupModalKelolaIKSS()" style="background: none; border: none; font-size: 1.5rem; color: #64748b; cursor: pointer; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">×</button>
            </div>
            <div style="padding: 1.5rem;">
                <div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
                    <p style="color: #64748b; margin: 0;">Kelola data master IKSS (tambah, edit, hapus)</p>
                    <button onclick="tambahIKSS()" style="padding: 0.75rem 1.5rem; background: #16a34a; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500;">
                        ➕ Tambah IKSS
                    </button>
                </div>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                            <tr>
                                <th style="padding: 0.75rem; text-align: center; font-weight: 600; color: #475569; width: 60px;">No</th>
                                <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #475569;">Sasaran Kegiatan</th>
                                <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #475569;">Indikator Kinerja</th>
                                <th style="padding: 0.75rem; text-align: center; font-weight: 600; color: #475569; width: 100px;">Target</th>
                                <th style="padding: 0.75rem; text-align: center; font-weight: 600; color: #475569; width: 80px;">Status</th>
                                <th style="padding: 0.75rem; text-align: center; font-weight: 600; color: #475569; width: 140px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="ikssManageTableBody">
                            <tr><td colspan="6" style="padding: 2rem; text-align: center; color: #94a3b8;">Memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════ MODAL: FORM IKSS (TAMBAH/EDIT) ═══════════════ -->
    <div id="modalFormIKSS" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 12px; width: 90%; max-width: 700px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
            <div style="padding: 1.5rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                <h3 id="formIKSSTitle" style="font-size: 1.25rem; font-weight: 600; color: #1e293b; margin: 0;">Tambah IKSS Baru</h3>
                <button onclick="tutupModalFormIKSS()" style="background: none; border: none; font-size: 1.5rem; color: #64748b; cursor: pointer; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">×</button>
            </div>
            <div style="padding: 1.5rem;">
                <input type="hidden" id="formIKSSId">
                
                <div style="margin-bottom: 1rem;">
                    <label for="formIKSSNomor" style="display: block; font-weight: 500; color: #475569; margin-bottom: 0.5rem; font-size: 0.9rem;">Nomor: <span style="color: #ef4444;">*</span></label>
                    <input type="text" id="formIKSSNomor" placeholder="Contoh: 1, 2, 3, dst" 
                           style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem;">
                </div>

                <div style="margin-bottom: 1rem;">
                    <label for="formIKSSSasaran" style="display: block; font-weight: 500; color: #475569; margin-bottom: 0.5rem; font-size: 0.9rem;">Sasaran Kegiatan: <span style="color: #ef4444;">*</span></label>
                    <textarea id="formIKSSSasaran" rows="3" placeholder="Contoh: Terwujudnya Penyediaan Data dan Insight Statistik..." 
                              style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem; resize: vertical;"></textarea>
                </div>

                <div style="margin-bottom: 1rem;">
                    <label for="formIKSSIndikator" style="display: block; font-weight: 500; color: #475569; margin-bottom: 0.5rem; font-size: 0.9rem;">Indikator Kinerja: <span style="color: #ef4444;">*</span></label>
                    <textarea id="formIKSSIndikator" rows="3" placeholder="Contoh: Persentase Publikasi/Laporan Statistik..." 
                              style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem; resize: vertical;"></textarea>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label for="formIKSSTarget" style="display: block; font-weight: 500; color: #475569; margin-bottom: 0.5rem; font-size: 0.9rem;">Target: <span style="color: #ef4444;">*</span></label>
                    <input type="text" id="formIKSSTarget" placeholder="Contoh: 100 Persen, 74.45 Poin" 
                           style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem;">
                </div>

                <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                    <button onclick="tutupModalFormIKSS()" 
                            style="padding: 0.75rem 1.5rem; background: #e2e8f0; color: #475569; border: none; border-radius: 8px; cursor: pointer; font-weight: 500;">
                        Batal
                    </button>
                    <button onclick="simpanIKSS()" 
                            style="padding: 0.75rem 1.5rem; background: #1e40af; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500;">
                        💾 Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>
