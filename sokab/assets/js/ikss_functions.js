/**
 * IKSS Functions - BPS Kota Bima
 * File: ikss_functions.js
 * Version: 2.0
 * Updated: 2026-04-01
 */

// ============================================
// GLOBAL VARIABLES
// ============================================
let currentIKSSData = [];
let currentTriwulan = 'TW I';

// ============================================
// 1. GENERATE TAHUN DROPDOWN
// ============================================
function generateYearOptions() {
    const currentYear = new Date().getFullYear();
    const startYear = 2020;
    const endYear = currentYear + 5;
    
    const selectElement = document.getElementById('ikssFilterTahun');
    if (!selectElement) {
        console.error('Element ikssFilterTahun not found');
        return;
    }
    
    let html = '';
    for (let year = endYear; year >= startYear; year--) {
        const selected = year === currentYear ? 'selected' : '';
        html += `<option value="${year}" ${selected}>${year}</option>`;
    }
    
    selectElement.innerHTML = html;
    console.log(`✅ Generated year options: ${startYear} - ${endYear}`);
}

// ============================================
// 2. LOAD IKSS DATA
// ============================================
function loadIKSSData(triwulan) {
    if (!triwulan) {
        const activeTW = document.querySelector('.ikss-tw-btn.active');
        triwulan = activeTW ? activeTW.dataset.tw : 'TW I';
    }
    
    currentTriwulan = triwulan;
    
    console.log('🔍 Loading IKSS data for triwulan:', triwulan);
    
    const tbody = document.getElementById('ikssTableBody');
    if (!tbody) {
        console.error('❌ Element ikssTableBody not found!');
        return;
    }
    
    // Show loading
    tbody.innerHTML = `
        <tr>
            <td colspan="5" style="padding: 2rem; text-align: center; color: #94a3b8;">
                <div style="display: inline-block; width: 20px; height: 20px; border: 3px solid #e2e8f0; border-top-color: #3b82f6; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                <div style="margin-top: 0.5rem;">Memuat data IKSS...</div>
            </td>
        </tr>`;
    
    fetch(`api/ikss.php?action=list&triwulan=${encodeURIComponent(triwulan)}`)
        .then(res => res.json())
        .then(data => {
            console.log('📊 IKSS data received:', data);
            if (data.success) {
                currentIKSSData = data.data;
                renderIKSSTable(data.data);
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" style="padding: 2rem; text-align: center; color: #ef4444;">
                            ❌ ${data.message || 'Error loading data'}
                        </td>
                    </tr>`;
            }
        })
        .catch(err => {
            console.error('❌ Error loading IKSS:', err);
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" style="padding: 2rem; text-align: center; color: #ef4444;">
                        ❌ Error: ${err.message}
                    </td>
                </tr>`;
        });
}

// ============================================
// PAGINATION STATE
// ============================================
let currentPage = 1;
let rowsPerPage = 10;
let allIKSSData = [];

// ============================================
// 3. RENDER IKSS TABLE WITH PAGINATION
// ============================================
function renderIKSSTable(data) {
    allIKSSData = data; // Simpan data untuk pagination
    
    const tbody = document.getElementById('ikssTableBody');
    const paginationContainer = document.getElementById('paginationContainer');
    
    if (!data || data.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" style="padding: 2rem; text-align: center; color: #94a3b8;">
                    Belum ada data IKSS
                </td>
            </tr>`;
        if (paginationContainer) paginationContainer.innerHTML = '';
        return;
    }
    
    // Hitung pagination
    const totalRows = data.length;
    const totalPages = Math.ceil(totalRows / rowsPerPage);
    const startIndex = (currentPage - 1) * rowsPerPage;
    const endIndex = Math.min(startIndex + rowsPerPage, totalRows);
    const currentData = data.slice(startIndex, endIndex);
    
    // Render table rows
    let html = '';
    currentData.forEach((ikss, index) => {
        html += `
            <tr style="border-bottom: 1px solid #e2e8f0;">
                <td style="padding: 1rem; text-align: center; color: #64748b; font-weight: 500;">${ikss.nomor}</td>
                <td style="padding: 1rem; color: #1e293b; line-height: 1.5; text-align: justify;">
                    <div style="font-size: 0.9rem;">${ikss.sasaran_kegiatan}</div>
                </td>
                <td style="padding: 1rem; color: #475569; line-height: 1.5; text-align: justify;">
                    <div style="font-size: 0.85rem;">${ikss.indikator_kinerja}</div>
                    ${ikss.target ? `<div style="margin-top: 0.25rem; font-size: 0.75rem; color: #16a34a; font-weight: 600;">Target: ${ikss.target}</div>` : ''}
                </td>
                <td style="padding: 0.5rem; vertical-align: middle;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        ${ikss.link_dokumen_sumber ? `
                            <a href="${ikss.link_dokumen_sumber}" target="_blank" 
                               style="flex: 1; color: #2563eb; text-decoration: none; font-size: 0.85rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"
                               title="${ikss.link_dokumen_sumber}">
                                🔗 Lihat Dokumen
                            </a>
                        ` : `<span style="flex: 1; color: #94a3b8; font-size: 0.85rem;">Belum ada link</span>`}
                        ${isAdmin ? `
                            <button onclick="editLinkDokumen(${ikss.id}, '${ikss.sasaran_kegiatan.replace(/'/g, "\\'")}', '${ikss.link_dokumen_sumber || ''}')" 
                                    style="padding: 0.35rem 0.75rem; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.75rem; white-space: nowrap; font-weight: 500;">
                                Edit
                            </button>
                        ` : ''}
                    </div>
                </td>
                <td style="padding: 0.5rem; vertical-align: middle;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        ${ikss.link_tindak_lanjut ? `
                            <a href="${ikss.link_tindak_lanjut}" target="_blank" 
                               style="flex: 1; color: #2563eb; text-decoration: none; font-size: 0.85rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"
                               title="${ikss.link_tindak_lanjut}">
                                🔗 Lihat Tindak Lanjut
                            </a>
                        ` : `<span style="flex: 1; color: #94a3b8; font-size: 0.85rem;">Belum ada link</span>`}
                        ${isAdmin ? `
                            <button onclick="editLinkTindakLanjut(${ikss.id}, '${ikss.sasaran_kegiatan.replace(/'/g, "\\'")}', '${ikss.link_tindak_lanjut || ''}')" 
                                    style="padding: 0.35rem 0.75rem; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.75rem; white-space: nowrap; font-weight: 500;">
                                Edit
                            </button>
                        ` : ''}
                    </div>
                </td>
            </tr>`;
    });
    
    tbody.innerHTML = html;
    
    // Render pagination controls
    renderPagination(totalRows, totalPages, startIndex, endIndex);
    
    console.log(`✅ Rendered ${currentData.length} of ${totalRows} IKSS rows (Page ${currentPage}/${totalPages})`);
}

// ============================================
// PAGINATION CONTROLS
// ============================================
function renderPagination(totalRows, totalPages, startIndex, endIndex) {
    const container = document.getElementById('paginationContainer');
    if (!container) return;
    
    if (totalRows === 0) {
        container.innerHTML = '';
        return;
    }
    
    let paginationHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: #f8fafc; border-top: 1px solid #e2e8f0;">
            <!-- Left: Rows per page -->
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <span style="font-size: 0.875rem; color: #64748b;">Tampilkan:</span>
                <select onchange="changeRowsPerPage(this.value)" style="padding: 0.35rem 0.5rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.875rem; cursor: pointer;">
                    <option value="10" ${rowsPerPage === 10 ? 'selected' : ''}>10</option>
                    <option value="25" ${rowsPerPage === 25 ? 'selected' : ''}>25</option>
                    <option value="50" ${rowsPerPage === 50 ? 'selected' : ''}>50</option>
                    <option value="${totalRows}" ${rowsPerPage === totalRows ? 'selected' : ''}>Semua</option>
                </select>
                <span style="font-size: 0.875rem; color: #64748b;">per halaman</span>
            </div>
            
            <!-- Center: Info -->
            <div style="font-size: 0.875rem; color: #475569;">
                Menampilkan ${startIndex + 1} - ${endIndex} dari ${totalRows} data
            </div>
            
            <!-- Right: Page buttons -->
            <div style="display: flex; gap: 0.25rem;">`;
    
    // Previous button
    paginationHTML += `
        <button onclick="changePage(${currentPage - 1})" 
                ${currentPage === 1 ? 'disabled' : ''}
                style="padding: 0.4rem 0.75rem; border: 1px solid #cbd5e1; background: ${currentPage === 1 ? '#f1f5f9' : 'white'}; color: ${currentPage === 1 ? '#94a3b8' : '#475569'}; border-radius: 4px; cursor: ${currentPage === 1 ? 'not-allowed' : 'pointer'}; font-size: 0.875rem;">
            ‹
        </button>`;
    
    // Page numbers
    const maxVisiblePages = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
    
    if (endPage - startPage < maxVisiblePages - 1) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }
    
    if (startPage > 1) {
        paginationHTML += `
            <button onclick="changePage(1)" 
                    style="padding: 0.4rem 0.75rem; border: 1px solid #cbd5e1; background: white; color: #475569; border-radius: 4px; cursor: pointer; font-size: 0.875rem;">
                1
            </button>`;
        if (startPage > 2) {
            paginationHTML += `<span style="padding: 0.4rem 0.5rem; color: #94a3b8;">...</span>`;
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        paginationHTML += `
            <button onclick="changePage(${i})" 
                    style="padding: 0.4rem 0.75rem; border: 1px solid ${i === currentPage ? '#3b82f6' : '#cbd5e1'}; background: ${i === currentPage ? '#3b82f6' : 'white'}; color: ${i === currentPage ? 'white' : '#475569'}; border-radius: 4px; cursor: pointer; font-size: 0.875rem; font-weight: ${i === currentPage ? '600' : '400'};">
                ${i}
            </button>`;
    }
    
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            paginationHTML += `<span style="padding: 0.4rem 0.5rem; color: #94a3b8;">...</span>`;
        }
        paginationHTML += `
            <button onclick="changePage(${totalPages})" 
                    style="padding: 0.4rem 0.75rem; border: 1px solid #cbd5e1; background: white; color: #475569; border-radius: 4px; cursor: pointer; font-size: 0.875rem;">
                ${totalPages}
            </button>`;
    }
    
    // Next button
    paginationHTML += `
        <button onclick="changePage(${currentPage + 1})" 
                ${currentPage === totalPages ? 'disabled' : ''}
                style="padding: 0.4rem 0.75rem; border: 1px solid #cbd5e1; background: ${currentPage === totalPages ? '#f1f5f9' : 'white'}; color: ${currentPage === totalPages ? '#94a3b8' : '#475569'}; border-radius: 4px; cursor: ${currentPage === totalPages ? 'not-allowed' : 'pointer'}; font-size: 0.875rem;">
            ›
        </button>
    </div>
    </div>`;
    
    container.innerHTML = paginationHTML;
}

function changePage(page) {
    const totalPages = Math.ceil(allIKSSData.length / rowsPerPage);
    if (page < 1 || page > totalPages) return;
    currentPage = page;
    renderIKSSTable(allIKSSData);
}

function changeRowsPerPage(value) {
    rowsPerPage = parseInt(value);
    currentPage = 1; // Reset to first page
    renderIKSSTable(allIKSSData);
}

// ============================================
// 4. EDIT LINK DOKUMEN SUMBER
// ============================================
function editLinkDokumen(ikssId, sasaranKegiatan, currentLink) {
    const modal = document.getElementById('modalEditLinkDokumen');
    if (!modal) {
        console.error('Modal editLinkDokumen not found');
        return;
    }
    
    document.getElementById('editDokIkssId').value = ikssId;
    document.getElementById('editDokSasaran').textContent = sasaranKegiatan;
    document.getElementById('editDokLink').value = currentLink || '';
    
    modal.style.display = 'flex';
}

function tutupModalEditDokumen() {
    const modal = document.getElementById('modalEditLinkDokumen');
    if (modal) modal.style.display = 'none';
}

function simpanLinkDokumen() {
    const ikssId = document.getElementById('editDokIkssId').value;
    const link = document.getElementById('editDokLink').value.trim();
    
    if (!ikssId) {
        showToast('Data tidak valid', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('ikss_id', ikssId);
    formData.append('triwulan', currentTriwulan);
    formData.append('link_dokumen_sumber', link);
    formData.append('link_tindak_lanjut', ''); // Keep existing or empty
    
    // Get existing link_tindak_lanjut
    const existingData = currentIKSSData.find(d => d.id == ikssId);
    if (existingData && existingData.link_tindak_lanjut) {
        formData.set('link_tindak_lanjut', existingData.link_tindak_lanjut);
    }
    
    fetch('api/ikss.php?action=update_link', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast(data.message || 'Link berhasil disimpan', 'success');
            tutupModalEditDokumen();
            loadIKSSData(currentTriwulan);
        } else {
            showToast(data.message || 'Error menyimpan link', 'error');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        showToast('Error menyimpan link', 'error');
    });
}

// ============================================
// 5. EDIT LINK TINDAK LANJUT
// ============================================
function editLinkTindakLanjut(ikssId, sasaranKegiatan, currentLink) {
    const modal = document.getElementById('modalEditLinkTindakLanjut');
    if (!modal) {
        console.error('Modal editLinkTindakLanjut not found');
        return;
    }
    
    document.getElementById('editTLIkssId').value = ikssId;
    document.getElementById('editTLSasaran').textContent = sasaranKegiatan;
    document.getElementById('editTLLink').value = currentLink || '';
    
    modal.style.display = 'flex';
}

function tutupModalEditTindakLanjut() {
    const modal = document.getElementById('modalEditLinkTindakLanjut');
    if (modal) modal.style.display = 'none';
}

function simpanLinkTindakLanjut() {
    const ikssId = document.getElementById('editTLIkssId').value;
    const link = document.getElementById('editTLLink').value.trim();
    
    if (!ikssId) {
        showToast('Data tidak valid', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('ikss_id', ikssId);
    formData.append('triwulan', currentTriwulan);
    formData.append('link_tindak_lanjut', link);
    formData.append('link_dokumen_sumber', ''); // Keep existing or empty
    
    // Get existing link_dokumen_sumber
    const existingData = currentIKSSData.find(d => d.id == ikssId);
    if (existingData && existingData.link_dokumen_sumber) {
        formData.set('link_dokumen_sumber', existingData.link_dokumen_sumber);
    }
    
    fetch('api/ikss.php?action=update_link', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast(data.message || 'Link berhasil disimpan', 'success');
            tutupModalEditTindakLanjut();
            loadIKSSData(currentTriwulan);
        } else {
            showToast(data.message || 'Error menyimpan link', 'error');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        showToast('Error menyimpan link', 'error');
    });
}

// ============================================
// 6. KELOLA IKSS (ADMIN ONLY)
// ============================================
function kelolaIKSS() {
    const modal = document.getElementById('modalKelolaIKSS');
    if (!modal) {
        console.error('Modal kelolaIKSS not found');
        return;
    }
    
    modal.style.display = 'flex';
    loadIKSSManage();
}

function tutupModalKelolaIKSS() {
    const modal = document.getElementById('modalKelolaIKSS');
    if (modal) modal.style.display = 'none';
}

function loadIKSSManage() {
    const tbody = document.getElementById('ikssManageTableBody');
    if (!tbody) {
        console.error('Element ikssManageTableBody not found');
        return;
    }
    
    tbody.innerHTML = '<tr><td colspan="6" style="padding: 2rem; text-align: center;">Memuat data...</td></tr>';
    
    fetch('api/ikss.php?action=list_all')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                renderIKSSManageTable(data.data);
            } else {
                tbody.innerHTML = `<tr><td colspan="6" style="padding: 2rem; text-align: center; color: #ef4444;">Error: ${data.message}</td></tr>`;
            }
        })
        .catch(err => {
            console.error('Error:', err);
            tbody.innerHTML = `<tr><td colspan="6" style="padding: 2rem; text-align: center; color: #ef4444;">Error loading data</td></tr>`;
        });
}

function renderIKSSManageTable(data) {
    const tbody = document.getElementById('ikssManageTableBody');
    
    if (!data || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="padding: 2rem; text-align: center;">Belum ada data IKSS</td></tr>';
        return;
    }
    
    let html = '';
    data.forEach(ikss => {
        const statusBadge = ikss.is_active == 1 
            ? '<span style="background: #dcfce7; color: #16a34a; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 500;">Aktif</span>'
            : '<span style="background: #fee2e2; color: #dc2626; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 500;">Nonaktif</span>';
        
        html += `
            <tr style="border-bottom: 1px solid #e2e8f0;">
                <td style="padding: 0.75rem; text-align: center;">${ikss.nomor}</td>
                <td style="padding: 0.75rem; font-size: 0.85rem; text-align: justify;">${ikss.sasaran_kegiatan}</td>
                <td style="padding: 0.75rem; font-size: 0.85rem; text-align: justify;">${ikss.indikator_kinerja}</td>
                <td style="padding: 0.75rem; text-align: center; font-size: 0.85rem;">${ikss.target || '-'}</td>
                <td style="padding: 0.75rem; text-align: center;">${statusBadge}</td>
                <td style="padding: 0.75rem; text-align: center;">
                    <div style="display: flex; gap: 0.5rem; justify-content: center;">
                        <button onclick="editIKSSMaster(${ikss.id})" style="padding: 0.35rem 0.75rem; background: #f59e0b; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.75rem;">Edit</button>
                        <button onclick="hapusIKSS(${ikss.id})" style="padding: 0.35rem 0.75rem; background: #ef4444; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.75rem;">Hapus</button>
                    </div>
                </td>
            </tr>`;
    });
    
    tbody.innerHTML = html;
}

// ============================================
// 7. TAMBAH IKSS BARU
// ============================================
function tambahIKSS() {
    const modal = document.getElementById('modalFormIKSS');
    if (!modal) {
        console.error('Modal formIKSS not found');
        return;
    }
    
    document.getElementById('formIKSSTitle').textContent = 'Tambah IKSS Baru';
    document.getElementById('formIKSSId').value = '';
    document.getElementById('formIKSSNomor').value = '';
    document.getElementById('formIKSSSasaran').value = '';
    document.getElementById('formIKSSIndikator').value = '';
    document.getElementById('formIKSSTarget').value = '';
    
    modal.style.display = 'flex';
}

function editIKSSMaster(id) {
    // Get data from manage table
    fetch(`api/ikss.php?action=detail&ikss_id=${id}&triwulan=TW%20I`)
        .then(res => res.json())
        .then(result => {
            if (result.success) {
                const ikss = result.data;
                const modal = document.getElementById('modalFormIKSS');
                if (!modal) return;
                
                document.getElementById('formIKSSTitle').textContent = 'Edit IKSS';
                document.getElementById('formIKSSId').value = ikss.id;
                document.getElementById('formIKSSNomor').value = ikss.nomor;
                document.getElementById('formIKSSSasaran').value = ikss.sasaran_kegiatan;
                document.getElementById('formIKSSIndikator').value = ikss.indikator_kinerja;
                document.getElementById('formIKSSTarget').value = ikss.target || '';
                
                modal.style.display = 'flex';
            } else {
                showToast('Error loading IKSS data', 'error');
            }
        })
        .catch(err => {
            console.error('Error:', err);
            showToast('Error loading IKSS data', 'error');
        });
}

function tutupModalFormIKSS() {
    const modal = document.getElementById('modalFormIKSS');
    if (modal) modal.style.display = 'none';
}

function simpanIKSS() {
    const id = document.getElementById('formIKSSId').value;
    const nomor = document.getElementById('formIKSSNomor').value.trim();
    const sasaran = document.getElementById('formIKSSSasaran').value.trim();
    const indikator = document.getElementById('formIKSSIndikator').value.trim();
    const target = document.getElementById('formIKSSTarget').value.trim();
    
    if (!nomor || !sasaran || !indikator || !target) {
        showToast('Semua field harus diisi', 'error');
        return;
    }
    
    const formData = new FormData();
    if (id) formData.append('id', id);
    formData.append('nomor', nomor);
    formData.append('sasaran_kegiatan', sasaran);
    formData.append('indikator_kinerja', indikator);
    formData.append('target', target);
    
    const action = id ? 'update_ikss' : 'create_ikss';
    
    fetch(`api/ikss.php?action=${action}`, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            tutupModalFormIKSS();
            loadIKSSManage();
            loadIKSSData(currentTriwulan); // Refresh main table
        } else {
            showToast(data.message || 'Error menyimpan IKSS', 'error');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        showToast('Error menyimpan IKSS', 'error');
    });
}

function hapusIKSS(id) {
    if (!confirm('Apakah Anda yakin ingin menghapus IKSS ini?\n\nData yang dihapus tidak dapat dikembalikan.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('id', id);
    
    fetch('api/ikss.php?action=delete_ikss', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            loadIKSSManage();
            loadIKSSData(currentTriwulan); // Refresh main table
        } else {
            showToast(data.message || 'Error menghapus IKSS', 'error');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        showToast('Error menghapus IKSS', 'error');
    });
}

// ============================================
// 8. EVENT LISTENERS & INITIALIZATION
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 IKSS Functions initialized');
    
    // Generate year options
    generateYearOptions();
    
    // Event listener untuk tombol TW
    const twButtons = document.querySelectorAll('.ikss-tw-btn');
    twButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            // Remove active dari semua tombol
            twButtons.forEach(b => {
                b.classList.remove('active');
                b.style.background = 'white';
                b.style.color = '#64748b';
            });
            
            // Tambah active ke tombol yang diklik
            this.classList.add('active');
            this.style.background = '#1e40af';
            this.style.color = 'white';
            
            // Load data dengan triwulan baru
            const triwulan = this.dataset.tw;
            loadIKSSData(triwulan);
        });
    });
    
    // Load IKSS saat halaman monitoring-kinerja aktif
    setTimeout(() => {
        const currentPage = document.querySelector('.content-page.active');
        if (currentPage && currentPage.id === 'page-monitoring-kinerja') {
            console.log('✅ Page is monitoring-kinerja, loading IKSS...');
            loadIKSSData('TW I');
        }
    }, 500);
    
    // Event listener untuk menu Monitoring Capaian Kinerja
    const menuLinks = document.querySelectorAll('.menu-link');
    menuLinks.forEach(link => {
        link.addEventListener('click', function() {
            const onclick = this.getAttribute('onclick');
            if (onclick && onclick.includes('monitoring-kinerja')) {
                setTimeout(() => loadIKSSData(currentTriwulan), 500);
            }
        });
    });
});

// ============================================
// 9. UTILITY: SHOW TOAST (if not exists)
// ============================================
if (typeof showToast === 'undefined') {
    function showToast(message, type = 'info') {
        const colors = {
            success: { bg: '#dcfce7', color: '#16a34a', icon: '✅' },
            error: { bg: '#fee2e2', color: '#dc2626', icon: '❌' },
            info: { bg: '#dbeafe', color: '#2563eb', icon: 'ℹ️' }
        };
        
        const style = colors[type] || colors.info;
        
        const toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${style.bg};
            color: ${style.color};
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            z-index: 10000;
            font-weight: 500;
            animation: slideIn 0.3s ease;
        `;
        toast.textContent = `${style.icon} ${message}`;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
}

console.log('✅ IKSS Functions loaded successfully');
