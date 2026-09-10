<?php
/**
 * SOKAB - Kertas Kerja List Page
 * Display all kertas kerja dengan options edit/view
 */

session_start();
require_once __DIR__ . '/../../includes/check_session.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

$db = getDBConnection();

// Get all kertas kerja
$stmt = $db->prepare("
    SELECT 
        kk.id, 
        kk.satker, 
        kk.nilai_sakip,
        kk.predikat,
        kk.triwulan, 
        kk.tahun,
        kk.status,
        kk.created_at,
        COUNT(kkd.id) as iku_count
    FROM kertas_kerja kk
    LEFT JOIN kertas_kerja_detail kkd ON kk.id = kkd.kertas_kerja_id
    GROUP BY kk.id
    ORDER BY kk.tahun DESC, kk.triwulan ASC
");
$stmt->execute();
$kertas_kerjas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = count($kertas_kerjas);
$draft = count(array_filter($kertas_kerjas, fn($x) => $x['status'] === 'draft'));
$final = count(array_filter($kertas_kerjas, fn($x) => $x['status'] === 'final'));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kertas Kerja Pengukuran Kinerja - SOKAB</title>
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .header {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .header h1 {
            color: #003d82;
            margin-bottom: 0.5rem;
            font-size: 2rem;
        }

        .header p {
            color: #666;
            margin-bottom: 1rem;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem;
            border-radius: 6px;
            text-align: center;
        }

        .stat-card.total { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-card.draft { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-card.final { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }

        .stat-card h3 {
            font-size: 2rem;
            font-weight: bold;
        }

        .stat-card p {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .actions {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
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

        .btn-primary:hover {
            background: #002d5f;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,61,130,0.3);
        }

        .btn-secondary {
            background: #f5f7fa;
            color: #003d82;
            border: 2px solid #003d82;
        }

        .btn-secondary:hover {
            background: #003d82;
            color: white;
        }

        .table-container {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }

        th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #003d82;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
        }

        tbody tr:hover {
            background: #f8f9fa;
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

        .status-submitted {
            background: #d1ecf1;
            color: #0c5460;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-edit {
            background: #003d82;
            color: white;
        }

        .btn-edit:hover {
            background: #002d5f;
        }

        .btn-view {
            background: #6c757d;
            color: white;
        }

        .btn-view:hover {
            background: #5a6268;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background: #c82333;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #999;
        }

        .empty-state p {
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
        }

        .modal-header {
            margin-bottom: 1.5rem;
        }

        .modal-header h2 {
            color: #003d82;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 2rem;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #000;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #003d82;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #003d82;
            box-shadow: 0 0 0 3px rgba(0,61,130,0.1);
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .form-actions button {
            flex: 1;
            padding: 0.75rem;
            border: none;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-submit {
            background: #003d82;
            color: white;
        }

        .btn-submit:hover {
            background: #002d5f;
        }

        .btn-cancel {
            background: #f5f7fa;
            color: #003d82;
            border: 1px solid #ddd;
        }

        .btn-cancel:hover {
            background: #e9ecef;
        }

        .loading {
            text-align: center;
            padding: 2rem;
            color: #999;
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

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Header -->
    <div class="header">
        <h1>📊 Kertas Kerja Pengukuran Kinerja</h1>
        <p>Manajemen Data Pencapaian Kinerja BPS Kota Bima per Triwulan</p>

        <!-- Stats -->
        <div class="stats">
            <div class="stat-card total">
                <h3><?= $total ?></h3>
                <p>Total Kertas Kerja</p>
            </div>
            <div class="stat-card draft">
                <h3><?= $draft ?></h3>
                <p>Draft</p>
            </div>
            <div class="stat-card final">
                <h3><?= $final ?></h3>
                <p>Final</p>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="actions">
        <button class="btn btn-primary" onclick="openCreateModal()">+ Buat Baru</button>
        <button class="btn btn-secondary" onclick="openImportModal()">📤 Import Excel</button>
    </div>

    <!-- Table -->
    <div class="table-container">
        <?php if (empty($kertas_kerjas)): ?>
            <div class="empty-state">
                <p>📭 Belum ada Kertas Kerja</p>
                <button class="btn btn-primary" onclick="openCreateModal()">Buat Kertas Kerja Pertama</button>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Satker</th>
                        <th>Triwulan</th>
                        <th>Tahun</th>
                        <th>SAKIP</th>
                        <th>Jumlah IKU</th>
                        <th>Status</th>
                        <th>Dibuat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($kertas_kerjas as $kk): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($kk['satker']) ?></strong></td>
                            <td><?= $kk['triwulan'] ?></td>
                            <td><?= $kk['tahun'] ?></td>
                            <td><?= $kk['nilai_sakip'] ? $kk['nilai_sakip'] . ' (' . htmlspecialchars($kk['predikat']) . ')' : '-' ?></td>
                            <td><span class="badge"><?= $kk['iku_count'] ?> IKU</span></td>
                            <td>
                                <span class="status-badge status-<?= $kk['status'] ?>">
                                    <?= ucfirst($kk['status']) ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y', strtotime($kk['created_at'])) ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-small btn-edit" onclick="editKertasKerja(<?= $kk['id'] ?>)">Edit</button>
                                    <?php if ($kk['status'] === 'draft'): ?>
                                        <button class="btn-small btn-delete" onclick="deleteKertasKerja(<?= $kk['id'] ?>)">Hapus</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Create Modal -->
<div id="createModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span class="close" onclick="closeCreateModal()">&times;</span>
            <h2>Buat Kertas Kerja Baru</h2>
        </div>
        <form id="createForm" onsubmit="createKertasKerja(event)">
            <div class="form-group">
                <label>Satker</label>
                <input type="text" name="satker" value="BPS Kota Bima" required>
            </div>
            <div class="form-group">
                <label>Triwulan</label>
                <select name="triwulan" required>
                    <option value="">-- Pilih --</option>
                    <option value="I">Triwulan I (Jan-Mar)</option>
                    <option value="II">Triwulan II (Apr-Jun)</option>
                    <option value="III">Triwulan III (Jul-Sep)</option>
                    <option value="IV">Triwulan IV (Oct-Dec)</option>
                </select>
            </div>
            <div class="form-group">
                <label>Tahun</label>
                <input type="number" name="tahun" value="2026" required>
            </div>
            <div class="form-group">
                <label>Nilai SAKIP</label>
                <input type="text" name="nilai_sakip" placeholder="Cth: 75">
            </div>
            <div class="form-group">
                <label>Predikat</label>
                <select name="predikat">
                    <option value="">-- Pilih --</option>
                    <option value="SANGAT BAIK">Sangat Baik</option>
                    <option value="BAIK">Baik</option>
                    <option value="CUKUP">Cukup</option>
                    <option value="KURANG">Kurang</option>
                </select>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-submit">Buat</button>
                <button type="button" class="btn-cancel" onclick="closeCreateModal()">Batal</button>
            </div>
        </form>
    </div>
</div>

<!-- Import Modal -->
<div id="importModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span class="close" onclick="closeImportModal()">&times;</span>
            <h2>Import dari Excel</h2>
        </div>
        <form id="importForm" onsubmit="importExcel(event)">
            <div class="form-group">
                <label>File Excel (.xlsx)</label>
                <input type="file" name="excel_file" accept=".xlsx,.xls" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-submit">Import</button>
                <button type="button" class="btn-cancel" onclick="closeImportModal()">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('createModal').style.display = 'block';
}

function closeCreateModal() {
    document.getElementById('createModal').style.display = 'none';
}

function openImportModal() {
    document.getElementById('importModal').style.display = 'block';
}

function closeImportModal() {
    document.getElementById('importModal').style.display = 'none';
}

function createKertasKerja(event) {
    event.preventDefault();
    const form = event.target;
    const data = new FormData(form);

    fetch('/sokab/api/kertas_kerja_api.php?action=create', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(Object.fromEntries(data))
    })
    .then(res => res.json())
    .then(result => {
        if (result.success) {
            alert('Kertas Kerja berhasil dibuat!');
            window.location.href = '/sokab/pages/pengukuran/kertas-kerja-editor.php?id=' + result.id;
        } else {
            alert('Error: ' + result.message);
        }
    })
    .catch(err => alert('Error: ' + err.message));
}

function editKertasKerja(id) {
    window.location.href = '/sokab/pages/pengukuran/kertas-kerja-editor.php?id=' + id;
}

function deleteKertasKerja(id) {
    if (confirm('Hapus Kertas Kerja ini?')) {
        fetch('/sokab/api/kertas_kerja_api.php?action=delete', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({id: id})
        })
        .then(res => res.json())
        .then(result => {
            if (result.success) {
                alert('Kertas Kerja dihapus!');
                location.reload();
            } else {
                alert('Error: ' + result.message);
            }
        });
    }
}

function importExcel(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);

    fetch('/sokab/api/kertas_kerja_import.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(result => {
        if (result.success) {
            alert('Excel berhasil diimport!');
            location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    });
}

// Close modal when clicking outside
window.onclick = function(event) {
    const createModal = document.getElementById('createModal');
    const importModal = document.getElementById('importModal');
    
    if (event.target === createModal) {
        createModal.style.display = 'none';
    }
    if (event.target === importModal) {
        importModal.style.display = 'none';
    }
}
</script>

</body>
</html>
