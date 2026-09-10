<?php
/**
 * SOKAB — Konfigurasi tampilan dashboard
 *
 * Semua daftar yang menentukan "apa saja yang dimuat" ada di sini, terpisah dari
 * kode. Untuk menambah/menghapus menu, modal, atau file JS, cukup sunting file ini.
 *
 * Catatan: ini konfigurasi TAMPILAN. Kredensial database tetap di
 * config/database.php dan tidak ada hubungannya dengan file ini.
 */

return [

    /**
     * Halaman dashboard, sesuai urutan tampil.
     *
     * Setiap nama harus punya file di pages/dashboard/<nama>.php yang berisi:
     *     <div id="page-<nama>" class="content-page"> ... </div>
     *
     * Nama yang sama dipakai di sidebar: onclick="showPage('<nama>')"
     *
     * Menambah menu: buat filenya, tambahkan namanya di sini, lalu tambahkan
     * tombolnya di includes/layout/sidebar.php. Tiga langkah, tidak lebih.
     *
     * Menonaktifkan menu sementara: beri // di depan barisnya. Filenya tetap
     * aman, hanya tidak dimuat.
     */
    'halaman' => [
        'home',
        'renstra',
        'perjanjian-kinerja',
        'monitoring-kinerja',
        'monitoring-renstra',
        'notula-kinerja',
        'capaian-kinerja',
        'permindok',
        'lakin-draft',
        'lakin-final',
        'evaluasi-permindok',
        'evaluasi-tahun',
        'materi-panduan',
        'kelola-user',      // isinya sendiri sudah dibatasi untuk admin
    ],

    /**
     * Modal (jendela popup) yang dimuat di akhir halaman.
     * Setiap nama = includes/modals/<nama>.php
     */
    'modal' => [
        'jadwal',
        'user',
        'dokumen',
        'ikss',
        'permindok',
    ],

    /**
     * Berkas JavaScript, dimuat sesuai urutan di bawah.
     * Setiap nama = assets/js/<nama>.js
     *
     * URUTAN PENTING: core.js harus lebih dulu karena berisi showPage dan
     * pemuat konten yang dipakai file lain.
     */
    'js' => [
        'core',                 // navigasi: showPage, submenu, pemuat konten
        'jadwal',               // kalender & jadwal SAKIP
        'dokumen',              // dokumen Drive/tautan + iframe Capaian Kinerja
        'lakin',                // LAKIN draft & final
        'util',                 // pembantu kecil (closeModal)
        'ikss_functions',       // Monitoring Capaian Kinerja
        'permindok_functions',  // Permintaan Dokumen
        'notula_frame',         // iframe Generate Notula
    ],

    /**
     * Berkas JavaScript khusus admin. Pengguna biasa tidak mengunduhnya.
     */
    'js_admin' => [
        'user_management',
    ],

    /**
     * Pustaka luar. Dipisah supaya gampang diganti versinya, atau diunduh
     * ke server sendiri kalau nanti perlu jalan tanpa internet.
     */
    'cdn' => [
        'css' => [
            'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap',
            'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css',
        ],
        'js' => [
            'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js',
        ],
    ],

];
