<?php
/**
 * SOKAB — Form Entry Capaian Kinerja per IKU
 * URL: /sokab/pages/capaian/entry.php?kode=1.1.1.1&periode_id=1
 */

session_start();
require_once __DIR__ . '/../../includes/check_session.php';
require_once __DIR__ . '/../../config/database.php';
requireLogin();

$db         = getDBConnection();
$kode       = $_GET['kode'] ?? '';
$periode_id = (int)($_GET['periode_id'] ?? 0);

if (!$kode || !$periode_id) {
    header('Location: index.php');
    exit;
}

// Load periode
$stmt = $db->prepare("SELECT * FROM ck_periode WHERE id = ?");
$stmt->execute([$periode_id]);
$periode = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$periode) { header('Location: index.php'); exit; }

// Load IKU master
$stmt = $db->prepare("SELECT * FROM ck_iku_master WHERE kode = ?");
$stmt->execute([$kode]);
$master = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$master) { header('Location: index.php'); exit; }

// Load entry (jika sudah ada)
$stmt = $db->prepare("SELECT * FROM ck_entry_iku WHERE periode_id = ? AND iku_kode = ?");
$stmt->execute([$periode_id, $kode]);
$entry = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

// Load RO master + entry
$stmt = $db->prepare("
    SELECT rm.*, re.vol_ro, re.progres, re.narasi
    FROM ck_ro_master rm
    LEFT JOIN ck_entry_ro re ON re.ro_master_id = rm.id AND re.periode_id = ?
    WHERE rm.iku_kode = ?
    ORDER BY rm.ro_order
");
$stmt->execute([$periode_id, $kode]);
$ros = $stmt->fetchAll(PDO::FETCH_ASSOC);

$tw = $periode['triwulan'];
$tw_idx = array_search($tw, ['I','II','III','IV']) + 1;

// Nilai alokasi untuk TW aktif
$alokasi_tw = (float)($master["alokasi_tw$tw_idx"] ?? 0);
$target      = (float)($master['target'] ?? 0);

// ── Triwulan sebelumnya (untuk Bagian 4: Tindak Lanjut) ──
// TW I mundur ke TW IV tahun sebelumnya — logika sama seperti di api/ikss.php.
$urut = ['I', 'II', 'III', 'IV'];
$idx_tw = array_search($tw, $urut, true);
$tw_sblm    = $urut[($idx_tw + 3) % 4];
$tahun_sblm = ($idx_tw === 0) ? ((int)$periode['tahun'] - 1) : (int)$periode['tahun'];

$stmt = $db->prepare("SELECT * FROM ck_periode WHERE tahun = ? AND triwulan = ?");
$stmt->execute([$tahun_sblm, $tw_sblm]);
$periode_sblm = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

$entry_sblm = [];
$ros_sblm   = [];
if ($periode_sblm) {
    $stmt = $db->prepare("SELECT * FROM ck_entry_iku WHERE periode_id = ? AND iku_kode = ?");
    $stmt->execute([$periode_sblm['id'], $kode]);
    $entry_sblm = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $stmt = $db->prepare("
        SELECT rm.*, re.narasi
        FROM ck_ro_master rm
        LEFT JOIN ck_entry_ro re ON re.ro_master_id = rm.id AND re.periode_id = ?
        WHERE rm.iku_kode = ?
        ORDER BY rm.ro_order
    ");
    $stmt->execute([$periode_sblm['id'], $kode]);
    $ros_sblm = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function val($entry, $key, $default = '') {
    return htmlspecialchars($entry[$key] ?? $default);
}
function numval($entry, $key) {
    $v = $entry[$key] ?? '';
    return $v !== '' && $v !== null ? $v : '';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($kode) ?> — Capaian TW <?= $tw ?> — SOKAB</title>
<style>
:root{
    --ink:#1f2937; --ink-soft:#64748b; --ink-faint:#94a3b8;
    --paper:#fff; --bg:#f4f6f9; --line:#e7eaf0;
    --brand:#e67e22; --brand-dark:#c8641a; --brand-tint:#fff3e8; --brand-line:#f3cc9e;
    --ok:#1f9d5c; --ok-tint:#e9f9ef;
    --err:#e0483f; --err-tint:#fdecea;
    --info:#2563eb; --info-tint:#eef4ff; --info-line:#c9dcfb;
    --history:#8b5e34; --history-tint:#faf3ea; --history-line:#e6cda3;
    --radius:12px; --radius-sm:8px;
    --shadow:0 1px 2px rgba(15,23,42,.04), 0 6px 20px -8px rgba(15,23,42,.10);
}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter','Segoe UI',system-ui,sans-serif;background:var(--bg);color:var(--ink);-webkit-font-smoothing:antialiased}

/* ── Header ── */
.topbar{background:var(--paper);border-bottom:1px solid var(--line);padding:.9rem 2rem;display:flex;align-items:center;gap:1rem;position:sticky;top:0;z-index:100;box-shadow:0 1px 3px rgba(15,23,42,.04)}
.back-btn{background:var(--bg);border:1px solid var(--line);color:var(--ink-soft);padding:.5rem 1rem;border-radius:99px;cursor:pointer;font-size:.83rem;font-weight:600;display:flex;align-items:center;gap:.4rem;transition:.15s}
.back-btn:hover{border-color:var(--brand);color:var(--brand-dark);background:var(--brand-tint)}
.topbar h1{font-size:1.05rem;font-weight:700;color:var(--ink);flex:1;letter-spacing:-.01em}
.tw-badge{background:var(--brand);color:#fff;font-weight:700;font-size:.82rem;padding:.4rem 1rem;border-radius:99px;box-shadow:0 2px 8px -2px rgba(230,126,34,.5)}

/* ── Layout ── */
.main{max-width:1400px;margin:0 auto;padding:1.75rem 2rem 1rem}

/* ── IKU Info Card ── */
.iku-info{background:var(--paper);border-radius:var(--radius);padding:1.4rem 1.6rem;margin-bottom:1.5rem;border-left:4px solid var(--brand);box-shadow:var(--shadow)}
.iku-kode{font-size:.76rem;font-weight:700;color:var(--brand-dark);text-transform:uppercase;letter-spacing:.06em;margin-bottom:.35rem}
.iku-nama{font-size:1.2rem;font-weight:700;color:var(--ink);margin-bottom:.6rem;line-height:1.4;letter-spacing:-.01em}
.iku-meta{display:flex;gap:1.5rem;flex-wrap:wrap;font-size:.82rem;color:var(--ink-soft)}
.iku-meta span{display:flex;align-items:center;gap:.35rem}
.target-row{display:flex;gap:.75rem;margin-top:1rem;flex-wrap:wrap}
.target-item{background:var(--brand-tint);border:1px solid var(--brand-line);border-radius:var(--radius-sm);padding:.55rem .9rem;font-size:.82rem;color:var(--ink-soft)}
.target-item b{color:var(--brand-dark)}

/* ── Navigasi Tab (Bagian 1-4 tidak menumpuk sekaligus) ── */
.tab-nav{display:flex;gap:.6rem;flex-wrap:wrap;margin-bottom:1.5rem}
.tab-nav-btn{background:var(--paper);border:1px solid var(--line);color:var(--ink-soft);padding:.65rem 1.15rem;border-radius:99px;font-weight:600;font-size:.85rem;cursor:pointer;transition:.15s;box-shadow:var(--shadow);font-family:inherit;white-space:nowrap}
.tab-nav-btn:hover{border-color:var(--brand);color:var(--brand-dark)}
.tab-nav-btn.active{background:var(--brand);border-color:var(--brand);color:#fff;box-shadow:0 4px 14px -4px rgba(230,126,34,.5)}
.tab-panel{display:none}
.tab-panel.active{display:block}

/* ── Section ── */
.section{background:var(--paper);border-radius:var(--radius);padding:1.5rem 1.6rem;margin-bottom:1.5rem;box-shadow:var(--shadow)}
.section-title{font-size:.92rem;font-weight:700;color:var(--ink);margin-bottom:1.3rem;padding-bottom:.85rem;border-bottom:1px solid var(--line);display:flex;align-items:center;gap:.55rem;letter-spacing:-.01em}
.section-title .badge-readonly{margin-left:auto;font-size:.68rem;font-weight:700;color:var(--history);background:var(--history-tint);border:1px solid var(--history-line);padding:.25rem .65rem;border-radius:99px;text-transform:none;letter-spacing:0}

/* ── Form ── */
.form-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-bottom:1.1rem}
.form-group{display:flex;flex-direction:column;gap:.45rem}
.form-group label{font-size:.79rem;font-weight:700;color:var(--ink-soft)}
.form-group input,.form-group textarea,.form-group select{
    width:100%;padding:.65rem .85rem;border:1px solid var(--line);border-radius:var(--radius-sm);
    font-size:.9rem;font-family:inherit;background:var(--paper);color:var(--ink);
    transition:border-color .15s, box-shadow .15s;
}
.form-group input:focus,.form-group textarea:focus{outline:none;border-color:var(--brand);box-shadow:0 0 0 3px var(--brand-tint)}
.form-group textarea{resize:vertical;min-height:90px;line-height:1.55}
.input-note{font-size:.72rem;color:var(--ink-faint);margin-top:.2rem}

/* ── TW Grid ── */
.tw-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:.75rem;margin-bottom:1rem}
.tw-col{background:var(--bg);border:1px solid var(--line);border-radius:var(--radius-sm);padding:.85rem;transition:.15s}
.tw-col.active{background:var(--brand-tint);border-color:var(--brand-line)}
.tw-col-header{font-size:.72rem;font-weight:700;color:var(--ink-faint);text-transform:uppercase;letter-spacing:.05em;margin-bottom:.65rem}
.tw-col.active .tw-col-header{color:var(--brand-dark)}
.tw-col input{width:100%;border:1px solid var(--line);border-radius:6px;padding:.5rem .6rem;font-size:.9rem;text-align:right;background:var(--paper)}
.tw-col input:disabled{background:#eef0f3;color:var(--ink-faint);cursor:not-allowed}
.capaian-label{font-size:.71rem;color:var(--ink-faint);margin-top:.45rem;text-align:right}
.capaian-val{font-size:.92rem;font-weight:700;color:var(--ok);text-align:right}

/* ── RO Table ── */
.ro-table{width:100%;border-collapse:collapse;font-size:.85rem}
.ro-table th{background:var(--bg);padding:.75rem .85rem;text-align:left;font-size:.74rem;font-weight:700;color:var(--ink-soft);text-transform:uppercase;letter-spacing:.03em;border-bottom:1px solid var(--line)}
.ro-table th:first-child{border-radius:var(--radius-sm) 0 0 0}
.ro-table th:last-child{border-radius:0 var(--radius-sm) 0 0}
.ro-table td{padding:.75rem .85rem;border-bottom:1px solid var(--line);vertical-align:top}
.ro-table tr:last-child td{border-bottom:none}
.ro-table tr:hover td{background:#fbfbfc}
.ro-table input,.ro-table textarea{width:100%;border:1px solid var(--line);border-radius:6px;padding:.45rem .65rem;font-size:.85rem;font-family:inherit}
.ro-table textarea{min-height:70px;resize:vertical}
.ro-table input:focus,.ro-table textarea:focus{outline:none;border-color:var(--brand);box-shadow:0 0 0 3px var(--brand-tint)}
.ro-nama{font-size:.82rem;color:var(--ink);font-weight:600;line-height:1.45}
.ro-narasi-baca{font-size:.82rem;color:var(--ink-soft);line-height:1.55;font-style:italic}
.pct-suffix{display:flex;align-items:center;gap:.35rem}
.pct-suffix input{flex:1}
.pct-suffix span{color:var(--ink-faint);font-size:.8rem}

/* ── Save Bar ── */
.save-bar{position:sticky;bottom:0;background:var(--paper);border-top:1px solid var(--line);padding:1rem 2rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;z-index:50;box-shadow:0 -4px 16px -8px rgba(15,23,42,.08)}
.save-status{font-size:.85rem;color:var(--ink-faint);display:flex;align-items:center;gap:.5rem}
.save-status.saved{color:var(--ok)}
.save-status.saving{color:var(--brand-dark)}
.save-status.error{color:var(--err)}
.btn-simpan{background:var(--brand);color:#fff;border:none;padding:.75rem 1.8rem;border-radius:99px;font-weight:700;font-size:.92rem;cursor:pointer;transition:.15s;display:flex;align-items:center;gap:.5rem;box-shadow:0 4px 14px -4px rgba(230,126,34,.55)}
.btn-simpan:hover:not(:disabled){background:var(--brand-dark);transform:translateY(-1px)}
.btn-simpan:disabled{background:#dcdfe4;color:#9aa1ab;cursor:not-allowed;box-shadow:none}

.ikss-bar{display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;margin-bottom:1.1rem;padding:.75rem 1rem;background:var(--info-tint);border:1px solid var(--info-line);border-radius:var(--radius-sm)}
.btn-ikss{background:var(--info);color:#fff;border:none;padding:.5rem 1rem;border-radius:99px;font-weight:600;font-size:.81rem;cursor:pointer;font-family:inherit;white-space:nowrap;transition:.15s}
.btn-ikss:hover:not(:disabled){background:#1d4ed8}
.btn-ikss:disabled{background:#ccc;cursor:not-allowed}
.ikss-info{font-size:.79rem;color:#4a67a3;line-height:1.5}
.ikss-hint{font-size:.73rem;color:var(--ok);margin-top:.3rem;min-height:1em;font-weight:600}
.ikss-hint.kosong{color:#a3762c}

.btn-kembali{background:var(--paper);color:var(--ink-soft);border:1px solid var(--line);padding:.75rem 1.5rem;border-radius:99px;font-weight:600;font-size:.85rem;cursor:pointer;transition:.15s}
.btn-kembali:hover{background:var(--bg);border-color:#c9cdd4}

.btn-doksum{background:var(--brand);color:#fff;border:none;padding:.6rem 1.2rem;border-radius:99px;font-weight:600;font-size:.83rem;cursor:pointer;font-family:inherit;transition:.15s;display:inline-flex;align-items:center;gap:.4rem;box-shadow:0 3px 10px -3px rgba(230,126,34,.5)}
.btn-doksum:hover:not(:disabled){background:var(--brand-dark);transform:translateY(-1px)}
.btn-doksum:disabled{background:#dcdfe4;color:#9aa1ab;cursor:not-allowed;box-shadow:none}
.btn-doksum.ghost{background:var(--paper);color:var(--history);border:1px solid var(--history-line);box-shadow:none}
.btn-doksum.ghost:hover:not(:disabled){background:var(--history-tint);color:var(--history);transform:translateY(-1px)}

/* ── Toast ── */
.toast{position:fixed;bottom:5rem;right:1.5rem;padding:.75rem 1.3rem;border-radius:var(--radius-sm);font-size:.87rem;font-weight:600;color:#fff;box-shadow:0 8px 24px -6px rgba(15,23,42,.35);z-index:9999;opacity:0;transform:translateY(20px);transition:all .3s}
.toast.show{opacity:1;transform:translateY(0)}
.toast.ok{background:var(--ok)}
.toast.err{background:var(--err)}

.no-ro{color:var(--ink-faint);font-style:italic;padding:.5rem 0;font-size:.85rem}

/* ── Bagian riwayat / read-only (Tindak Lanjut TW Sebelumnya) ── */
.section-sblm{background:var(--history-tint);border:1px solid var(--history-line)}
.section-sblm .section-title{border-bottom-color:var(--history-line);color:var(--history)}
.sblm-ringkasan{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;margin-bottom:1.3rem}
.sblm-item{background:var(--paper);border:1px solid var(--history-line);border-radius:var(--radius-sm);padding:.85rem 1rem}
.sblm-item label{display:block;font-size:.72rem;font-weight:700;color:var(--history);text-transform:uppercase;letter-spacing:.04em;margin-bottom:.4rem}
.sblm-item p{font-size:.84rem;color:var(--ink-soft);line-height:1.55;white-space:pre-line}
.sblm-item p.kosong{color:var(--ink-faint);font-style:italic}
.section-sblm .ro-table{background:var(--paper);border-radius:var(--radius-sm);overflow:hidden;box-shadow:0 1px 2px rgba(15,23,42,.04)}
.section-sblm .ro-table th{background:#f3ece2}

/* ── Galeri Bukti Foto RO ── */
.foto-galeri{display:flex;flex-wrap:wrap;gap:.5rem;margin-bottom:.6rem;min-height:2px}
.foto-thumb{position:relative;width:60px;height:60px;border-radius:8px;overflow:hidden;border:1px solid var(--line);box-shadow:0 1px 3px rgba(15,23,42,.08);transition:.15s}
.foto-thumb:hover{transform:scale(1.05);box-shadow:0 4px 10px rgba(15,23,42,.18)}
.foto-thumb img{width:100%;height:100%;object-fit:cover;display:block;cursor:pointer}
.foto-thumb .hapus{position:absolute;top:0;right:0;background:rgba(15,23,42,.65);color:#fff;border:none;width:18px;height:18px;font-size:.68rem;line-height:18px;cursor:pointer;border-radius:0 0 0 8px}
.foto-thumb .hapus:hover{background:var(--err)}
.foto-thumb.uploading img{opacity:.4}
.btn-foto{background:var(--paper);border:1.5px dashed #cbd0d8;border-radius:var(--radius-sm);padding:.4rem .7rem;font-size:.76rem;color:var(--ink-soft);cursor:pointer;font-family:inherit;font-weight:600;transition:.15s}
.btn-foto:hover{border-color:var(--brand);color:var(--brand-dark);background:var(--brand-tint)}
</style>
</head>
<body>

<div class="topbar">
    <button class="back-btn" onclick="history.back()">← Kembali</button>
    <h1>Entry Capaian Kinerja</h1>
    <span class="tw-badge">TW <?= $tw ?> / <?= $periode['tahun'] ?></span>
</div>

<div class="main">

    <!-- IKU Info -->
    <div class="iku-info">
        <div class="iku-kode">IKU <?= htmlspecialchars($kode) ?> · <?= $master['jenis_iku'] ?> · <?= $master['jenis_periode'] ?></div>
        <div class="iku-nama"><?= htmlspecialchars($master['nama']) ?></div>
        <div class="iku-meta">
            <span>📂 <?= htmlspecialchars($master['sasaran_kode']) ?> — <?= htmlspecialchars(substr($master['sasaran_nama'] ?? '', 0, 60)) ?>...</span>
            <span>📊 <?= $master['jenis_satuan'] === '%' ? 'Persentase (X/Y)' : 'Nilai langsung' ?></span>
            <span>📅 <?= $master['jenis_periode'] ?></span>
        </div>
        <div class="target-row">
            <div class="target-item">Target Tahunan: <b><?= number_format((float)$master['target'], 2) ?> <?= htmlspecialchars($master['satuan']) ?></b></div>
            <div class="target-item">Alokasi TW <?= $tw ?>: <b><?= number_format($alokasi_tw, 2) ?> <?= htmlspecialchars($master['satuan']) ?></b></div>
        </div>
        <div style="margin-top:1.1rem">
            <button type="button" class="btn-doksum" id="btn-doksum" onclick="generateDokumenSumberAktif()">
                📄 <span id="doksum-label">Generate Dokumen Sumber TW <?= $tw ?></span>
            </button>
            <span id="doksum-status" style="font-size:.78rem;color:var(--ink-faint);margin-left:.7rem"></span>
        </div>
    </div>

    <!-- NAVIGASI TAB — supaya 4 bagian tidak menumpuk ke bawah sekaligus -->
    <div class="tab-nav">
        <button type="button" class="tab-nav-btn active" data-tab="1" onclick="gantiTabEntry(1)">📊 Capaian IKU</button>
        <button type="button" class="tab-nav-btn" data-tab="2" onclick="gantiTabEntry(2)">📝 Analisis Capaian IKU</button>
        <button type="button" class="tab-nav-btn" data-tab="3" onclick="gantiTabEntry(3)">📋 Capaian RO</button>
        <?php if ($periode_sblm): ?>
        <button type="button" class="tab-nav-btn" data-tab="4" onclick="gantiTabEntry(4)">🔁 Tindak Lanjut TW Sebelumnya</button>
        <?php endif; ?>
    </div>

    <!-- BAGIAN 1: DATA CAPAIAN -->
    <div class="section tab-panel active" id="tab-panel-1">
        <div class="section-title">📊 Bagian 1 — Data Capaian IKU</div>

        <?php if ($master['jenis_satuan'] === '%'): ?>
        <!-- Input X dan Y per TW -->
        <div style="font-size:.82rem;color:#888;margin-bottom:.75rem">
            Isi X (jumlah yang berkualitas) dan Y (jumlah seluruhnya) untuk setiap triwulan yang sudah berjalan.
            Capaian TW = X ÷ Y × 100 (dihitung otomatis).
        </div>

        <div style="margin-bottom:.5rem;font-size:.8rem;font-weight:700;color:#555">
            X: <?= htmlspecialchars($master['x_label'] ?? '') ?>
        </div>
        <div class="tw-grid" id="grid-x">
            <?php foreach (['I','II','III','IV'] as $i => $t): ?>
            <div class="tw-col <?= $t === $tw ? 'active' : '' ?>">
                <div class="tw-col-header">TW <?= $t ?> <?= $t === $tw ? '← aktif' : '' ?></div>
                <input type="number" id="x_tw<?= $i+1 ?>" name="x_tw<?= $i+1 ?>"
                    value="<?= numval($entry, "x_tw".($i+1)) ?>"
                    min="0" step="1" placeholder="0"
                    onchange="hitungCapaian(<?= $i+1 ?>)">
            </div>
            <?php endforeach; ?>
        </div>

        <div style="margin-bottom:.5rem;font-size:.8rem;font-weight:700;color:#555">
            Y: <?= htmlspecialchars($master['y_label'] ?? '') ?>
        </div>
        <div class="tw-grid" id="grid-y">
            <?php foreach (['I','II','III','IV'] as $i => $t): ?>
            <div class="tw-col <?= $t === $tw ? 'active' : '' ?>">
                <div class="tw-col-header">TW <?= $t ?></div>
                <input type="number" id="y_tw<?= $i+1 ?>" name="y_tw<?= $i+1 ?>"
                    value="<?= numval($entry, "y_tw".($i+1)) ?>"
                    min="0" step="1" placeholder="0"
                    onchange="hitungCapaian(<?= $i+1 ?>)">
                <div class="capaian-label">Capaian TW <?= $t ?></div>
                <div class="capaian-val" id="cap_tw<?= $i+1 ?>">
                    <?php
                        $x = (float)($entry["x_tw".($i+1)] ?? 0);
                        $y = (float)($entry["y_tw".($i+1)] ?? 0);
                        echo $y > 0 ? number_format($x/$y*100, 2).'%' : '—';
                    ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php else: ?>
        <!-- Input realisasi langsung -->
        <div style="font-size:.82rem;color:#888;margin-bottom:.75rem">
            Isi nilai realisasi langsung untuk setiap triwulan yang sudah berjalan.
        </div>
        <div class="tw-grid">
            <?php foreach (['I','II','III','IV'] as $i => $t): ?>
            <div class="tw-col <?= $t === $tw ? 'active' : '' ?>">
                <div class="tw-col-header">TW <?= $t ?> <?= $t === $tw ? '← aktif' : '' ?></div>
                <input type="number" id="realisasi_tw<?= $i+1 ?>" name="realisasi_tw<?= $i+1 ?>"
                    value="<?= numval($entry, "realisasi_tw".($i+1)) ?>"
                    min="0" step="0.0001" placeholder="0"
                    oninput="markDirty()">
                <div class="capaian-label"><?= htmlspecialchars($master['satuan']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- BAGIAN 2: ANALISIS -->
    <div class="section tab-panel" id="tab-panel-2">
        <div class="section-title">📝 Bagian 2 — Analisis Pencapaian TW <?= $tw ?></div>

        <div class="form-row">
            <div class="form-group" style="grid-column:1/-1">
                <label>Kendala yang Dihadapi</label>
                <textarea id="kendala" rows="4" placeholder="Uraikan kendala yang dihadapi pada triwulan ini..."
                    oninput="markDirty()"><?= val($entry, 'kendala') ?></textarea>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group" style="grid-column:1/-1">
                <label>Solusi yang Sudah Dilakukan</label>
                <textarea id="solusi" rows="4" placeholder="Uraikan solusi yang sudah dilakukan..."
                    oninput="markDirty()"><?= val($entry, 'solusi') ?></textarea>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group" style="grid-column:1/-1">
                <label>Rencana Tindak Lanjut (RTL)</label>
                <textarea id="rtl" rows="3" placeholder="Uraikan rencana tindak lanjut..."
                    oninput="markDirty()"><?= val($entry, 'rtl') ?></textarea>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>PIC Tindak Lanjut</label>
                <input type="text" id="pic" value="<?= val($entry, 'pic', $master['pic_default'] ?? '') ?>"
                    placeholder="Nama / jabatan" oninput="markDirty()">
            </div>
            <div class="form-group">
                <label>Batas Waktu TL</label>
                <input type="date" id="batas_waktu" value="<?= val($entry, 'batas_waktu') ?>"
                    oninput="markDirty()">
            </div>
        </div>
        <div class="ikss-bar">
            <button type="button" class="btn-ikss" id="btn-ikss" onclick="ambilDariIKSS()">
                &#x1F517; Ambil dari IKSS
            </button>
            <span class="ikss-info" id="ikss-info">
                Isi kedua tautan di bawah dari data Monitoring Capaian Kinerja Triwulanan.
            </span>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Link Bukti Dukung Kinerja</label>
                <input type="url" id="link_bukti" value="<?= val($entry, 'link_bukti') ?>"
                    placeholder="https://..." oninput="markDirty()">
                <div class="ikss-hint" id="hint-bukti"></div>
                <span class="ikss-info" id="ikss-info">
                Pastikan foto dalam dokumen sumber memiliki timestamp, ya, Guys!
                </span>

            </div>
            <div class="form-group">
                <label>Link Bukti Dukung TL Sebelumnya</label>
                <input type="url" id="link_tl" value="<?= val($entry, 'link_tl') ?>"
                    placeholder="https://..." oninput="markDirty()">
                <div class="ikss-hint" id="hint-tl"></div>
            </div>
        </div>
    </div>

    <!-- BAGIAN 3: RINCIAN OUTPUT -->
    <div class="section tab-panel" id="tab-panel-3">
        <div class="section-title">📋 Bagian 3 — Rincian Output (Sub-sheet <?= htmlspecialchars($kode) ?>)</div>

        <?php if (count($ros) > 0): ?>
        <table class="ro-table">
            <thead>
                <tr>
                    <th style="width:38%">Rincian Output</th>
                    <th style="width:12%">Realisasi Vol</th>
                    <th style="width:14%">Progres (%)</th>
                    <th>Narasi Realisasi</th>
                    <th style="width:16%">Bukti Foto</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ros as $ro): ?>
                <tr data-ro-id="<?= $ro['id'] ?>">
                    <td><div class="ro-nama"><?= htmlspecialchars($ro['nama_ro']) ?></div></td>
                    <td>
                        <input type="number" class="ro-vol" min="0" step="1"
                            value="<?= $ro['vol_ro'] !== null ? $ro['vol_ro'] : '' ?>"
                            placeholder="0" oninput="markDirty()">
                    </td>
                    <td>
                        <div class="pct-suffix">
                            <input type="number" class="ro-progres" min="0" max="100" step="0.01"
                                value="<?= $ro['progres'] !== null ? number_format((float)$ro['progres'] * 100, 2) : '' ?>"
                                placeholder="0.00" oninput="markDirty()">
                            <span>%</span>
                        </div>
                    </td>
                    <td>
                        <textarea class="ro-narasi" rows="3"
                            placeholder="Uraikan realisasi kegiatan ini..."
                            oninput="markDirty()"><?= htmlspecialchars($ro['narasi'] ?? '') ?></textarea>
                    </td>
                    <td>
                        <div class="foto-galeri" id="foto-galeri-<?= $periode_id ?>-<?= $ro['id'] ?>"></div>
                        <button type="button" class="btn-foto" onclick="pilihFoto(<?= $ro['id'] ?>, <?= $periode_id ?>)">📷 Tambah Foto</button>
                        <input type="file" class="foto-input" data-ro-id="<?= $ro['id'] ?>" data-periode-id="<?= $periode_id ?>"
                            accept="image/png,image/jpeg" multiple style="display:none"
                            onchange="unggahFoto(this)">
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p class="no-ro">Tidak ada Rincian Output untuk IKU ini.</p>
        <?php endif; ?>
    </div>

    <!-- BAGIAN 4: TINDAK LANJUT TW SEBELUMNYA (riwayat, hanya foto bisa ditambah) -->
    <?php if ($periode_sblm): ?>
    <div class="section section-sblm tab-panel" id="tab-panel-4">
        <div class="section-title">
            🔁 Bagian 4 — Tindak Lanjut TW <?= htmlspecialchars($periode_sblm['triwulan']) ?> <?= htmlspecialchars($periode_sblm['tahun']) ?>
            <span class="badge-readonly">🔒 Riwayat — teks tak bisa diedit</span>
        </div>

        <div class="sblm-ringkasan">
            <div class="sblm-item">
                <label>Kendala TW lalu</label>
                <p class="<?= empty($entry_sblm['kendala']) ? 'kosong' : '' ?>"><?= htmlspecialchars($entry_sblm['kendala'] ?? '') ?: '(kosong)' ?></p>
            </div>
            <div class="sblm-item">
                <label>Solusi TW lalu</label>
                <p class="<?= empty($entry_sblm['solusi']) ? 'kosong' : '' ?>"><?= htmlspecialchars($entry_sblm['solusi'] ?? '') ?: '(kosong)' ?></p>
            </div>
            <div class="sblm-item">
                <label>Rencana Tindak Lanjut</label>
                <p class="<?= empty($entry_sblm['rtl']) ? 'kosong' : '' ?>"><?= htmlspecialchars($entry_sblm['rtl'] ?? '') ?: '(kosong)' ?></p>
            </div>
            <div class="sblm-item">
                <label>PIC &amp; Batas Waktu</label>
                <p class="<?= empty($entry_sblm['pic']) ? 'kosong' : '' ?>"><?= htmlspecialchars($entry_sblm['pic'] ?? '') ?: '(kosong)' ?><?php if (!empty($entry_sblm['batas_waktu'])): ?> — <?= htmlspecialchars($entry_sblm['batas_waktu']) ?><?php endif; ?></p>
            </div>
        </div>

        <?php if (count($ros_sblm) > 0): ?>
        <table class="ro-table ro-table-sblm">
            <thead>
                <tr>
                    <th style="width:32%">Rincian Output</th>
                    <th>Narasi Realisasi (riwayat)</th>
                    <th style="width:20%">Bukti Foto Tindak Lanjut</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ros_sblm as $ro): ?>
                <tr data-ro-id="<?= $ro['id'] ?>">
                    <td><div class="ro-nama"><?= htmlspecialchars($ro['nama_ro']) ?></div></td>
                    <td><div class="ro-narasi-baca"><?= htmlspecialchars($ro['narasi'] ?? '') ?: '(belum diisi saat itu)' ?></div></td>
                    <td>
                        <div class="foto-galeri" id="foto-galeri-<?= $periode_sblm['id'] ?>-<?= $ro['id'] ?>"></div>
                        <button type="button" class="btn-foto" onclick="pilihFoto(<?= $ro['id'] ?>, <?= $periode_sblm['id'] ?>)">📷 Tambah Foto</button>
                        <input type="file" class="foto-input" data-ro-id="<?= $ro['id'] ?>" data-periode-id="<?= $periode_sblm['id'] ?>"
                            accept="image/png,image/jpeg" multiple style="display:none"
                            onchange="unggahFoto(this)">
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p class="no-ro">Tidak ada Rincian Output untuk IKU ini di triwulan sebelumnya.</p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</div><!-- /main -->

<!-- Save Bar -->
<div class="save-bar">
    <button class="btn-kembali" onclick="history.back()">← Kembali ke List</button>
    <div class="save-status" id="save-status">⬜ Belum ada perubahan</div>
    <button class="btn-simpan" id="btn-simpan" onclick="simpanSemua()" disabled>
        💾 Simpan Data
    </button>
</div>

<div class="toast" id="toast"></div>

<script>
const API        = '../../api/capaian_api.php';
const API_NOTULA = '../../api/notula_api.php';
const PERIODE_ID      = <?= $periode_id ?>;
const PERIODE_ID_SBLM = <?= $periode_sblm ? (int)$periode_sblm['id'] : 'null' ?>;
const LABEL_DOKSUM_AKTIF = <?= json_encode("Generate Dokumen Sumber TW $tw") ?>;
const LABEL_DOKSUM_SBLM  = <?= $periode_sblm ? json_encode('Generate Dokumen Sumber Tindak Lanjut TW ' . $periode_sblm['triwulan']) : 'null' ?>;
const IKU_KODE  = <?= json_encode($kode) ?>;
const JENIS_SAT = <?= json_encode($master['jenis_satuan']) ?>;

let isDirty = false;

function markDirty() {
    if (!isDirty) {
        isDirty = true;
        setStatus('saving', '✏️ Ada perubahan belum tersimpan...');
        document.getElementById('btn-simpan').disabled = false;
    }
}

function setStatus(type, msg) {
    const el = document.getElementById('save-status');
    el.textContent = msg;
    el.className = 'save-status ' + type;
}

function hitungCapaian(twIdx) {
    markDirty();
    const x = parseFloat(document.getElementById(`x_tw${twIdx}`)?.value) || 0;
    const y = parseFloat(document.getElementById(`y_tw${twIdx}`)?.value) || 0;
    const cap = document.getElementById(`cap_tw${twIdx}`);
    if (cap) {
        cap.textContent = y > 0 ? (x / y * 100).toFixed(2) + '%' : '—';
    }
}

async function simpanSemua() {
    const btn = document.getElementById('btn-simpan');
    btn.disabled = true;
    setStatus('saving', '⏳ Menyimpan...');

    try {
        // Build payload IKU
        const payload = {
            periode_id: PERIODE_ID,
            iku_kode:   IKU_KODE,
            kendala:    document.getElementById('kendala')?.value     || null,
            solusi:     document.getElementById('solusi')?.value      || null,
            rtl:        document.getElementById('rtl')?.value         || null,
            pic:        document.getElementById('pic')?.value         || null,
            batas_waktu:document.getElementById('batas_waktu')?.value || null,
            link_bukti: document.getElementById('link_bukti')?.value  || null,
            link_tl:    document.getElementById('link_tl')?.value     || null,
        };

        if (JENIS_SAT === '%') {
            for (let i = 1; i <= 4; i++) {
                const xEl = document.getElementById(`x_tw${i}`);
                const yEl = document.getElementById(`y_tw${i}`);
                payload[`x_tw${i}`] = xEl?.value !== '' ? xEl?.value : null;
                payload[`y_tw${i}`] = yEl?.value !== '' ? yEl?.value : null;
            }
        } else {
            for (let i = 1; i <= 4; i++) {
                const el = document.getElementById(`realisasi_tw${i}`);
                payload[`realisasi_tw${i}`] = el?.value !== '' ? el?.value : null;
            }
        }

        const r1 = await fetch(`${API}?action=save_iku`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        }).then(r => r.json());

        if (!r1.success) throw new Error(r1.message);

        // Simpan RO rows
        // ':not(.ro-table-sblm)' wajib — Bagian 4 (Tindak Lanjut) pakai ro_master_id yang
        // SAMA dengan Bagian 3 tapi read-only; tanpa ini datanya bisa tertimpa kosong.
        const roRows = document.querySelectorAll('.ro-table:not(.ro-table-sblm) tbody tr[data-ro-id]');
        for (const row of roRows) {
            const roId   = parseInt(row.dataset.roId);
            const vol    = row.querySelector('.ro-vol')?.value;
            const progPct = row.querySelector('.ro-progres')?.value;
            const narasi = row.querySelector('.ro-narasi')?.value;

            const roPay = {
                periode_id:   PERIODE_ID,
                ro_master_id: roId,
                iku_kode:     IKU_KODE,
                vol_ro:       vol !== '' ? vol : null,
                progres:      progPct !== '' ? (parseFloat(progPct) / 100).toFixed(6) : null,
                narasi:       narasi || null,
            };

            const r2 = await fetch(`${API}?action=save_ro`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(roPay)
            }).then(r => r.json());

            if (!r2.success) throw new Error('Gagal simpan RO: ' + r2.message);
        }

        isDirty = false;
        btn.disabled = false;
        setStatus('saved', '✅ Tersimpan — ' + new Date().toLocaleTimeString('id-ID'));
        showToast('Data berhasil disimpan!', true);

    } catch (err) {
        btn.disabled = false;
        setStatus('error', '❌ Gagal menyimpan: ' + err.message);
        showToast('Gagal: ' + err.message, false);
    }
}

function showToast(msg, ok) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'toast show ' + (ok ? 'ok' : 'err');
    setTimeout(() => t.classList.remove('show'), 3000);
}

// Warn sebelum meninggalkan halaman jika ada perubahan belum disimpan
window.addEventListener('beforeunload', e => {
    if (isDirty) {
        e.preventDefault();
        e.returnValue = '';
    }
});

// ── Ambil tautan dari IKSS ─────────────────────────────
let IKSS_CACHE = null;

async function muatIKSS() {
    if (IKSS_CACHE) return IKSS_CACHE;
    const r = await fetch(
        `${API}?action=ikss_links&periode_id=${PERIODE_ID}&iku_kode=${encodeURIComponent(IKU_KODE)}`
    ).then(r => r.json());
    if (!r.success) throw new Error(r.message || 'Gagal mengambil data IKSS');
    IKSS_CACHE = r;
    return r;
}

/** Tandai di bawah field, dari mana isinya berasal. */
function tandai(idHint, teks, kosong) {
    const el = document.getElementById(idHint);
    if (!el) return;
    el.textContent = teks;
    el.classList.toggle('kosong', !!kosong);
}

async function ambilDariIKSS() {
    const btn  = document.getElementById('btn-ikss');
    const info = document.getElementById('ikss-info');
    btn.disabled = true;
    info.textContent = 'Mengambil data IKSS...';

    try {
        const r = await muatIKSS();

        if (!r.tersedia) {
            info.textContent = r.pesan || 'Data IKSS tidak tersedia untuk IKU ini.';
            return;
        }

        const fBukti = document.getElementById('link_bukti');
        const fTl    = document.getElementById('link_tl');

        // Jangan timpa isian yang sudah ada tanpa persetujuan
        const akanTertimpa = [];
        if (r.link_bukti && fBukti.value.trim() && fBukti.value.trim() !== r.link_bukti) {
            akanTertimpa.push('Link Bukti Dukung Kinerja');
        }
        if (r.link_tl && fTl.value.trim() && fTl.value.trim() !== r.link_tl) {
            akanTertimpa.push('Link Bukti Dukung TL Sebelumnya');
        }
        if (akanTertimpa.length &&
            !confirm('Isian berikut sudah terisi dan akan diganti:\n\n- ' +
                     akanTertimpa.join('\n- ') + '\n\nLanjutkan?')) {
            info.textContent = 'Dibatalkan, tidak ada yang diubah.';
            return;
        }

        let terisi = 0;

        if (r.link_bukti) {
            fBukti.value = r.link_bukti;
            tandai('hint-bukti', `✓ dari IKSS ${r.triwulan}`, false);
            terisi++;
        } else {
            tandai('hint-bukti', `Belum ada dokumen sumber di IKSS ${r.triwulan}`, true);
        }

        if (r.link_tl) {
            fTl.value = r.link_tl;
            tandai('hint-tl', `✓ dari tindak lanjut IKSS ${r.triwulan_sblm}`, false);
            terisi++;
        } else {
            tandai('hint-tl', `Belum ada tindak lanjut di IKSS ${r.triwulan_sblm}`, true);
        }

        info.textContent = 'Sumber: ' + (r.ikss_indikator || 'IKSS #' + r.ikss_id);

        if (terisi) {
            markDirty();
            showToast(`${terisi} tautan terisi dari IKSS — jangan lupa Simpan Data`);
        } else {
            showToast('Belum ada tautan di IKSS untuk triwulan ini', false);
        }
    } catch (e) {
        info.textContent = e.message;
        showToast(e.message, false);
    } finally {
        btn.disabled = false;
    }
}

// ── Navigasi Tab (Bagian 1-4) ──────────────────────────
// Tab 4 aktif → tombol "Generate Dokumen Sumber" di atas ikut berganti target
// jadi Tindak Lanjut TW sebelumnya (menggantikan, bukan menambah tombol baru di bawah).
let TAB_AKTIF = 1;

function gantiTabEntry(n) {
    TAB_AKTIF = n;
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.getElementById('tab-panel-' + n)?.classList.add('active');
    document.querySelectorAll('.tab-nav-btn').forEach(b => b.classList.toggle('active', b.dataset.tab == n));
    window.scrollTo({ top: 0, behavior: 'smooth' });

    const label = document.getElementById('doksum-label');
    if (n === 4 && LABEL_DOKSUM_SBLM) {
        label.textContent = LABEL_DOKSUM_SBLM;
    } else {
        label.textContent = LABEL_DOKSUM_AKTIF;
    }
}

function generateDokumenSumberAktif() {
    if (TAB_AKTIF === 4 && PERIODE_ID_SBLM) {
        generateDokumenSumber(PERIODE_ID_SBLM, 'btn-doksum', 'doksum-status');
    } else {
        generateDokumenSumber(PERIODE_ID, 'btn-doksum', 'doksum-status');
    }
}

// ── Generate Dokumen Sumber (narasi RO + foto bukti) ──
// periodeId bisa periode aktif (Bagian 1-3) atau periode sebelumnya (Bagian 4 — Tindak Lanjut).
async function generateDokumenSumber(periodeId, btnId, statusId) {
    const btn = document.getElementById(btnId);
    const st  = document.getElementById(statusId);
    if (periodeId === PERIODE_ID && isDirty &&
        !confirm('Ada perubahan belum disimpan. Data yang belum disimpan tidak akan ikut ' +
                 'ke dokumen. Lanjutkan generate dengan data yang sudah tersimpan?')) {
        return;
    }

    btn.disabled = true;
    st.textContent = '⏳ Menyusun dokumen...';

    try {
        const res = await fetch(`${API_NOTULA}?action=generate_dokumen_sumber&periode_id=${periodeId}&iku_kode=${encodeURIComponent(IKU_KODE)}`);
        const ct = res.headers.get('Content-Type') || '';

        if (ct.includes('application/json')) {
            const err = await res.json();
            throw new Error(err.message || 'Gagal generate');
        }

        const blob = await res.blob();
        const disp = res.headers.get('Content-Disposition') || '';
        const nama = (disp.match(/filename="(.+?)"/) || [])[1] || `Bukti_Dokumen_Sumber_${IKU_KODE}.docx`;

        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url; a.download = nama;
        document.body.appendChild(a); a.click(); a.remove();
        URL.revokeObjectURL(url);

        let extra = '';
        try {
            const info = JSON.parse(res.headers.get('X-Dokumen-Sumber-Info') || '{}');
            extra = ` (${info.ro_terisi || 0} RO, ${info.foto_terpasang || 0} foto)`;
        } catch (e) { /* header opsional */ }

        st.textContent = '✅ Selesai' + extra;
        showToast('Dokumen sumber berhasil dibuat' + extra, true);
    } catch (e) {
        st.textContent = '❌ Gagal';
        showToast('Gagal generate: ' + e.message, false);
    } finally {
        btn.disabled = false;
        setTimeout(() => st.textContent = '', 6000);
    }
}

// ── Bukti Foto RO ──────────────────────────────────────
// Galeri diberi id gabungan periode+RO supaya Bagian 3 (periode aktif) dan
// Bagian 4 (periode sebelumnya) tidak saling menimpa walau RO-nya sama.
function renderGaleriFoto(periodeId, roId, daftar) {
    const el = document.getElementById(`foto-galeri-${periodeId}-${roId}`);
    if (!el) return;
    el.innerHTML = (daftar || []).map(f => `
        <div class="foto-thumb" data-foto-id="${f.id}">
            <img src="../../${f.file_path}" onclick="window.open('../../${f.file_path}','_blank')"
                 alt="${(f.keterangan || f.original_name || '').replace(/"/g,'')}">
            <button class="hapus" title="Hapus foto" onclick="hapusFoto(${f.id}, ${roId}, ${periodeId})">✕</button>
        </div>`).join('');
}

async function muatFotoUntukPeriode(periodeId, rowSelector) {
    if (!periodeId) return;
    try {
        const r = await fetch(`${API}?action=foto_list&periode_id=${periodeId}`).then(r => r.json());
        if (!r.success) return;
        document.querySelectorAll(rowSelector).forEach(row => {
            const roId = parseInt(row.dataset.roId);
            renderGaleriFoto(periodeId, roId, r.data[roId] || []);
        });
    } catch (e) { /* galeri kosong, biarkan */ }
}

function muatSemuaFoto() {
    muatFotoUntukPeriode(PERIODE_ID, '.ro-table:not(.ro-table-sblm) tbody tr[data-ro-id]');
    if (PERIODE_ID_SBLM) {
        muatFotoUntukPeriode(PERIODE_ID_SBLM, '.ro-table-sblm tbody tr[data-ro-id]');
    }
}

function pilihFoto(roId, periodeId) {
    document.querySelector(`.foto-input[data-ro-id="${roId}"][data-periode-id="${periodeId}"]`).click();
}

async function unggahFoto(inputEl) {
    const files = inputEl.files;
    if (!files || !files.length) return;
    const roId = inputEl.dataset.roId;
    const periodeId = inputEl.dataset.periodeId;
    const galeri = document.getElementById(`foto-galeri-${periodeId}-${roId}`);

    for (const file of files) {
        if (!['image/png', 'image/jpeg'].includes(file.type)) {
            showToast(`${file.name}: hanya PNG/JPG yang diterima`, false);
            continue;
        }
        const placeholder = document.createElement('div');
        placeholder.className = 'foto-thumb uploading';
        placeholder.innerHTML = '<img src="' + URL.createObjectURL(file) + '">';
        galeri.appendChild(placeholder);

        const fd = new FormData();
        fd.append('ro_master_id', roId);
        fd.append('periode_id', periodeId);
        fd.append('file', file);

        try {
            const r = await fetch(`${API}?action=foto_upload`, { method: 'POST', body: fd }).then(r => r.json());
            if (!r.success) throw new Error(r.message || 'Gagal upload');
            placeholder.dataset.fotoId = r.id;
            placeholder.classList.remove('uploading');
            placeholder.innerHTML += `<button class="hapus" title="Hapus foto" onclick="hapusFoto(${r.id}, ${roId}, ${periodeId})">✕</button>`;
        } catch (e) {
            placeholder.remove();
            showToast(`Gagal upload ${file.name}: ${e.message}`, false);
        }
    }
    inputEl.value = '';
}

async function hapusFoto(fotoId, roId, periodeId) {
    if (!confirm('Hapus foto ini?')) return;
    try {
        const r = await fetch(`${API}?action=foto_delete`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: fotoId })
        }).then(r => r.json());
        if (!r.success) throw new Error(r.message);
        document.querySelector(`#foto-galeri-${periodeId}-${roId} [data-foto-id="${fotoId}"]`)?.remove();
    } catch (e) {
        showToast('Gagal menghapus foto: ' + e.message, false);
    }
}

// Init capaian display
document.addEventListener('DOMContentLoaded', () => {
    for (let i = 1; i <= 4; i++) hitungCapaian(i);
    muatSemuaFoto();

    // Kedua field kosong → isi otomatis, tanpa perlu klik.
    // Kalau salah satu sudah terisi, biarkan; user bisa klik tombolnya sendiri.
    const kosong = !document.getElementById('link_bukti').value.trim()
                && !document.getElementById('link_tl').value.trim();
    if (kosong) {
        muatIKSS().then(r => {
            if (!r.tersedia) return;
            let n = 0;
            if (r.link_bukti) {
                document.getElementById('link_bukti').value = r.link_bukti;
                tandai('hint-bukti', `✓ otomatis dari IKSS ${r.triwulan}`, false);
                n++;
            }
            if (r.link_tl) {
                document.getElementById('link_tl').value = r.link_tl;
                tandai('hint-tl', `✓ otomatis dari tindak lanjut IKSS ${r.triwulan_sblm}`, false);
                n++;
            }
            if (n) {
                document.getElementById('ikss-info').textContent =
                    'Terisi otomatis dari: ' + (r.ikss_indikator || 'IKSS #' + r.ikss_id);
                markDirty();
            }
        }).catch(() => { /* diamkan — tombol manual tetap ada */ });
    }
});
</script>
</body>
</html>
