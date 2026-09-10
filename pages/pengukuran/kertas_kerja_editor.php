<?php
/**
 * SOKAB - Kertas Kerja Editor Page
 * Spreadsheet-like interface dengan inline editing
 */

session_start();
require_once __DIR__ . '/../../includes/check_session.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

$db = getDBConnection();
$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: kertas-kerja-list.php');
    exit;
}

// Get kertas kerja
$stmt = $db->prepare("SELECT * FROM kertas_kerja WHERE id = :id");
$stmt->execute(['id' => $id]);
$kertas_kerja = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$kertas_kerja) {
    header('Location: kertas-kerja-list.php');
    exit;
}

// Get details
$stmt = $db->prepare("
    SELECT * FROM kertas_kerja_detail 
    WHERE kertas_kerja_id = :id 
    ORDER BY row_order ASC
");
$stmt->execute(['id' => $id]);
$details = $stmt->fetchAll(PDO::FETCH_ASSOC);

$isLocked = $kertas_kerja['status'] !== 'draft';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($kertas_kerja['satker']) ?> TW <?= $kertas_kerja['triwulan'] ?> - SOKAB</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            color: #333;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1rem;
        }

        .header-section {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .header-top h1 {
            color: #003d82;
            font-size: 1.5rem;
        }

        .back-link {
            color: #003d82;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .info-item {
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 4px solid #003d82;
        }

        .info-item label {
            display: block;
            font-weight: 600;
            color: #003d82;
            font-size: 0.85rem;
            margin-bottom: 0.4rem;
        }

        .info-item value {
            display: block;
            font-size: 1.1rem;
            color: #333;
        }

        .status-badge {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-draft {
            background: #fff3cd;
            color: #856404;
        }

        .status-final {
            background: #d4edda;
            color: #155724;
        }

        .actions-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #003d82;
            color: white;
        }

        .btn-primary:hover:not(:disabled) {
            background: #002d5f;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,61,130,0.3);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover:not(:disabled) {
            background: #218838;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .table-wrapper {
            background: white;
            border-radius: 8px;
            overflow: auto;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        thead {
            background: #f8f9fa;
            position: sticky;
            top: 0;
        }

        th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #003d82;
            border-bottom: 2px solid #dee2e6;
            white-space: nowrap;
        }

        td {
            padding: 0.75rem;
            border-bottom: 1px solid #dee2e6;
        }

        tbody tr:hover {
            background: #f8f9fa;
        }

        .editable {
            cursor: pointer;
            position: relative;
            background: #fffacd;
        }

        .editable:hover {
            background: #fff8dc;
            border: 1px dashed #003d82;
        }

        .edit-cell {
            position: fixed;
            background: white;
            border: 2px solid #003d82;
            border-radius: 6px;
            padding: 0.75rem;
            min-width: 200px;
            max-width: 400px;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .edit-cell input,
        .edit-cell textarea {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: inherit;
            font-size: inherit;
            margin-bottom: 0.5rem;
        }

        .edit-cell textarea {
            min-height: 80px;
            resize: vertical;
        }

        .edit-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .edit-buttons button {
            flex: 1;
            padding: 0.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
        }

        .edit-save {
            background: #28a745;
            color: white;
        }

        .edit-cancel {
            background: #6c757d;
            color: white;
        }

        .action-col {
            width: 100px;
            text-align: center;
        }

        .btn-small {
            padding: 0.4rem 0.8rem;
            font-size: 0.8rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background: #c82333;
        }

        .row-number {
            width: 50px;
            text-align: center;
            font-weight: 600;
            color: #999;
            background: #f8f9fa;
        }

        .empty-table {
            text-align: center;
            padding: 2rem;
            color: #999;
        }

        .loading {
            text-align: center;
            padding: 2rem;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #003d82;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            display: none;
        }

        .alert.show {
            display: block;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .locked-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 999;
            display: none;
        }

        .locked-overlay.show {
            display: flex;
        }

        .locked-message {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Header -->
    <div class="header-section">
        <div class="header-top">
            <div>
                <h1>📊 Kertas Kerja Pengukuran Kinerja</h1>
                <a href="kertas-kerja-list.php" class="back-link">← Kembali ke List</a>
            </div>
            <div>
                <span class="status-badge status-<?= $kertas_kerja['status'] ?>">
                    <?= ucfirst($kertas_kerja['status']) ?>
                </span>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-item">
                <label>Satker</label>
                <value><?= htmlspecialchars($kertas_kerja['satker']) ?></value>
            </div>
            <div class="info-item">
                <label>Triwulan</label>
                <value><?= $kertas_kerja['triwulan'] ?></value>
            </div>
            <div class="info-item">
                <label>Tahun</label>
                <value><?= $kertas_kerja['tahun'] ?></value>
            </div>
            <div class="info-item">
                <label>Nilai SAKIP</label>
                <value><?= $kertas_kerja['nilai_sakip'] ? $kertas_kerja['nilai_sakip'] . ' (' . htmlspecialchars($kertas_kerja['predikat']) . ')' : '-' ?></value>
            </div>
        </div>
    </div>

    <!-- Alert -->
    <div id="alert" class="alert"></div>

    <!-- Actions -->
    <div class="actions-bar">
        <div>
            <button class="btn btn-primary" onclick="addRow()" <?= $isLocked ? 'disabled' : '' ?>>+ Tambah IKU</button>
            <button class="btn btn-secondary" onclick="downloadExcel()">📥 Download Excel</button>
        </div>
        <div>
            <button class="btn btn-success" onclick="generateNotula()" <?= $isLocked ? 'disabled' : '' ?>>📄 Generate Notula</button>
            <?php if (!$isLocked): ?>
                <button class="btn btn-primary" onclick="finalizeKertasKerja()">🔒 Finalize</button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Table -->
    <div class="table-wrapper">
        <table id="dataTable">
            <thead>
                <tr>
                    <th class="row-number">#</th>
                    <th>Kode IKU</th>
                    <th>Nama IKU</th>
                    <th>Target PK</th>
                    <th>Satuan</th>
                    <th>Alokasi TW</th>
                    <th>Realisasi</th>
                    <th>Capaian TW (%)</th>
                    <th>Capaian PK (%)</th>
                    <th>Kendala</th>
                    <th>Solusi</th>
                    <th>RTL</th>
                    <th>PIC</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php if (empty($details)): ?>
                    <tr>
                        <td colspan="14" class="empty-table">
                            <?php if ($isLocked): ?>
                                Tidak ada data IKU
                            <?php else: ?>
                                Belum ada IKU. <a href="#" onclick="addRow(); return false;">Tambah IKU</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($details as $i => $detail): ?>
                        <tr data-id="<?= $detail['id'] ?>">
                            <td class="row-number"><?= $i + 1 ?></td>
                            <td class="editable" data-field="iku_code"><?= htmlspecialchars($detail['iku_code'] ?? '') ?></td>
                            <td class="editable" data-field="iku_nama"><?= htmlspecialchars($detail['iku_nama'] ?? '') ?></td>
                            <td class="editable" data-field="target_pk"><?= $detail['target_pk'] ?? '' ?></td>
                            <td class="editable" data-field="satuan"><?= htmlspecialchars($detail['satuan'] ?? '') ?></td>
                            <td class="editable" data-field="alokasi_tw"><?= $detail['alokasi_tw'] ?? '' ?></td>
                            <td class="editable" data-field="realisasi"><?= $detail['realisasi'] ?? '' ?></td>
                            <td class="editable" data-field="capaian_tw"><?= $detail['capaian_tw'] ?? '' ?></td>
                            <td class="editable" data-field="capaian_pk"><?= $detail['capaian_pk'] ?? '' ?></td>
                            <td class="editable" data-field="kendala"><?= htmlspecialchars($detail['kendala'] ?? '') ?></td>
                            <td class="editable" data-field="solusi"><?= htmlspecialchars($detail['solusi'] ?? '') ?></td>
                            <td class="editable" data-field="rtl"><?= htmlspecialchars($detail['rtl'] ?? '') ?></td>
                            <td class="editable" data-field="pic"><?= htmlspecialchars($detail['pic'] ?? '') ?></td>
                            <td class="action-col">
                                <button class="btn-small btn-delete" onclick="deleteRow(<?= $detail['id'] ?>)" <?= $isLocked ? 'disabled' : '' ?>>Hapus</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Locked Overlay -->
<div id="lockedOverlay" class="locked-overlay" <?= $isLocked ? 'style="display: flex;"' : '' ?>>
    <div class="locked-message">
        <h2>🔒 Kertas Kerja Terkunci</h2>
        <p style="margin-top: 1rem; color: #666;">Status Final - Data tidak dapat diubah</p>
    </div>
</div>

<script>
const KERTAS_KERJA_ID = <?= $id ?>;
const IS_LOCKED = <?= $isLocked ? 'true' : 'false' ?>;

// Edit cell on click
document.querySelectorAll('.editable').forEach(cell => {
    cell.addEventListener('click', function(e) {
        if (IS_LOCKED) return;
        
        const row = this.closest('tr');
        const detailId = row.dataset.id;
        const field = this.dataset.field;
        const value = this.textContent;
        
        editCell(this, detailId, field, value);
    });
});

function editCell(element, detailId, field, value) {
    const rect = element.getBoundingClientRect();
    const editBox = document.createElement('div');
    editBox.className = 'edit-cell';
    editBox.style.top = (rect.top + window.scrollY) + 'px';
    editBox.style.left = (rect.left + window.scrollX) + 'px';
    
    const isLargeField = ['kendala', 'solusi', 'rtl'].includes(field);
    
    editBox.innerHTML = `
        ${isLargeField ? 
            `<textarea id="editInput" autofocus>${value}</textarea>` :
            `<input type="text" id="editInput" value="${value}" autofocus>`
        }
        <div class="edit-buttons">
            <button class="edit-save" onclick="saveEdit(${detailId}, '${field}')">Simpan</button>
            <button class="edit-cancel" onclick="cancelEdit()">Batal</button>
        </div>
    `;
    
    document.body.appendChild(editBox);
    document.getElementById('editInput').focus();
    
    // Save on Enter (for input fields)
    if (!isLargeField) {
        document.getElementById('editInput').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                saveEdit(detailId, field);
            }
        });
    }
    
    // Cancel on Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            cancelEdit();
        }
    });
}

function saveEdit(detailId, field) {
    const value = document.getElementById('editInput').value;
    
    fetch('/sokab/api/kertas_kerja_api.php?action=update', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            detail_id: detailId,
            field: field,
            value: value
        })
    })
    .then(res => res.json())
    .then(result => {
        if (result.success) {
            cancelEdit();
            location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    })
    .catch(err => alert('Error: ' + err.message));
}

function cancelEdit() {
    const editBox = document.querySelector('.edit-cell');
    if (editBox) editBox.remove();
}

function addRow() {
    if (IS_LOCKED) {
        alert('Kertas Kerja sudah final, tidak bisa menambah baris');
        return;
    }
    
    fetch('/sokab/api/kertas_kerja_api.php?action=add-detail', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            kertas_kerja_id: KERTAS_KERJA_ID
        })
    })
    .then(res => res.json())
    .then(result => {
        if (result.success) {
            location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    });
}

function deleteRow(detailId) {
    if (!confirm('Hapus baris ini?')) return;
    
    fetch('/sokab/api/kertas_kerja_api.php?action=delete-detail', {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            detail_id: detailId
        })
    })
    .then(res => res.json())
    .then(result => {
        if (result.success) {
            location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    });
}

function generateNotula() {
    window.open('/sokab/api/kertas_kerja_notula_api.php?id=' + KERTAS_KERJA_ID, '_blank');
}

function downloadExcel() {
    // TODO: Implement Excel export
    alert('Feature coming soon!');
}

function finalizeKertasKerja() {
    if (!confirm('Setelah di-finalize, data tidak bisa diubah. Lanjutkan?')) {
        return;
    }
    
    fetch('/sokab/api/kertas_kerja_api.php?action=finalize', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            id: KERTAS_KERJA_ID
        })
    })
    .then(res => res.json())
    .then(result => {
        if (result.success) {
            alert('Kertas Kerja telah di-finalize');
            location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    });
}
</script>

</body>
</html>
