// ============================================================
// PERMINDOK FUNCTIONS - MASAKO Style (Like IKSS)
// ============================================================

// Global state
let allPermindokData = [];
let currentPermindokPage = 1;
let permindokRowsPerPage = 10;
let currentPermindokTahun = new Date().getFullYear();

// ==================== Load Permindok Data ====================
async function loadPermindokData(tahun = null) {
    try {
        if (tahun) currentPermindokTahun = tahun;
        
        const response = await fetch(`api/permindok.php?action=list&tahun=${currentPermindokTahun}`);
        const result = await response.json();
        
        if (result.success) {
            allPermindokData = result.data;
            currentPermindokPage = 1; // Reset to first page
            renderPermindokTable();
        } else {
            console.error('Error loading permindok:', result.message);
            showToast('Gagal memuat data permindok', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Terjadi kesalahan saat memuat data', 'error');
    }
}

// ==================== Render Permindok Table ====================
function renderPermindokTable() {
    const tbody = document.getElementById('permindokTableBody');
    if (!tbody) return;
    
    // Pagination calculation
    const startIndex = (currentPermindokPage - 1) * permindokRowsPerPage;
    const endIndex = startIndex + permindokRowsPerPage;
    const paginatedData = allPermindokData.slice(startIndex, endIndex);
    
    if (paginatedData.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="3" style="text-align: center; padding: 2rem; color: #94a3b8;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📄</div>
                    <div style="font-size: 1.1rem; font-weight: 500; color: #64748b;">Belum ada data permindok untuk tahun ${currentPermindokTahun}</div>
                </td>
            </tr>
        `;
        updatePermindokPaginationInfo();
        return;
    }
    
    tbody.innerHTML = paginatedData.map(item => `
        <tr style="border-bottom: 1px solid #e2e8f0;">
            <td style="padding: 1rem; text-align: center; color: #64748b; font-weight: 500;">${item.nomor}</td>
            <td style="padding: 1rem; color: #1e293b; line-height: 1.5; text-align: justify;">
                <div style="font-size: 0.9rem;">${escapeHtml(item.judul)}</div>
            </td>
            <td style="padding: 0.5rem; vertical-align: middle;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    ${item.link_permindok ? `
                        <a href="${escapeHtml(item.link_permindok)}" 
                           target="_blank" 
                           style="flex: 1; color: #2563eb; text-decoration: none; font-size: 0.85rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"
                           title="${escapeHtml(item.link_permindok)}">
                            🔗 Lihat
                        </a>
                    ` : `<span style="flex: 1; color: #94a3b8; font-size: 0.85rem;">Belum ada</span>`}
                    ${isAdmin ? `
                        <button onclick='editPermindokLink(${item.id}, ${JSON.stringify(item.judul)}, ${JSON.stringify(item.link_permindok || "")})' 
                                style="padding: 0.35rem 0.75rem; background: #f59e0b; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.75rem; white-space: nowrap; font-weight: 500;">
                            Edit
                        </button>
                    ` : ''}
                </div>
            </td>
        </tr>
    `).join('');
    
    updatePermindokPaginationInfo();
    renderPermindokPagination();
}

// ==================== Edit Permindok Link ====================
function editPermindokLink(id, judul, currentLink) {
    document.getElementById('editPermindokId').value = id;
    document.getElementById('editPermindokJudul').textContent = judul;
    document.getElementById('editPermindokLink').value = currentLink;
    document.getElementById('modalEditPermindokLink').style.display = 'flex';
}

async function savePermindokLink() {
    const id = document.getElementById('editPermindokId').value;
    const link = document.getElementById('editPermindokLink').value.trim();
    
    try {
        const response = await fetch('api/permindok.php?action=update_link', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, link_permindok: link })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('Link berhasil diperbarui', 'success');
            closeModal('modalEditPermindokLink');
            loadPermindokData(currentPermindokTahun);
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Gagal menyimpan link', 'error');
    }
}

// ==================== Kelola Permindok (Admin) ====================
async function kelolaPermindok() {
    try {
        const response = await fetch('api/permindok.php?action=list_all');
        const result = await response.json();
        
        if (result.success) {
            renderKelolaPermindokTable(result.data);
            document.getElementById('modalKelolaPermindok').style.display = 'flex';
        } else {
            showToast('Gagal memuat data', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Terjadi kesalahan', 'error');
    }
}

function renderKelolaPermindokTable(data) {
    const tbody = document.getElementById('kelolaPermindokTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = data.map(item => `
        <tr style="${item.is_active == 0 ? 'opacity: 0.5; background: #f8fafc;' : ''}">
            <td style="text-align: center;">${item.nomor}</td>
            <td style="text-align: center;">${item.tahun}</td>
            <td style="text-align: justify;">${escapeHtml(item.judul)}</td>
            <td style="text-align: center; font-size: 0.875rem;">
                ${item.link_permindok ? '🔗 Ada' : '❌ Belum'}
            </td>
            <td style="text-align: center;">
                <span style="padding: 0.25rem 0.5rem; background: ${item.is_active == 1 ? '#dcfce7' : '#fee2e2'}; color: ${item.is_active == 1 ? '#166534' : '#991b1b'}; border-radius: 4px; font-size: 0.875rem;">
                    ${item.is_active == 1 ? 'Aktif' : 'Nonaktif'}
                </span>
            </td>
            <td style="text-align: center;">
                ${item.is_active == 1 ? `
                    <button onclick="editPermindokMaster(${item.id})" 
                            style="padding: 0.25rem 0.5rem; background: #f59e0b; color: white; border: none; border-radius: 4px; cursor: pointer; margin-right: 0.25rem; font-size: 0.875rem;">
                        Edit
                    </button>
                    <button onclick='deletePermindok(${item.id}, ${JSON.stringify(item.judul)})' 
                            style="padding: 0.25rem 0.5rem; background: #ef4444; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.875rem;">
                        Hapus
                    </button>
                ` : `
                    <span style="color: #94a3b8; font-size: 0.875rem;">Dihapus</span>
                `}
            </td>
        </tr>
    `).join('');
}

// ==================== Form Permindok ====================
function showFormPermindok(mode = 'create', id = null) {
    document.getElementById('formPermindokMode').value = mode;
    document.getElementById('formPermindokId').value = id || '';
    document.getElementById('formPermindokTitle').textContent = mode === 'create' ? 'Tambah Permindok Baru' : 'Edit Permindok';
    
    if (mode === 'create') {
        document.getElementById('formPermindokNomor').value = '';
        document.getElementById('formPermindokTahun').value = currentPermindokTahun;
        document.getElementById('formPermindokJudul').value = '';
        document.getElementById('formPermindokLink').value = '';
    } else {
        loadPermindokDetail(id);
    }
    
    document.getElementById('modalFormPermindok').style.display = 'flex';
}

async function loadPermindokDetail(id) {
    try {
        const response = await fetch(`api/permindok.php?action=detail&id=${id}`);
        const result = await response.json();
        
        if (result.success) {
            const data = result.data;
            document.getElementById('formPermindokNomor').value = data.nomor;
            document.getElementById('formPermindokTahun').value = data.tahun;
            document.getElementById('formPermindokJudul').value = data.judul;
            document.getElementById('formPermindokLink').value = data.link_permindok || '';
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Gagal memuat detail', 'error');
    }
}

async function saveFormPermindok() {
    const mode = document.getElementById('formPermindokMode').value;
    const id = document.getElementById('formPermindokId').value;
    const nomor = parseInt(document.getElementById('formPermindokNomor').value);
    const tahun = parseInt(document.getElementById('formPermindokTahun').value);
    const judul = document.getElementById('formPermindokJudul').value.trim();
    const link = document.getElementById('formPermindokLink').value.trim();
    
    if (!nomor || !tahun || !judul) {
        showToast('Nomor, tahun, dan judul harus diisi', 'error');
        return;
    }
    
    const action = mode === 'create' ? 'create' : 'update';
    const payload = mode === 'create' 
        ? { nomor, tahun, judul, link_permindok: link }
        : { id, nomor, tahun, judul, link_permindok: link };
    
    try {
        const response = await fetch(`api/permindok.php?action=${action}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast(result.message, 'success');
            closeModal('modalFormPermindok');
            kelolaPermindok(); // Refresh modal kelola
            loadPermindokData(currentPermindokTahun); // Refresh main table
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Gagal menyimpan data', 'error');
    }
}

function editPermindokMaster(id) {
    showFormPermindok('edit', id);
}

async function deletePermindok(id, judul) {
    if (!confirm(`Yakin hapus permindok:\n"${judul}"?`)) return;
    
    try {
        const response = await fetch('api/permindok.php?action=delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('Permindok berhasil dihapus', 'success');
            kelolaPermindok(); // Refresh modal
            loadPermindokData(currentPermindokTahun); // Refresh main table
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Gagal menghapus data', 'error');
    }
}

// ==================== Pagination ====================
function renderPermindokPagination() {
    const paginationDiv = document.getElementById('permindokPagination');
    if (!paginationDiv) return;
    
    const totalPages = Math.ceil(allPermindokData.length / permindokRowsPerPage);
    if (totalPages <= 1) {
        paginationDiv.innerHTML = '';
        return;
    }
    
    let html = `
        <button onclick="changePermindokPage(${currentPermindokPage - 1})" 
                ${currentPermindokPage === 1 ? 'disabled' : ''}
                style="padding: 0.5rem 0.75rem; background: white; border: 1px solid #e2e8f0; border-radius: 6px; cursor: pointer; margin-right: 0.5rem;">
            ‹
        </button>
    `;
    
    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= currentPermindokPage - 1 && i <= currentPermindokPage + 1)) {
            html += `
                <button onclick="changePermindokPage(${i})" 
                        style="padding: 0.5rem 0.75rem; background: ${i === currentPermindokPage ? '#3b82f6' : 'white'}; color: ${i === currentPermindokPage ? 'white' : '#64748b'}; border: 1px solid ${i === currentPermindokPage ? '#3b82f6' : '#e2e8f0'}; border-radius: 6px; cursor: pointer; margin-right: 0.5rem; font-weight: ${i === currentPermindokPage ? '500' : '400'};">
                    ${i}
                </button>
            `;
        } else if (i === currentPermindokPage - 2 || i === currentPermindokPage + 2) {
            html += '<span style="margin-right: 0.5rem; color: #94a3b8;">...</span>';
        }
    }
    
    html += `
        <button onclick="changePermindokPage(${currentPermindokPage + 1})" 
                ${currentPermindokPage === totalPages ? 'disabled' : ''}
                style="padding: 0.5rem 0.75rem; background: white; border: 1px solid #e2e8f0; border-radius: 6px; cursor: pointer;">
            ›
        </button>
    `;
    
    paginationDiv.innerHTML = html;
}

function changePermindokPage(page) {
    const totalPages = Math.ceil(allPermindokData.length / permindokRowsPerPage);
    if (page < 1 || page > totalPages) return;
    currentPermindokPage = page;
    renderPermindokTable();
}

function changePermindokRowsPerPage(rows) {
    permindokRowsPerPage = parseInt(rows);
    currentPermindokPage = 1;
    renderPermindokTable();
}

function updatePermindokPaginationInfo() {
    const infoDiv = document.getElementById('permindokPaginationInfo');
    if (!infoDiv) return;
    
    const start = allPermindokData.length === 0 ? 0 : (currentPermindokPage - 1) * permindokRowsPerPage + 1;
    const end = Math.min(currentPermindokPage * permindokRowsPerPage, allPermindokData.length);
    const total = allPermindokData.length;
    
    infoDiv.textContent = `Menampilkan ${start}-${end} dari ${total}`;
}

// ==================== Utility ====================
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Auto-load on page ready
document.addEventListener('DOMContentLoaded', function() {
    // Populate tahun dropdown dinamis
    const selectTahun = document.getElementById('filterPermindokTahun');
    if (selectTahun) {
        const currentYear = new Date().getFullYear();
        const startYear = 2020;
        const endYear = currentYear + 5; // 5 tahun ke depan
        
        // Clear existing options
        selectTahun.innerHTML = '';
        
        // Generate options dari tahun terbaru ke terlama
        for (let year = endYear; year >= startYear; year--) {
            const option = document.createElement('option');
            option.value = year;
            option.textContent = year;
            if (year === currentYear) {
                option.selected = true;
            }
            selectTahun.appendChild(option);
        }
    }
    
    // Load data
    if (document.getElementById('permindokTableBody')) {
        loadPermindokData();
    }
});
