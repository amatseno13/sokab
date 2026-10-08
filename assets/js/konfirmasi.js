// Dialog bergaya aplikasi:
//   await konfirmasiHapus('judul', 'keterangan') → true/false
//   await pilihFormat()                          → 'word' | 'pdf' | null (batal)
function khStyle() {
    if (!document.getElementById('kh-style')) {
        document.head.insertAdjacentHTML('beforeend', `<style id="kh-style">
            .kh-back{position:fixed;inset:0;background:rgba(15,23,42,.45);display:flex;align-items:center;justify-content:center;z-index:10000;padding:1rem}
            .kh-box{background:#fff;border-radius:14px;max-width:380px;width:100%;padding:1.4rem 1.5rem;box-shadow:0 20px 50px -10px rgba(0,0,0,.35);font-family:inherit}
            .kh-box h4{margin:0 0 .4rem;font-size:1rem;color:#1f2937}
            .kh-box p{margin:0 0 1.1rem;font-size:.84rem;color:#6b7280;line-height:1.5}
            .kh-aksi{display:flex;gap:.6rem;justify-content:flex-end}
            .kh-aksi button{border:none;border-radius:8px;padding:.5rem 1.1rem;font-weight:600;font-size:.84rem;cursor:pointer;font-family:inherit}
            .kh-batal{background:#eef0f3;color:#374151}.kh-hapus{background:#e74c3c;color:#fff}
            .kh-format{display:flex;gap:.7rem;margin-bottom:1rem}
            .kh-format button{flex:1;border:1.5px solid #e5e7eb;background:#fff;border-radius:10px;padding:.9rem .5rem;font-size:.88rem;font-weight:600;cursor:pointer;font-family:inherit;color:#1f2937;transition:.15s}
            .kh-format button:hover{border-color:#e67e22;background:#fff7ed}
        </style>`);
    }
}

function konfirmasiHapus(judul, keterangan = '') {
    khStyle();
    return new Promise(selesai => {
        const el = document.createElement('div');
        el.className = 'kh-back';
        el.innerHTML = `<div class="kh-box" role="alertdialog" aria-modal="true">
            <h4></h4><p></p>
            <div class="kh-aksi"><button type="button" class="kh-batal">Batal</button><button type="button" class="kh-hapus">Hapus</button></div></div>`;
        el.querySelector('h4').textContent = judul;
        el.querySelector('p').textContent = keterangan;
        const tutup = ok => { document.removeEventListener('keydown', tombol); el.remove(); selesai(ok); };
        const tombol = e => { if (e.key === 'Escape') tutup(false); };
        el.querySelector('.kh-batal').onclick = () => tutup(false);
        el.querySelector('.kh-hapus').onclick = () => tutup(true);
        el.onclick = e => { if (e.target === el) tutup(false); };
        document.addEventListener('keydown', tombol);
        document.body.appendChild(el);
        el.querySelector('.kh-batal').focus();   // default aman: Batal
    });
}

function pilihFormat() {
    khStyle();
    return new Promise(selesai => {
        const el = document.createElement('div');
        el.className = 'kh-back';
        el.innerHTML = `<div class="kh-box" role="dialog" aria-modal="true">
            <h4>Pilih format dokumen</h4><p>Dokumen akan dibuat dalam format yang Anda pilih.</p>
            <div class="kh-format">
                <button type="button" data-f="word">📝<br>Word (.docx)</button>
                <button type="button" data-f="pdf">📕<br>PDF</button>
            </div>
            <div class="kh-aksi"><button type="button" class="kh-batal">Batal</button></div></div>`;
        const tutup = f => { document.removeEventListener('keydown', tombol); el.remove(); selesai(f); };
        const tombol = e => { if (e.key === 'Escape') tutup(null); };
        el.querySelectorAll('[data-f]').forEach(b => b.onclick = () => tutup(b.dataset.f));
        el.querySelector('.kh-batal').onclick = () => tutup(null);
        el.onclick = e => { if (e.target === el) tutup(null); };
        document.addEventListener('keydown', tombol);
        document.body.appendChild(el);
    });
}
