# Patch `dashboard.php` — Menu "Generate Notula"

Tiga sisipan di `sokab/dashboard.php`. Semuanya **tambahan**, tidak ada baris lama yang dihapus.

---

## 1. Item menu sidebar

**Cari blok ini** (sekitar baris 1345–1352):

```html
<!-- ENTRY CAPAIAN KINERJA -->
<div class="menu-item menu-sub">
    <div class="menu-link" onclick="showPage('capaian-kinerja')">
        <div class="menu-icon">&#x270D;&#xFE0F;</div>
        <div class="menu-text"><h3>Entry Capaian Kinerja</h3><p>Input IKU per Triwulan</p></div>
    </div>
</div>
```

**Sisipkan tepat setelahnya:**

```html
<!-- GENERATE NOTULA -->
<div class="menu-item menu-sub">
    <div class="menu-link" onclick="showPage('notula-kinerja')">
        <div class="menu-icon">&#x1F4C4;</div>
        <div class="menu-text"><h3>Generate Notula</h3><p>Notula Monitoring Kinerja</p></div>
    </div>
</div>
```

---

## 2. Container halaman

**Cari blok ini** (sekitar baris 1593–1606):

```html
<!-- PAGE: ENTRY CAPAIAN KINERJA -->
<div id="page-capaian-kinerja" class="content-page">
    ...
    <div id="content-capaian-kinerja" style="padding:0">
        <div class="loading-state">Memuat...</div>
    </div>
</div>
```

**Sisipkan tepat setelah `</div>` penutupnya:**

```html
<!-- PAGE: GENERATE NOTULA -->
<div id="page-notula-kinerja" class="content-page">
    <div class="page-header-bar">
        <div class="page-header-icon" style="background:#fde8d8">&#x1F4C4;</div>
        <div>
            <h2 class="page-title">Generate Notula Monitoring Kinerja</h2>
            <p class="page-subtitle">Susun notula triwulanan otomatis dari data Capaian Kinerja</p>
        </div>
    </div>
    <div id="content-notula-kinerja" style="padding:0">
        <div class="loading-state">Memuat...</div>
    </div>
</div>
```

---

## 3. Loader JavaScript

**Cari baris ini** (sekitar baris 2408):

```js
if (pageId === 'capaian-kinerja') loadCapaianKinerja();
```

**Sisipkan tepat di bawahnya:**

```js
if (pageId === 'notula-kinerja') loadNotulaKinerja();
```

Lalu **cari fungsi `loadCapaianKinerja()`** dan sisipkan fungsi berikut tepat setelahnya:

```js
// ── GENERATE NOTULA LOADER ──────────────────────
function loadNotulaKinerja() {
    var container = document.getElementById('content-notula-kinerja');
    if (container.querySelector('iframe')) return;   // sudah dimuat
    container.innerHTML = '<div class="loading-state">Memuat...</div>';
    var iframe = document.createElement('iframe');
    iframe.src = 'pages/capaian/notula.php';
    iframe.style.cssText = 'width:100%;height:calc(100vh - 280px);border:none;';
    iframe.onload = function() {
        container.innerHTML = '';
        container.appendChild(iframe);
    };
    iframe.onerror = function() {
        container.innerHTML = '<div class="loading-state">Gagal memuat halaman Generate Notula</div>';
    };
    container.appendChild(iframe);
}
```

---

## ⚠️ Catatan penamaan file

Di `dashboard.php` yang sekarang, `loadCapaianKinerja()` memanggil
`pages/capaian/capaian_index.php`, sedangkan di ZIP yang dikirim file-nya bernama
`pages/capaian/index.php`. Begitu juga `index.php` memanggil `capaian_entry.php`
padahal file-nya `entry.php`.

Kalau di server aslinya file-file itu memang bernama `capaian_index.php` /
`capaian_entry.php`, ganti juga nama file baru ini menjadi
`pages/capaian/capaian_notula.php` dan sesuaikan `iframe.src` di atas — supaya
polanya konsisten. Kalau tidak, di deployment yang sekarang menu Entry Capaian
Kinerja seharusnya sudah error 404.
