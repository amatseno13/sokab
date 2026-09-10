<?php
/**
 * SOKAB — Generate Notula Monitoring Kinerja (dari upload Excel)
 * Halaman: /sokab/pages/capaian/notula.php
 */

session_start();
require_once __DIR__ . '/../../includes/check_session.php';
require_once __DIR__ . '/../../config/database.php';
requireLogin();

$db = getDBConnection();
$periodes = $db->query("SELECT * FROM ck_periode ORDER BY tahun DESC, FIELD(triwulan,'I','II','III','IV')")
               ->fetchAll(PDO::FETCH_ASSOC);

// Kelompokkan per tahun untuk pemilih bertingkat pada mode Database
$per_tahun = [];
foreach ($periodes as $p) {
    $per_tahun[(int)$p['tahun']][] = ['id' => (int)$p['id'], 'tw' => $p['triwulan'], 'status' => $p['status']];
}
krsort($per_tahun);
$daftar_tahun = array_keys($per_tahun);
$tahun_kini   = (int)date('Y');
$tahun_awal   = in_array($tahun_kini, $daftar_tahun, true) ? $tahun_kini : ($daftar_tahun[0] ?? $tahun_kini);

$default_periode = null;
foreach ($periodes as $p) { if ($p['status'] === 'open') { $default_periode = $p; break; } }
if (!$default_periode && $periodes) $default_periode = $periodes[0];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Generate Notula — SOKAB</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',sans-serif;background:#f5f7fa;color:#2c3e50}

.header{background:#fff;padding:1.5rem 2rem;border-bottom:3px solid #e67e22;display:flex;align-items:center;gap:1rem;flex-wrap:wrap}
.header h1{font-size:1.4rem;color:#e67e22;flex:1}

.main{padding:1.5rem 2rem;max-width:1100px;margin:0 auto}

.card{background:#fff;border-radius:10px;border:1px solid #eee;padding:1.25rem 1.5rem;margin-bottom:1.25rem}
.card.dim{opacity:.45;pointer-events:none}
.card h2{font-size:.95rem;color:#e67e22;margin-bottom:.35rem;display:flex;align-items:center;gap:.5rem}
.card .hint{font-size:.8rem;color:#999;margin-bottom:1rem;line-height:1.5}
.step{background:#e67e22;color:#fff;width:22px;height:22px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:.78rem;font-weight:700;flex-shrink:0}

.alert{padding:.85rem 1rem;border-radius:8px;font-size:.85rem;margin-bottom:1.25rem;line-height:1.6}
.alert.warn{background:#fef9e7;color:#8a6d1a;border:1px solid #f5d98a}
.alert.err{background:#fdecea;color:#a33227;border:1px solid #f2b8b2}
.alert.ok{background:#e8f8ef;color:#1e7a48;border:1px solid #a8e0c2}
.alert code{background:rgba(0,0,0,.06);padding:.1rem .35rem;border-radius:4px}

.sumber-tab{display:flex;gap:.5rem;margin-bottom:1rem;border-bottom:2px solid #eee}
.tab-btn{background:none;border:none;border-bottom:3px solid transparent;padding:.6rem 1rem;margin-bottom:-2px;font-size:.88rem;font-weight:600;color:#999;cursor:pointer;font-family:inherit}
.tab-btn:hover{color:#e67e22}
.tab-btn.aktif{color:#e67e22;border-bottom-color:#e67e22}

/* Dropzone */
.drop{border:2px dashed #ddd;border-radius:10px;padding:2.25rem 1.5rem;text-align:center;cursor:pointer;transition:.2s;background:#fcfcfc}
.drop:hover,.drop.over{border-color:#e67e22;background:#fff8f2}
.drop .ic{font-size:2.4rem;margin-bottom:.6rem}
.drop .t{font-weight:600;font-size:.95rem}
.drop .s{font-size:.8rem;color:#aaa;margin-top:.35rem}

.filebox{display:flex;align-items:center;gap:.9rem;background:#f0f9f3;border:1px solid #a8e0c2;border-radius:8px;padding:.85rem 1rem}
.filebox .ic{font-size:1.6rem}
.filebox .nm{font-weight:600;font-size:.88rem;word-break:break-all}
.filebox .sz{font-size:.76rem;color:#7a9a86}

.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:1rem}
.field label{display:block;font-size:.78rem;font-weight:700;color:#777;margin-bottom:.35rem}
.field input,.field select{width:100%;padding:.55rem .7rem;border:1px solid #ddd;border-radius:6px;font-size:.88rem;font-family:inherit;background:#fff}
.field input:focus,.field select:focus{outline:none;border-color:#e67e22;box-shadow:0 0 0 3px rgba(230,126,34,.15)}
.field small{display:block;font-size:.72rem;color:#aaa;margin-top:.25rem}

.btn{border:none;border-radius:6px;padding:.6rem 1.2rem;font-weight:600;font-size:.86rem;cursor:pointer;transition:.2s;font-family:inherit}
.btn-primary{background:#e67e22;color:#fff}
.btn-primary:hover:not(:disabled){background:#d35400}
.btn:disabled{background:#ddd;color:#999;cursor:not-allowed}
.btn-ghost{background:#fff;color:#e67e22;border:1px solid #e67e22}
.btn-ghost:hover{background:#fff6ee}
.btn-link{background:none;border:none;color:#999;text-decoration:underline;cursor:pointer;font-size:.8rem;font-family:inherit}
.btn-row{display:flex;gap:.75rem;flex-wrap:wrap;margin-top:1rem;align-items:center}

.stats{display:flex;gap:.6rem;flex-wrap:wrap;margin-bottom:1rem}
.stat{flex:1;min-width:105px;background:#fafafa;border:1px solid #eee;border-radius:8px;padding:.7rem .9rem}
.stat .n{font-size:1.4rem;font-weight:700}
.stat .l{font-size:.72rem;color:#999;text-transform:uppercase;letter-spacing:.05em}
.stat.g .n{color:#27ae60}.stat.y .n{color:#f39c12}.stat.r .n{color:#e74c3c}.stat.b .n{color:#e67e22}

table{width:100%;border-collapse:collapse;font-size:.82rem}
th{background:#fafafa;text-align:left;padding:.6rem .7rem;font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;color:#999;border-bottom:2px solid #eee;white-space:nowrap}
td{padding:.55rem .7rem;border-bottom:1px solid #f2f2f2;vertical-align:top}
td.num{text-align:right;white-space:nowrap;font-variant-numeric:tabular-nums}
tr:hover td{background:#fffaf5}
.kode{font-weight:700;color:#e67e22;white-space:nowrap}
.pill{display:inline-block;font-size:.7rem;font-weight:700;padding:.2rem .5rem;border-radius:10px;white-space:nowrap}
.pill.lengkap{background:#e8f8ef;color:#27ae60}
.pill.sebagian{background:#fef9e7;color:#f39c12}
.pill.kosong{background:#f5f5f5;color:#aaa}

.loading{display:flex;align-items:center;justify-content:center;gap:.75rem;padding:2rem;color:#aaa;font-size:.9rem}
.spinner{width:20px;height:20px;border:3px solid #eee;border-top-color:#e67e22;border-radius:50%;animation:spin .8s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}

.toast{position:fixed;bottom:1.5rem;right:1.5rem;background:#27ae60;color:#fff;padding:.75rem 1.2rem;border-radius:8px;font-size:.86rem;font-weight:600;box-shadow:0 4px 12px rgba(0,0,0,.2);transform:translateY(140px);opacity:0;transition:.3s;z-index:9999;max-width:400px;line-height:1.5}
.toast.show{transform:translateY(0);opacity:1}

/* Galeri foto dokumentasi */
.dok-galeri{display:flex;flex-wrap:wrap;gap:.6rem;min-height:2px}
.dok-thumb{position:relative;width:90px;height:90px;border-radius:8px;overflow:hidden;border:1px solid #eee;box-shadow:0 1px 3px rgba(0,0,0,.08)}
.dok-thumb img{width:100%;height:100%;object-fit:cover;display:block;cursor:pointer}
.dok-thumb .hapus{position:absolute;top:0;right:0;background:rgba(0,0,0,.6);color:#fff;border:none;width:22px;height:22px;font-size:.8rem;line-height:22px;cursor:pointer;border-radius:0 0 0 8px}
.dok-thumb.uploading img{opacity:.4}
</style>
</head>
<body>

<div class="header">
    <div style="font-size:1.8rem">📄</div>
    <h1>Generate Notula Monitoring Kinerja</h1>
</div>

<div class="main">

    <div id="env-alert"></div>

    <!-- ── LANGKAH 1: SUMBER DATA ── -->
    <div class="card">
        <h2><span class="step">1</span> Pilih Sumber Data</h2>

        <div class="sumber-tab">
            <button type="button" class="tab-btn aktif" id="tab-db" onclick="gantiSumber('db')">
                &#x1F5C4;&#xFE0F; Dari Data Aplikasi
            </button>
            <button type="button" class="tab-btn" id="tab-excel" onclick="gantiSumber('excel')">
                &#x1F4C4; Upload Excel
            </button>
        </div>

        <!-- MODE A: langsung dari database -->
        <div id="mode-db">
            <p class="hint">
                Mengambil langsung dari <b>Entry Capaian Kinerja</b> yang sudah diisi di aplikasi.
                Tidak perlu unduh atau unggah apa pun.
            </p>
            <div class="btn-row" style="margin-top:0">
                <div class="field" style="min-width:130px">
                    <label>Tahun</label>
                    <select id="db-tahun" onchange="gantiTahunDB()">
                        <?php foreach ($daftar_tahun as $th): ?>
                        <option value="<?= $th ?>" <?= $th === $tahun_awal ? 'selected' : '' ?>><?= $th ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field" style="min-width:170px">
                    <label>Triwulan</label>
                    <select id="db-periode" onchange="muatPreviewDB()"></select>
                </div>
                <button class="btn btn-ghost" onclick="muatPreviewDB()" style="align-self:end">
                    &#x1F504; Muat Ulang
                </button>
            </div>
        </div>

        <!-- MODE B: upload Excel -->
        <div id="mode-excel" style="display:none">
        <p class="hint">
            Pilih file <b>excel_FRA.xlsx</b> (sheet <code>LK_Kabkot</code>). Isinya langsung dibaca
            dan ditampilkan di bawah supaya bisa dicek dulu sebelum notula dibuat.
        </p>

        <div id="drop" class="drop">
            <div class="ic">📤</div>
            <div class="t">Seret file ke sini</div>
            <div class="s">atau</div>
            <button type="button" class="btn btn-primary" id="btn-pilih" style="margin-top:.75rem">
                📁 Pilih File Excel
            </button>
            <div class="s" style="margin-top:.6rem">Format .xlsx, maksimal 15 MB</div>
        </div>
        <input type="file" id="file-input" accept=".xlsx" style="display:none">

        <div id="file-info" style="display:none"></div>

        <div class="btn-row" id="opsi-baca" style="display:none">
            <div class="field" style="min-width:170px">
                <label>Sheet</label>
                <select id="sel-sheet" onchange="bacaUlang()"></select>
            </div>
            <div class="field" style="min-width:130px">
                <label>Triwulan</label>
                <select id="sel-tw" onchange="bacaUlang()">
                    <option value="I">Triwulan I</option>
                    <option value="II">Triwulan II</option>
                    <option value="III">Triwulan III</option>
                    <option value="IV">Triwulan IV</option>
                </select>
                <small>Menentukan kolom TW mana yang dibaca</small>
            </div>
            <div class="field" style="min-width:110px">
                <label>Tahun</label>
                <input id="m_tahun" value="<?= $default_periode['tahun'] ?? 2026 ?>">
            </div>
        </div>
        </div><!-- /mode-excel -->
    </div>

    <!-- ── LANGKAH 2: INFORMASI RAPAT ── -->
    <div class="card dim" id="card-meta">
        <h2><span class="step">2</span> Informasi Rapat</h2>
        <p class="hint">
            Bagian yang tidak ada di Excel. Disimpan per triwulan, jadi cukup diisi sekali.
        </p>
        <div class="grid">
            <div class="field"><label>Satuan Kerja</label><input id="m_satker"><small>Terisi otomatis dari Excel</small></div>
            <div class="field"><label>Nilai SAKIP</label><input id="m_nilai_sakip"></div>
            <div class="field"><label>Predikat</label><input id="m_predikat"></div>
            <div class="field"><label>Hari / Tanggal Rapat</label><input id="m_hari_tanggal" placeholder="Senin, 6 April 2026"></div>
            <div class="field"><label>Waktu</label><input id="m_waktu" placeholder="09.00 WITA — selesai"></div>
            <div class="field"><label>Tempat</label><input id="m_tempat" placeholder="Ruang Rapat BPS Kota Bima"></div>
            <div class="field"><label>Pimpinan Rapat</label><input id="m_pimpinan" placeholder="Kepala BPS Kota Bima"></div>
            <div class="field"><label>Nama Kepala Satker</label><input id="m_kepala_satker" placeholder="Nama lengkap + gelar"><small>Untuk blok tanda tangan</small></div>
            <div class="field"><label>Nama Notulis</label><input id="m_notulis"></div>
            <div class="field"><label>Kota (blok TTD)</label><input id="m_tempat_ttd" placeholder="Kota Bima"></div>
        </div>
        <div class="btn-row">
            <button class="btn btn-ghost" onclick="simpanMeta()">💾 Simpan Informasi Rapat</button>
            <span id="meta-status" style="font-size:.8rem;color:#aaa"></span>
        </div>
    </div>

    <!-- ── LANGKAH 3: DOKUMENTASI ── -->
    <div class="card dim" id="card-dok">
        <h2><span class="step">3</span> Dokumentasi Rapat</h2>
        <p class="hint">
            Foto-foto ini otomatis ditempel di halaman terakhir notula, satu foto per halaman
            baru. Tersimpan per triwulan, jadi cukup diupload sekali.
        </p>
        <div class="dok-galeri" id="dok-galeri"></div>
        <div class="btn-row" style="margin-top:.5rem">
            <button type="button" class="btn btn-ghost" onclick="document.getElementById('dok-input').click()">
                📷 Tambah Foto Dokumentasi
            </button>
            <input type="file" id="dok-input" accept="image/png,image/jpeg" multiple style="display:none"
                onchange="unggahDokumentasi(this.files)">
            <span id="dok-status" style="font-size:.82rem;color:#aaa"></span>
        </div>
    </div>

    <!-- ── LANGKAH 4: PREVIEW ── -->
    <div class="card dim" id="card-preview">
        <h2><span class="step">4</span> Cek Data Hasil Pembacaan</h2>
        <div id="preview-box">
            <p class="hint" style="margin:0">Upload file Excel dulu untuk melihat isinya.</p>
        </div>
    </div>

    <!-- ── LANGKAH 5: GENERATE ── -->
    <div class="card dim" id="card-gen">
        <h2><span class="step">5</span> Unduh Notula</h2>
        <p class="hint">
            Notula disusun dari template resmi <b>word_FRA.docx</b>. Kolom yang kosong di Excel
            dibiarkan kosong di dokumen supaya mudah dilengkapi manual di Word.
        </p>
        <div class="btn-row">
            <button class="btn btn-primary" id="btn-gen" onclick="generateNotula()">📄 Generate &amp; Unduh Notula (.docx)</button>
            <span id="gen-status" style="font-size:.82rem;color:#aaa"></span>
        </div>
    </div>

</div>

<div class="toast" id="toast"></div>

<script>
const API = '../../api/notula_api.php';
const PERIODE_ID = <?= (int)($default_periode['id'] ?? 0) ?>;

let SUMBER = 'db';                    // 'db' atau 'excel'
const PERIODE = <?= json_encode($per_tahun, JSON_UNESCAPED_UNICODE) ?>;
let TOKEN = null;
let HASIL = null;
let FILE_TERPILIH = null;
let ENV_OK = false;

function toast(msg, ok = true) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.style.background = ok ? '#27ae60' : '#e74c3c';
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 5000);
}

const fmtSize = b => b < 1024 * 1024
    ? (b / 1024).toFixed(0) + ' KB'
    : (b / 1024 / 1024).toFixed(1) + ' MB';

function aktifkan(id, on) {
    document.getElementById(id).classList.toggle('dim', !on);
}

// ── Cek lingkungan server ──────────────────────────────
async function cekEnv() {
    const box = document.getElementById('env-alert');
    try {
        const r = await fetch(`${API}?action=env_check&_=${Date.now()}`,
                              { cache: 'no-store' }).then(r => r.json());
        const masalah = [];
        if (!r.zip_ok)       masalah.push('Ekstensi PHP <code>zip</code> tidak aktif — aktifkan lewat hPanel → PHP Configuration');
        if (!r.dom_ok)       masalah.push('Ekstensi PHP <code>xml/dom</code> tidak aktif');
        if (!r.template_ok)  masalah.push('File <code>tools/notula/template/word_FRA.docx</code> belum ada');
        if (r.masalah_tmp)   masalah.push(r.masalah_tmp);

        ENV_OK = masalah.length === 0;

        let rincian = '';
        if (false) {
            rincian = `<div style="margin-top:.7rem">
                <b>Path Python yang sudah dicoba:</b>
                <table style="margin-top:.35rem;font-size:.8rem">
                    ${r.percobaan_python.map(x => `<tr>
                        <td style="padding:.15rem .6rem .15rem 0"><code>${x.path}</code></td>
                        <td style="padding:.15rem 0">${x.hasil}</td>
                    </tr>`).join('')}
                </table>
            </div>`;
        }

        box.innerHTML = ENV_OK
            ? `<div class="alert ok">✅ Lingkungan siap — mesin dokumen PHP ${r.php}, batas upload ${r.max_upload}</div>`
            : `<div class="alert err"><b>⚠️ Belum siap:</b><ul style="margin:.4rem 0 0 1.1rem">${masalah.map(m => `<li>${m}</li>`).join('')}</ul>${rincian}</div>`;
    } catch (e) {
        box.innerHTML = `<div class="alert err">Gagal mengecek lingkungan server.</div>`;
    }
}

// ── Dropzone ───────────────────────────────────────────
const drop = document.getElementById('drop');
const fileInput = document.getElementById('file-input');

drop.onclick = () => fileInput.click();
document.getElementById('btn-pilih').onclick = e => { e.stopPropagation(); fileInput.click(); };
drop.ondragover = e => { e.preventDefault(); drop.classList.add('over'); };
drop.ondragleave = () => drop.classList.remove('over');
drop.ondrop = e => {
    e.preventDefault();
    drop.classList.remove('over');
    if (e.dataTransfer.files.length) kirimFile(e.dataTransfer.files[0]);
};
fileInput.onchange = () => {
    if (fileInput.files.length) kirimFile(fileInput.files[0]);
    fileInput.value = '';   // supaya file yang sama bisa dipilih lagi
};

// ── Upload + parse ─────────────────────────────────────
async function kirimFile(file) {
    if (!ENV_OK) { toast('Perbaiki dulu masalah lingkungan di atas.', false); return; }
    if (!file.name.toLowerCase().endsWith('.xlsx')) {
        toast('Format harus .xlsx', false); return;
    }
    FILE_TERPILIH = file;
    await unggah(file, null, null);
}

async function bacaUlang() {
    if (!FILE_TERPILIH) return;
    await unggah(FILE_TERPILIH,
                 document.getElementById('sel-sheet').value,
                 document.getElementById('sel-tw').value);
}

async function unggah(file, sheet, tw) {
    const info = document.getElementById('file-info');
    drop.style.display = 'none';
    info.style.display = 'block';
    info.innerHTML = '<div class="loading"><div class="spinner"></div>Mengunggah &amp; membaca isi Excel...</div>';

    const fd = new FormData();
    fd.append('excel', file);
    if (sheet) fd.append('sheet', sheet);
    if (tw)    fd.append('triwulan', tw);

    try {
        const r = await fetch(`${API}?action=upload`, { method: 'POST', body: fd }).then(r => r.json());
        if (!r.success) throw new Error(r.message);

        TOKEN = r.token;
        HASIL = r;

        info.innerHTML = `
            <div class="filebox">
                <div class="ic">📗</div>
                <div style="flex:1">
                    <div class="nm">${r.nama_file}</div>
                    <div class="sz">${fmtSize(r.ukuran)} · sheet <b>${r.sheet}</b> · ${r.iku_list.length} IKU terbaca</div>
                </div>
                <button class="btn-link" onclick="gantiFile()">Ganti file</button>
            </div>`;

        // Isi opsi sheet & triwulan
        const selSheet = document.getElementById('sel-sheet');
        selSheet.innerHTML = r.sheets_tersedia.map(s =>
            `<option value="${s}" ${s === r.sheet ? 'selected' : ''}>${s}</option>`).join('');
        document.getElementById('sel-tw').value = r.triwulan;
        document.getElementById('opsi-baca').style.display = 'flex';

        // Isi field yang terbaca dari Excel
        if (r.satker)      document.getElementById('m_satker').value = r.satker;
        if (r.nilai_sakip) document.getElementById('m_nilai_sakip').value = r.nilai_sakip;
        if (r.predikat)    document.getElementById('m_predikat').value = r.predikat;

        aktifkan('card-meta', true);
        aktifkan('card-dok', true);
        aktifkan('card-preview', true);
        aktifkan('card-gen', true);
        renderPreview(r);
        muatDokumentasi();

        if (r.peringatan && r.peringatan.length) {
            toast(r.peringatan.join(' '), false);
        }
    } catch (e) {
        info.style.display = 'none';
        drop.style.display = 'block';
        toast(e.message || 'Upload gagal', false);
    }
}

function gantiFile() {
    if (TOKEN) fetch(`${API}?action=discard&token=${TOKEN}`);
    TOKEN = null; HASIL = null; FILE_TERPILIH = null;
    fileInput.value = '';
    document.getElementById('file-info').style.display = 'none';
    document.getElementById('opsi-baca').style.display = 'none';
    drop.style.display = 'block';
    aktifkan('card-meta', false);
    aktifkan('card-dok', false);
    aktifkan('card-preview', false);
    aktifkan('card-gen', false);
    document.getElementById('preview-box').innerHTML =
        '<p class="hint" style="margin:0">Upload file Excel dulu untuk melihat isinya.</p>';
}

// ── Preview tabel ──────────────────────────────────────
function statusIKU(i) {
    const angka  = i.real_tw !== '' && i.real_tw !== null;
    const narasi = (i.kendala || '').trim() !== '' || (i.rtl || '').trim() !== '';
    return (angka && narasi) ? 'lengkap' : (angka || narasi) ? 'sebagian' : 'kosong';
}

function renderPreview(r) {
    const c = { lengkap: 0, sebagian: 0, kosong: 0 };
    r.iku_list.forEach(i => c[statusIKU(i)]++);

    let html = `
    <div class="stats">
        <div class="stat b"><div class="n">${r.iku_list.length}</div><div class="l">IKU Terbaca</div></div>
        <div class="stat g"><div class="n">${c.lengkap}</div><div class="l">Lengkap</div></div>
        <div class="stat y"><div class="n">${c.sebagian}</div><div class="l">Sebagian</div></div>
        <div class="stat r"><div class="n">${c.kosong}</div><div class="l">Kosong</div></div>
    </div>`;

    if (r.iku_list.length === 0) {
        html += `<div class="alert err">Tidak ada baris IKU yang terbaca. Cek apakah sheet yang dipilih benar,
                 kolom D berisi kode IKU, dan kolom H berisi teks <code>IKU</code>.</div>`;
    } else if (c.kosong + c.sebagian > 0) {
        html += `<div class="alert warn">
            <b>${c.kosong + c.sebagian} IKU</b> datanya belum lengkap di Excel. Notula tetap bisa dibuat,
            bagian tersebut akan kosong di dokumen.</div>`;
    }

    if (r.iku_list.length) {
        html += `<div style="overflow-x:auto"><table>
            <thead><tr>
                <th>Kode</th><th>Indikator</th>
                <th style="text-align:right">Target PK</th>
                <th style="text-align:right">Target TW</th>
                <th style="text-align:right">Realisasi</th>
                <th style="text-align:right">Cap. TW</th>
                <th style="text-align:right">Cap. PK</th>
                <th>Status</th>
            </tr></thead><tbody>`;

        r.iku_list.forEach(i => {
            const st = statusIKU(i);
            const lbl = { lengkap: '✅ Lengkap', sebagian: '⚠️ Sebagian', kosong: '⬜ Kosong' }[st];
            html += `<tr>
                <td class="kode">${i.kode}</td>
                <td>${i.nama}</td>
                <td class="num">${i.target_pk} ${i.satuan}</td>
                <td class="num">${i.alokasi_tw || '—'}</td>
                <td class="num">${i.real_tw || '—'}</td>
                <td class="num">${i.capaian_tw || '—'}</td>
                <td class="num">${i.capaian_pk || '—'}</td>
                <td><span class="pill ${st}">${lbl}</span></td>
            </tr>`;
        });
        html += '</tbody></table></div>';
    }

    document.getElementById('preview-box').innerHTML = html;
}

// ── Metadata ───────────────────────────────────────────
const FIELD_META = ['satker','nilai_sakip','predikat','hari_tanggal','waktu',
                    'tempat','pimpinan','kepala_satker','notulis','tempat_ttd'];

function ambilForm() {
    const o = {};
    FIELD_META.forEach(k => o[k] = document.getElementById('m_' + k).value);
    o.tahun    = document.getElementById('m_tahun').value;
    o.triwulan = document.getElementById('sel-tw').value;
    o.sheet    = document.getElementById('sel-sheet').value;
    return o;
}

async function muatMeta() {
    if (!PERIODE_ID) return;
    try {
        const r = await fetch(`${API}?action=meta_get&periode_id=${PERIODE_ID}`).then(r => r.json());
        if (!r.success) return;
        FIELD_META.forEach(k => {
            const el = document.getElementById('m_' + k);
            if (el && !el.value) el.value = r.data[k] || '';
        });
    } catch (e) { /* diamkan */ }
}

async function simpanMeta() {
    if (!PERIODE_ID) { toast('Tidak ada periode di database untuk menyimpan.', false); return; }
    const st = document.getElementById('meta-status');
    st.textContent = '⏳ Menyimpan...';
    const r = await fetch(`${API}?action=meta_save`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(Object.assign({ periode_id: PERIODE_ID }, ambilForm()))
    }).then(r => r.json());

    st.textContent = r.success ? '✅ Tersimpan' : '❌ Gagal';
    toast(r.success ? 'Informasi rapat tersimpan' : (r.message || 'Gagal menyimpan'), !!r.success);
    setTimeout(() => st.textContent = '', 3000);
}

// ── Generate & unduh ───────────────────────────────────
// ── Pemilih sumber ─────────────────────────────────────
function gantiSumber(mode) {
    SUMBER = mode;
    document.getElementById('tab-db').classList.toggle('aktif', mode === 'db');
    document.getElementById('tab-excel').classList.toggle('aktif', mode === 'excel');
    document.getElementById('mode-db').style.display    = mode === 'db' ? 'block' : 'none';
    document.getElementById('mode-excel').style.display = mode === 'excel' ? 'block' : 'none';

    if (mode === 'db') {
        aktifkan('card-meta', true);
        aktifkan('card-dok', true);
        aktifkan('card-preview', true);
        aktifkan('card-gen', true);
        muatPreviewDB();
        muatDokumentasi();
    } else {
        const siap = !!TOKEN;
        aktifkan('card-meta', siap);
        aktifkan('card-dok', siap);
        aktifkan('card-preview', siap);
        aktifkan('card-gen', siap);
        if (HASIL) renderPreview(HASIL);
        if (siap) muatDokumentasi();
    }
}

function isiPeriodeDB() {
    const th  = document.getElementById('db-tahun').value;
    const sel = document.getElementById('db-periode');
    const daftar = PERIODE[th] || [];
    sel.innerHTML = daftar.map(t =>
        `<option value="${t.id}">Triwulan ${t.tw}${t.status === 'open' ? ' (aktif)' : ''}</option>`
    ).join('');
    const aktif = daftar.find(t => t.status === 'open');
    if (aktif) sel.value = aktif.id;
    sel.disabled = daftar.length === 0;
}

function gantiTahunDB() {
    const sel = document.getElementById('db-periode');
    const lama = sel.selectedIndex >= 0
        ? (sel.options[sel.selectedIndex].text.match(/Triwulan (\w+)/) || [])[1] : null;
    isiPeriodeDB();
    if (lama) {
        const th = document.getElementById('db-tahun').value;
        const cocok = (PERIODE[th] || []).find(t => t.tw === lama);
        if (cocok) sel.value = cocok.id;
    }
    muatPreviewDB();
}

const periodeDB = () => parseInt(document.getElementById('db-periode').value);

async function muatPreviewDB() {
    if (SUMBER !== 'db') return;
    const pid = periodeDB();
    if (!pid) return;

    muatDokumentasi();
    document.getElementById('preview-box').innerHTML =
        '<div class="loading"><div class="spinner"></div>Memuat data dari aplikasi...</div>';
    try {
        const r = await fetch(`${API}?action=generate_db&preview=1&periode_id=${pid}&_=${Date.now()}`,
                              { cache: 'no-store' }).then(r => r.json());
        if (!r.success) throw new Error(r.message);

        // samakan bentuknya dengan hasil mode Excel supaya renderPreview bisa dipakai ulang
        HASIL = { iku_list: r.iku_list, sumber: 'db' };
        renderPreview(HASIL);

        ['satker','nilai_sakip','predikat','hari_tanggal','waktu','tempat',
         'pimpinan','kepala_satker','notulis','tempat_ttd'].forEach(k => {
            const el = document.getElementById('m_' + k);
            if (el && r.meta && r.meta[k]) el.value = r.meta[k];
        });
        document.getElementById('m_tahun').value = r.periode.tahun;
        document.getElementById('sel-tw').value  = r.periode.triwulan;

        if (r.terisi === 0) {
            document.getElementById('preview-box').insertAdjacentHTML('afterbegin',
                `<div class="alert warn">Belum ada data Entry Capaian Kinerja untuk periode ini.
                 Notula tetap bisa dibuat, tapi bagian capaian akan kosong. Isi dulu lewat menu
                 <b>Entry Capaian Kinerja</b>, atau pakai tab <b>Upload Excel</b>.</div>`);
        }
    } catch (e) {
        document.getElementById('preview-box').innerHTML =
            `<div class="alert err">${e.message}</div>`;
    }
}

async function generateNotula() {
    if (SUMBER === 'excel' && !TOKEN) { toast('Upload file Excel dulu.', false); return; }

    const btn = document.getElementById('btn-gen');
    const st  = document.getElementById('gen-status');
    btn.disabled = true;
    st.textContent = '⏳ Menyusun dokumen...';

    try {
        const fd = new FormData();
        Object.entries(ambilForm()).forEach(([k, v]) => fd.append(k, v));

        let endpoint;
        if (SUMBER === 'db') {
            endpoint = `${API}?action=generate_db&periode_id=${periodeDB()}`;
        } else {
            if (!TOKEN) throw new Error('Upload file Excel dulu.');
            fd.append('token', TOKEN);
            endpoint = `${API}?action=generate`;
        }

        const res = await fetch(endpoint, { method: 'POST', body: fd });
        const ct  = res.headers.get('Content-Type') || '';

        if (ct.includes('application/json')) {
            const err = await res.json();
            throw new Error(err.message || 'Gagal generate');
        }

        const blob = await res.blob();
        const disp = res.headers.get('Content-Disposition') || '';
        const nama = (disp.match(/filename="(.+?)"/) || [])[1] || 'Notula.docx';

        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url; a.download = nama;
        document.body.appendChild(a); a.click(); a.remove();
        URL.revokeObjectURL(url);

        let extra = '';
        try {
            const info = JSON.parse(res.headers.get('X-Notula-Info') || '{}');
            if (info.iku_tanpa_tabel && info.iku_tanpa_tabel.length) {
                extra = ' — IKU tanpa tabel di template: ' + info.iku_tanpa_tabel.join(', ');
            }
            if (info.dok_terpasang) extra += ` — ${info.dok_terpasang} foto dokumentasi ditempel`;
        } catch (e) { /* header opsional */ }

        st.textContent = '✅ Selesai';
        toast('Notula berhasil dibuat: ' + nama + extra, !extra);
    } catch (e) {
        st.textContent = '❌ Gagal';
        toast(e.message, false);
    } finally {
        btn.disabled = false;
        setTimeout(() => st.textContent = '', 6000);
    }
}

// ── Dokumentasi rapat (foto di halaman terakhir notula) ──
function periodeIdAktif() {
    if (SUMBER === 'db') return periodeDB() || null;
    const th = document.getElementById('m_tahun').value;
    const tw = document.getElementById('sel-tw').value;
    const daftar = PERIODE[th] || [];
    const cocok = daftar.find(p => p.tw === tw);
    return cocok ? cocok.id : null;
}

function renderGaleriDokumentasi(daftar) {
    const el = document.getElementById('dok-galeri');
    if (!el) return;
    el.innerHTML = (daftar || []).map(f => `
        <div class="dok-thumb" data-dok-id="${f.id}">
            <img src="../../${f.file_path}" onclick="window.open('../../${f.file_path}','_blank')"
                 alt="${(f.keterangan || f.original_name || '').replace(/"/g,'')}">
            <button class="hapus" title="Hapus foto" onclick="hapusDokumentasi(${f.id})">✕</button>
        </div>`).join('');
}

async function muatDokumentasi() {
    const pid = periodeIdAktif();
    if (!pid) { renderGaleriDokumentasi([]); return; }
    try {
        const r = await fetch(`${API}?action=dok_list&periode_id=${pid}`).then(r => r.json());
        if (r.success) renderGaleriDokumentasi(r.data);
    } catch (e) { /* galeri kosong, biarkan */ }
}

async function unggahDokumentasi(files) {
    if (!files || !files.length) return;
    const pid = periodeIdAktif();
    if (!pid) { toast('Pilih triwulan dulu sebelum upload dokumentasi.', false); return; }

    const galeri = document.getElementById('dok-galeri');
    for (const file of files) {
        if (!['image/png', 'image/jpeg'].includes(file.type)) {
            toast(`${file.name}: hanya PNG/JPG yang diterima`, false);
            continue;
        }
        const placeholder = document.createElement('div');
        placeholder.className = 'dok-thumb uploading';
        placeholder.innerHTML = '<img src="' + URL.createObjectURL(file) + '">';
        galeri.appendChild(placeholder);

        const fd = new FormData();
        fd.append('periode_id', pid);
        fd.append('file', file);

        try {
            const r = await fetch(`${API}?action=dok_upload`, { method: 'POST', body: fd }).then(r => r.json());
            if (!r.success) throw new Error(r.message || 'Gagal upload');
            placeholder.dataset.dokId = r.id;
            placeholder.classList.remove('uploading');
            placeholder.innerHTML += `<button class="hapus" title="Hapus foto" onclick="hapusDokumentasi(${r.id})">✕</button>`;
        } catch (e) {
            placeholder.remove();
            toast(`Gagal upload ${file.name}: ${e.message}`, false);
        }
    }
    document.getElementById('dok-input').value = '';
}

async function hapusDokumentasi(id) {
    if (!confirm('Hapus foto dokumentasi ini?')) return;
    try {
        const r = await fetch(`${API}?action=dok_delete`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        }).then(r => r.json());
        if (!r.success) throw new Error(r.message);
        document.querySelector(`.dok-thumb[data-dok-id="${id}"]`)?.remove();
    } catch (e) {
        toast('Gagal menghapus foto: ' + e.message, false);
    }
}

// Init
cekEnv();
isiPeriodeDB();
gantiSumber('db');
muatMeta();
</script>
</body>
</html>
