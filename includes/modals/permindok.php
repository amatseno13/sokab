<?php /* Modal: Permindok — edit tautan, kelola, form */ ?>
    <!-- ═══════════════ MODAL: EDIT LINK PERMINDOK ═══════════════ -->
    <div id="modalEditPermindokLink" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9998; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 12px; width: 90%; max-width: 600px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
            <div style="padding: 1.5rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 1.25rem; font-weight: 600; color: #1e293b; margin: 0;">Edit Link Permindok</h3>
                <button onclick="closeModal('modalEditPermindokLink')" style="background: none; border: none; font-size: 1.5rem; color: #64748b; cursor: pointer; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">×</button>
            </div>
            <div style="padding: 1.5rem;">
                <input type="hidden" id="editPermindokId">
                
                <div style="margin-bottom: 1rem; padding: 1rem; background: #f8fafc; border-radius: 6px;">
                    <label style="display: block; font-weight: 500; color: #64748b; font-size: 0.875rem; margin-bottom: 0.25rem;">Judul:</label>
                    <div id="editPermindokJudul" style="color: #1e293b; font-size: 1rem;"></div>
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label for="editPermindokLink" style="display: block; font-weight: 500; color: #475569; margin-bottom: 0.5rem; font-size: 0.9rem;">Link Permindok:</label>
                    <input type="url" 
                           id="editPermindokLink" 
                           placeholder="https://drive.google.com/... atau https://onedrive.live.com/..."
                           style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem;">
                    <small style="color: #64748b;">Kosongkan jika belum ada link</small>
                </div>

                <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                    <button onclick="closeModal('modalEditPermindokLink')" 
                            style="padding: 0.75rem 1.5rem; background: #e2e8f0; color: #475569; border: none; border-radius: 8px; cursor: pointer; font-weight: 500;">
                        Batal
                    </button>
                    <button onclick="savePermindokLink()" 
                            style="padding: 0.75rem 1.5rem; background: #1e40af; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500;">
                        💾 Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════ MODAL: KELOLA PERMINDOK (ADMIN) ═══════════════ -->
    <div id="modalKelolaPermindok" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9998; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 12px; width: 95%; max-width: 1200px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
            <div style="padding: 1.5rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 1.25rem; font-weight: 600; color: #1e293b; margin: 0;">⚙️ Kelola Permindok Master</h3>
                <button onclick="closeModal('modalKelolaPermindok')" style="background: none; border: none; font-size: 1.5rem; color: #64748b; cursor: pointer; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">×</button>
            </div>
            <div style="padding: 1.5rem;">
                <div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
                    <p style="color: #64748b; margin: 0;">Kelola data master Permindok (tambah, edit, hapus)</p>
                    <button onclick="showFormPermindok('create')" style="padding: 0.75rem 1.5rem; background: #16a34a; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500;">
                        ➕ Tambah Permindok
                    </button>
                </div>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                        <thead style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                            <tr>
                                <th style="padding: 0.75rem; text-align: center; font-weight: 600; color: #475569; width: 60px;">No</th>
                                <th style="padding: 0.75rem; text-align: center; font-weight: 600; color: #475569; width: 80px;">Tahun</th>
                                <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #475569;">Judul</th>
                                <th style="padding: 0.75rem; text-align: center; font-weight: 600; color: #475569; width: 80px;">Link</th>
                                <th style="padding: 0.75rem; text-align: center; font-weight: 600; color: #475569; width: 80px;">Status</th>
                                <th style="padding: 0.75rem; text-align: center; font-weight: 600; color: #475569; width: 140px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="kelolaPermindokTableBody">
                            <tr><td colspan="6" style="padding: 2rem; text-align: center; color: #94a3b8;">Memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════ MODAL: FORM PERMINDOK (TAMBAH/EDIT) ═══════════════ -->
    <div id="modalFormPermindok" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 12px; width: 90%; max-width: 700px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
            <div style="padding: 1.5rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                <h3 id="formPermindokTitle" style="font-size: 1.25rem; font-weight: 600; color: #1e293b; margin: 0;">Tambah Permindok Baru</h3>
                <button onclick="closeModal('modalFormPermindok')" style="background: none; border: none; font-size: 1.5rem; color: #64748b; cursor: pointer; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">×</button>
            </div>
            <div style="padding: 1.5rem;">
                <input type="hidden" id="formPermindokMode">
                <input type="hidden" id="formPermindokId">
                
                <div style="margin-bottom: 1rem;">
                    <label for="formPermindokNomor" style="display: block; font-weight: 500; color: #475569; margin-bottom: 0.5rem; font-size: 0.9rem;">Nomor: <span style="color: #ef4444;">*</span></label>
                    <input type="number" 
                           id="formPermindokNomor" 
                           min="1"
                           placeholder="1"
                           style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem;">
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label for="formPermindokTahun" style="display: block; font-weight: 500; color: #475569; margin-bottom: 0.5rem; font-size: 0.9rem;">Tahun: <span style="color: #ef4444;">*</span></label>
                    <input type="number" 
                           id="formPermindokTahun" 
                           min="2020" 
                           max="2030"
                           placeholder="2026"
                           style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem;">
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label for="formPermindokJudul" style="display: block; font-weight: 500; color: #475569; margin-bottom: 0.5rem; font-size: 0.9rem;">Judul Permindok: <span style="color: #ef4444;">*</span></label>
                    <textarea id="formPermindokJudul" 
                              rows="3"
                              placeholder="Contoh: Permintaan Data Statistik Kependudukan Triwulan I 2026"
                              style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem; resize: vertical;"></textarea>
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label for="formPermindokLink" style="display: block; font-weight: 500; color: #475569; margin-bottom: 0.5rem; font-size: 0.9rem;">Link Permindok:</label>
                    <input type="url" 
                           id="formPermindokLink" 
                           placeholder="https://drive.google.com/... (opsional)"
                           style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem;">
                    <small style="color: #64748b;">Bisa dikosongkan, dapat diisi kemudian</small>
                </div>

                <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                    <button onclick="closeModal('modalFormPermindok')" 
                            style="padding: 0.75rem 1.5rem; background: #e2e8f0; color: #475569; border: none; border-radius: 8px; cursor: pointer; font-weight: 500;">
                        Batal
                    </button>
                    <button onclick="saveFormPermindok()" 
                            style="padding: 0.75rem 1.5rem; background: #1e40af; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500;">
                        💾 Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>
