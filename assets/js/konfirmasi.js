// Dialog konfirmasi hapus bergaya aplikasi: await konfirmasiHapus('judul', 'keterangan') → true/false
function konfirmasiHapus(judul, keterangan = '') {
    if (!document.getElementById('kh-style')) {
        document.head.insertAdjacentHTML('beforeend', `<style id="kh-style">
            .kh-back{position:fixed;inset:0;background:rgba(15,23,42,.45);display:flex;align-items:center;justify-content:center;z-index:10000;padding:1rem}
            .kh-box{background:#fff;border-radius:14px;max-width:380px;width:100%;padding:1.4rem 1.5rem;box-shadow:0 20px 50px -10px rgba(0,0,0,.35);font-family:inherit}
            .kh-box h4{margin:0 0 .4rem;font-size:1rem;color:#1f2937}
            .kh-box p{margin:0 0 1.1rem;font-size:.84rem;color:#6b7280;line-height:1.5}
            .kh-aksi{display:flex;gap:.6rem;justify-content:flex-end}
            .kh-aksi button{border:none;border-radius:8px;padding:.5rem 1.1rem;font-weight:600;font-size:.84rem;cursor:pointer;font-family:inherit}
            .kh-batal{background:#eef0f3;color:#374151}.kh-hapus{background:#e74c3c;color:#fff}
        </style>`);
    }
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
