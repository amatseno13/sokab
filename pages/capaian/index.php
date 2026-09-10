<?php
/**
 * SOKAB — Capaian Kinerja Triwulanan
 * Halaman: /sokab/pages/capaian/index.php
 */

session_start();
require_once __DIR__ . '/../../includes/check_session.php';
require_once __DIR__ . '/../../config/database.php';
requireLogin();

$db = getDBConnection();
$stmt = $db->query("SELECT * FROM ck_periode ORDER BY tahun DESC, FIELD(triwulan,'I','II','III','IV')");
$periodes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Kelompokkan per tahun untuk filter bertingkat: tahun → triwulan
$per_tahun = [];
foreach ($periodes as $p) {
    $per_tahun[(int)$p['tahun']][] = ['id' => (int)$p['id'], 'tw' => $p['triwulan'], 'status' => $p['status']];
}
krsort($per_tahun);
$daftar_tahun = array_keys($per_tahun);

// Default: tahun berjalan bila ada, kalau tidak tahun terbaru
$tahun_kini    = (int)date('Y');
$tahun_default = in_array($tahun_kini, $daftar_tahun, true)
    ? $tahun_kini
    : ($daftar_tahun[0] ?? $tahun_kini);

// Triwulan default: yang open di tahun itu, kalau tidak ada ambil yang pertama
$tw_default = null;
foreach ($per_tahun[$tahun_default] ?? [] as $t) {
    if ($t['status'] === 'open') { $tw_default = $t; break; }
}
if (!$tw_default && !empty($per_tahun[$tahun_default])) $tw_default = $per_tahun[$tahun_default][0];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Capaian Kinerja Triwulanan — SOKAB</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',sans-serif;background:#f5f7fa;color:#2c3e50}

.header{background:#fff;padding:1.5rem 2rem;border-bottom:3px solid #e67e22;display:flex;align-items:center;gap:1rem;flex-wrap:wrap}
.header h1{font-size:1.4rem;color:#e67e22;flex:1}
.badge-tw{background:#e67e22;color:#fff;padding:.4rem 1rem;border-radius:20px;font-weight:700;font-size:.9rem}

.controls{background:#fff;padding:1rem 2rem;border-bottom:1px solid #eee;display:flex;align-items:center;gap:1rem;flex-wrap:wrap}
.controls label{font-weight:600;font-size:.9rem;color:#555}
.controls select{padding:.5rem .8rem;border:1px solid #ddd;border-radius:6px;font-size:.9rem;background:#fff;cursor:pointer}
.controls select:focus{outline:none;border-color:#e67e22;box-shadow:0 0 0 3px rgba(230,126,34,.15)}

.progress-bar-wrap{flex:1;min-width:200px}
.progress-label{display:flex;justify-content:space-between;font-size:.8rem;color:#888;margin-bottom:.4rem}
.progress-bar{height:8px;background:#eee;border-radius:4px;overflow:hidden}
.progress-fill{height:100%;background:#e67e22;border-radius:4px;transition:width .5s ease}

.main{padding:1.5rem 2rem;max-width:1200px;margin:0 auto}

.section-header{font-size:.8rem;font-weight:700;color:#999;text-transform:uppercase;letter-spacing:.08em;margin:1.5rem 0 .75rem}

.iku-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:1rem}

.iku-card{background:#fff;border-radius:10px;border:2px solid #eee;cursor:pointer;transition:all .2s;overflow:hidden}
.iku-card:hover{border-color:#e67e22;box-shadow:0 4px 16px rgba(230,126,34,.15);transform:translateY(-2px)}
.iku-card.status-isi{border-left:5px solid #27ae60}
.iku-card.status-sebagian{border-left:5px solid #f39c12}
.iku-card.status-kosong{border-left:5px solid #ddd}

.card-top{padding:1rem 1rem .7rem;display:flex;align-items:flex-start;gap:.75rem}
.kode-badge{background:#fff3e0;color:#e67e22;font-weight:700;font-size:.85rem;padding:.35rem .7rem;border-radius:6px;white-space:nowrap;border:1px solid #f0c080}
.card-nama{font-size:.9rem;font-weight:600;color:#2c3e50;line-height:1.4}
.card-sasaran{font-size:.78rem;color:#888;margin-top:.25rem}

.card-bottom{padding:.7rem 1rem;background:#fafafa;border-top:1px solid #eee;display:flex;align-items:center;justify-content:space-between}
.status-pill{font-size:.75rem;font-weight:700;padding:.3rem .7rem;border-radius:12px;display:flex;align-items:center;gap:.3rem}
.status-pill.isi{background:#e8f8ef;color:#27ae60}
.status-pill.sebagian{background:#fef9e7;color:#f39c12}
.status-pill.kosong{background:#f5f5f5;color:#aaa}
.last-edit{font-size:.73rem;color:#aaa}

.btn-export{background:#1e7a48;color:#fff;border:none;padding:.5rem 1rem;border-radius:6px;font-weight:600;font-size:.83rem;cursor:pointer;font-family:inherit;white-space:nowrap}
.btn-export:hover:not(:disabled){background:#166139}
.btn-export:disabled{background:#ddd;color:#999;cursor:not-allowed}

.btn-isi{background:#e67e22;color:#fff;border:none;padding:.5rem 1.1rem;border-radius:6px;font-weight:600;font-size:.82rem;cursor:pointer;transition:background .2s}
.btn-isi:hover{background:#d35400}

/* Loading */
.loading{display:flex;align-items:center;justify-content:center;gap:.75rem;padding:3rem;color:#aaa;font-size:.9rem}
.spinner{width:24px;height:24px;border:3px solid #eee;border-top-color:#e67e22;border-radius:50%;animation:spin .8s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}

/* Toast */
.toast{position:fixed;bottom:1.5rem;right:1.5rem;background:#27ae60;color:#fff;padding:.75rem 1.2rem;border-radius:8px;font-size:.9rem;font-weight:600;box-shadow:0 4px 12px rgba(0,0,0,.2);transform:translateY(100px);opacity:0;transition:all .3s;z-index:9999}
.toast.show{transform:translateY(0);opacity:1}
</style>
</head>
<body>

<div class="header">
    <div style="font-size:1.8rem">📈</div>
    <h1>Capaian Kinerja Triwulanan</h1>
    <span class="badge-tw" id="badge-tw">TW ? / 2026</span>
</div>

<div class="controls">
    <label>Tahun:</label>
    <select id="sel-tahun" onchange="gantiTahun()">
        <?php foreach ($daftar_tahun as $th): ?>
        <option value="<?= $th ?>" <?= $th === $tahun_default ? 'selected' : '' ?>><?= $th ?></option>
        <?php endforeach; ?>
    </select>

    <label>Triwulan:</label>
    <select id="sel-periode" onchange="loadIKU()"></select>

    <button class="btn-export" id="btn-export" onclick="exportExcel()"
            title="Isi kertas kerja Excel dengan data periode ini">
        &#x1F4D7; Export Kertas Kerja
    </button>

    <div class="progress-bar-wrap">
        <div class="progress-label">
            <span id="prog-text">Memuat...</span>
            <span id="prog-pct">0%</span>
        </div>
        <div class="progress-bar">
            <div class="progress-fill" id="prog-fill" style="width:0%"></div>
        </div>
    </div>
</div>

<div class="main">
    <div id="iku-container">
        <div class="loading">
            <div class="spinner"></div>
            Memuat data IKU...
        </div>
    </div>
</div>

<div class="toast" id="toast"></div>

<script>
const API = '../../api/capaian_api.php';
const API_EXPORT = '../../api/export_kertas_kerja.php';

// Peta tahun → daftar triwulan, disusun di sisi PHP
const PERIODE = <?= json_encode($per_tahun, JSON_UNESCAPED_UNICODE) ?>;
const TW_DEFAULT = <?= json_encode($tw_default['id'] ?? null) ?>;

/** Isi ulang dropdown triwulan sesuai tahun yang dipilih. */
function isiTriwulan(pilihId) {
    const tahun = document.getElementById('sel-tahun').value;
    const sel = document.getElementById('sel-periode');
    const daftar = PERIODE[tahun] || [];

    sel.innerHTML = daftar.map(t =>
        `<option value="${t.id}">Triwulan ${t.tw}${t.status === 'open' ? ' (aktif)' : ''}</option>`
    ).join('');

    if (pilihId && daftar.some(t => t.id === pilihId)) {
        sel.value = pilihId;
    } else {
        // pertahankan triwulan yang sedang dilihat saat berpindah tahun
        const twAktif = daftar.find(t => t.status === 'open');
        if (twAktif) sel.value = twAktif.id;
    }
    sel.disabled = daftar.length === 0;
}

function gantiTahun() {
    // ingat triwulan yang sedang dibuka, supaya ganti tahun tidak melompat
    const sel = document.getElementById('sel-periode');
    const twLama = sel.selectedIndex >= 0 ? sel.options[sel.selectedIndex].text.match(/Triwulan (\w+)/) : null;

    isiTriwulan(null);

    if (twLama) {
        const tahun = document.getElementById('sel-tahun').value;
        const cocok = (PERIODE[tahun] || []).find(t => t.tw === twLama[1]);
        if (cocok) sel.value = cocok.id;
    }
    loadIKU();
}

const TUJUAN_LABEL = {
    'T1': 'T1 — Kebijakan Berbasis Data',
    'T2': 'T2 — Sistem Statistik Nasional',
    'T3': 'T3 — Tata Kelola BPS',
};

function getPeriodeId() {
    return parseInt(document.getElementById('sel-periode').value);
}

async function loadIKU() {
    const pid = getPeriodeId();
    const selEl = document.getElementById('sel-periode');
    if (selEl.selectedIndex < 0) {
        document.getElementById('iku-container').innerHTML =
            '<div class="loading">Tidak ada periode untuk tahun ini.</div>';
        return;
    }
    const selTxt = selEl.options[selEl.selectedIndex].text.replace(' (aktif)', '');
    const thn = document.getElementById('sel-tahun').value;
    document.getElementById('badge-tw').textContent = `${selTxt} — ${thn}`;
    document.getElementById('iku-container').innerHTML = '<div class="loading"><div class="spinner"></div>Memuat...</div>';

    const [r1, r2] = await Promise.all([
        fetch(`${API}?action=list_iku&periode_id=${pid}`).then(r => r.json()),
        fetch(`${API}?action=summary&periode_id=${pid}`).then(r => r.json()),
    ]);

    if (!r1.success) return;

    // Update progress
    const { total, isi } = r2;
    const pct = Math.round(isi / total * 100);
    document.getElementById('prog-text').textContent = `${isi} dari ${total} IKU telah diisi`;
    document.getElementById('prog-pct').textContent = `${pct}%`;
    document.getElementById('prog-fill').style.width = pct + '%';

    // Group by tujuan
    const groups = {};
    r1.data.forEach(iku => {
        const key = iku.kode.startsWith('1') ? 'T1' : iku.kode.startsWith('2') ? 'T2' : 'T3';
        (groups[key] = groups[key] || []).push(iku);
    });

    let html = '';
    for (const [tkey, ikuList] of Object.entries(groups)) {
        html += `<div class="section-header">${TUJUAN_LABEL[tkey] || tkey}</div>`;
        html += '<div class="iku-grid">';
        ikuList.forEach(iku => {
            const st = iku.status_isi || 'kosong';
            const stLabel = { isi: '✅ Sudah Diisi', sebagian: '⚠️ Sebagian', kosong: '⬜ Belum Diisi' }[st];
            const lastEdit = iku.updated_at
                ? `${iku.diisi_oleh || 'user'} · ${iku.updated_at.substring(0,16)}`
                : '';
            html += `
            <div class="iku-card status-${st}" onclick="bukaEntry('${iku.kode}')">
                <div class="card-top">
                    <div>
                        <div class="kode-badge">${iku.kode}</div>
                    </div>
                    <div>
                        <div class="card-nama">${iku.nama}</div>
                        <div class="card-sasaran">${iku.sasaran_kode} · ${(iku.sasaran_nama||'').substring(0,60)}...</div>
                    </div>
                </div>
                <div class="card-bottom">
                    <span class="status-pill ${st}">${stLabel}</span>
                    <span class="last-edit">${lastEdit}</span>
                    <button class="btn-isi" onclick="event.stopPropagation();bukaEntry('${iku.kode}')">
                        ${st === 'kosong' ? 'Isi Data' : 'Edit'}
                    </button>
                </div>
            </div>`;
        });
        html += '</div>';
    }
    document.getElementById('iku-container').innerHTML = html;
}

function bukaEntry(kode) {
    const pid = getPeriodeId();
    window.location.href = `entry.php?kode=${encodeURIComponent(kode)}&periode_id=${pid}`;
}

function showToast(msg, ok = true) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.style.background = ok ? '#27ae60' : '#e74c3c';
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
}

// ── Export kertas kerja Excel ──────────────────────────
async function cekExport() {
    const btn = document.getElementById('btn-export');
    try {
        const r = await fetch(`${API_EXPORT}?action=cek&_=${Date.now()}`,
                              { cache: 'no-store' }).then(r => r.json());
        // Mesin dokumen kini PHP murni: yang dicek ekstensi zip/dom, bukan Python
        const siap = r.zip_ok && r.dom_ok && r.template_ok && !r.masalah_tmp;
        btn.disabled = !siap;
        if (!siap) {
            const sebab = !r.zip_ok      ? 'ekstensi PHP "zip" tidak aktif — hPanel → PHP Configuration'
                        : !r.dom_ok      ? 'ekstensi PHP "xml/dom" tidak aktif'
                        : !r.template_ok ? 'template kertas_kerja.xlsx belum ada di tools/notula/template/'
                        : r.masalah_tmp;
            btn.title = 'Belum bisa export — ' + sebab;
            console.warn('[export] belum siap:', sebab, r);
        } else {
            btn.title = 'Isi kertas kerja Excel dengan data periode ini';
        }
    } catch (e) {
        btn.disabled = true;
        btn.title = 'Gagal mengecek kesiapan export';
    }
}

async function exportExcel() {
    const btn = document.getElementById('btn-export');
    const pid = getPeriodeId();
    if (!pid) { showToast('Pilih periode dulu', false); return; }

    const teksAsli = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '⏳ Menyusun...';

    try {
        const res = await fetch(`${API_EXPORT}?action=export&periode_id=${pid}`);
        if ((res.headers.get('Content-Type') || '').includes('application/json')) {
            const err = await res.json();
            throw new Error(err.message || 'Gagal export');
        }

        const blob = await res.blob();
        const disp = res.headers.get('Content-Disposition') || '';
        const nama = (disp.match(/filename="(.+?)"/) || [])[1] || 'Kertas_Kerja.xlsx';

        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url; a.download = nama;
        document.body.appendChild(a); a.click(); a.remove();
        URL.revokeObjectURL(url);

        let pesan = 'Kertas kerja terunduh: ' + nama;
        try {
            const info = JSON.parse(res.headers.get('X-Export-Info') || '{}');
            if (info.sel_ditulis !== undefined) pesan += ` (${info.sel_ditulis} sel terisi)`;
            if (info.iku_tak_ketemu && info.iku_tak_ketemu.length) {
                pesan += ' — IKU tanpa baris di template: ' + info.iku_tak_ketemu.join(', ');
            }
        } catch (e) { /* header opsional */ }
        showToast(pesan);
    } catch (e) {
        showToast(e.message, false);
    } finally {
        btn.disabled = false;
        btn.innerHTML = teksAsli;
    }
}

// Init
isiTriwulan(TW_DEFAULT);
loadIKU();
cekExport();
</script>
</body>
</html>
