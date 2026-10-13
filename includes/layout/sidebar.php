<?php /* Menu samping. Tambah menu baru cukup di sini + 1 file di pages/dashboard/ */ ?>
        <aside class="sidebar">
            <button type="button" class="sidebar-toggle" onclick="toggleSidebar()" title="Perkecil/perbesar menu">‹</button>
            <div class="sidebar-header">
                <h2>Menu Dokumen</h2>
                <p>BPS Kota Bima</p>
            </div>

            <div class="menu-list">

                <!-- BERANDA -->
                <div class="menu-item">
                    <div class="menu-link active" onclick="showHome()">
                        <div class="menu-icon"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8" /><path d="M3 10a2 2 0 0 1 .709-1.528l7-5.999a2 2 0 0 1 2.582 0l7 5.999A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" /></svg></div>
                        <div class="menu-text"><h3>Beranda</h3><p>Halaman Utama</p></div>
                    </div>
                </div>

                <!-- PERENCANAAN (header) -->
                <div class="menu-section-header">Perencanaan</div>
                <div class="menu-item menu-sub">
                    <div class="menu-link" onclick="showPage('renstra')">
                        <div class="menu-icon"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m16.24 7.76-1.804 5.411a2 2 0 0 1-1.265 1.265L7.76 16.24l1.804-5.411a2 2 0 0 1 1.265-1.265z" /><circle cx="12" cy="12" r="10" /></svg></div>
                        <div class="menu-text"><h3>Renstra</h3><p>Rencana Strategis</p></div>
                    </div>
                </div>
                <div class="menu-item menu-sub">
                    <div class="menu-link" onclick="showPage('perjanjian-kinerja')">
                        <div class="menu-icon"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m18 5-2.414-2.414A2 2 0 0 0 14.172 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2" /><path d="M21.378 12.626a1 1 0 0 0-3.004-3.004l-4.01 4.012a2 2 0 0 0-.506.854l-.837 2.87a.5.5 0 0 0 .62.62l2.87-.837a2 2 0 0 0 .854-.506z" /><path d="M8 18h1" /></svg></div>
                        <div class="menu-text"><h3>Perjanjian Kinerja</h3><p>Dokumen Komitmen</p></div>
                    </div>
                </div>

                <!-- PENGUKURAN (header) -->
                <div class="menu-section-header">Pengukuran</div>
                <div class="menu-item menu-sub">
                    <div class="menu-link" onclick="showPage('monitoring-kinerja')">
                        <div class="menu-icon"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 14 4-4" /><path d="M3.34 19a10 10 0 1 1 17.32 0" /></svg></div>
                        <div class="menu-text"><h3>Monitoring Capaian Kinerja</h3><p>Triwulanan</p></div>
                    </div>
                </div>
                <div class="menu-item menu-sub">
                    <div class="menu-link" onclick="showPage('monitoring-renstra')">
                        <div class="menu-icon"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z" /><line x1="4" x2="4" y1="22" y2="15" /></svg></div>
                        <div class="menu-text"><h3>Monitoring Capaian Renstra</h3><p>Capaian Akhir Renstra</p></div>
                    </div>
                </div>



                <!-- ENTRY CAPAIAN KINERJA -->
                <div class="menu-item menu-sub">
                    <div class="menu-link" onclick="showPage('capaian-kinerja')">
                        <div class="menu-icon"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" /><path d="M18.375 2.625a1 1 0 0 1 3 3l-9.013 9.014a2 2 0 0 1-.853.505l-2.873.84a.5.5 0 0 1-.62-.62l.84-2.873a2 2 0 0 1 .506-.852z" /></svg></div>
                        <div class="menu-text"><h3>Entry Capaian Kinerja</h3><p>Input IKU per Triwulan</p></div>
                    </div>
                </div>

                <!-- GENERATE NOTULA -->
                <div class="menu-item menu-sub">
                    <div class="menu-link" onclick="showPage('notula-kinerja')">
                        <div class="menu-icon"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2v4a2 2 0 0 0 2 2h4" /><path d="M4 7V4a2 2 0 0 1 2-2 2 2 0 0 0-2 2" /><path d="M4.063 20.999a2 2 0 0 0 2 1L18 22a2 2 0 0 0 2-2V7l-5-5H6" /><path d="m5 11-3 3" /><path d="m5 17-3-3h10" /></svg></div>
                        <div class="menu-text"><h3>Generate Notula</h3><p>Notula Monitoring Kinerja</p></div>
                    </div>
                </div>

                <!-- PELAPORAN (header) -->
                <div class="menu-section-header">Pelaporan</div>
                <div class="menu-item menu-sub">
                    <div class="menu-link has-dropdown" onclick="toggleSubmenu(this)">
                        <div class="menu-icon"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z" /><path d="M14 2v4a2 2 0 0 0 2 2h4" /><path d="M8 18v-1" /><path d="M12 18v-6" /><path d="M16 18v-3" /></svg></div>
                        <div class="menu-text"><h3>LAKIN</h3><p>Laporan Akuntabilitas</p></div>
                        <span class="menu-arrow">▶</span>
                    </div>
                    <div class="submenu">
                        <div class="submenu-item" onclick="showPage('lakin-draft')">Draft</div>
                        <div class="submenu-item" onclick="showPage('lakin-final')">Final</div>
                    </div>
                </div>

                <!-- EVALUASI (header) -->
                <div class="menu-section-header">Evaluasi</div>
                <div class="menu-item menu-sub">
                    <div class="menu-link" onclick="showPage('permindok')">
                        <div class="menu-icon"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 9V5a2 2 0 0 1 2-2h3.9a2 2 0 0 1 1.69.9l.81 1.2a2 2 0 0 0 1.67.9H20a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-1" /><path d="M2 13h10" /><path d="m9 16 3-3-3-3" /></svg></div>
                        <div class="menu-text"><h3>Permintaan Dokumen</h3><p>Permindok by Tahun</p></div>
                    </div>
                </div>
                <!-- -->

                <!-- APLIKASI MONITORING (header) -->
                <div class="menu-section-header">Aplikasi Monitoring</div>
                <div class="menu-item menu-sub">
                    <a href="https://esr.menpan.go.id" target="_blank" class="menu-link">
                        <div class="menu-icon"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z" /><path d="m9 12 2 2 4-4" /></svg></div>
                        <div class="menu-text"><h3>ESR</h3><p>Evaluasi Sistem Review</p></div>
                    </a>
                </div>
                <div class="menu-item menu-sub">
                    <a href="https://sinergi.web.bps.go.id" target="_blank" class="menu-link">
                        <div class="menu-icon"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m11 17 2 2a1 1 0 1 0 3-3" /><path d="m14 14 2.5 2.5a1 1 0 1 0 3-3l-3.88-3.88a3 3 0 0 0-4.24 0l-.88.88a1 1 0 1 1-3-3l2.81-2.81a5.79 5.79 0 0 1 7.06-.87l.47.28a2 2 0 0 0 1.42.25L21 4" /><path d="m21 3 1 11h-2" /><path d="M3 3 2 14l6.5 6.5a1 1 0 1 0 3-3" /><path d="M3 4h8" /></svg></div>
                        <div class="menu-text"><h3>SINERGI</h3><p>Kolaborasi Unit</p></div>
                    </a>
                </div>

                <!-- MATERI & PANDUAN -->
                <div class="menu-section-header">Referensi</div>
                <div class="menu-item menu-sub">
                    <div class="menu-link" onclick="showPage('materi-panduan')">
                        <div class="menu-icon"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 7v14" /><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z" /></svg></div>
                        <div class="menu-text"><h3>Materi &amp; Panduan</h3><p>Referensi SAKIP</p></div>
                    </div>
                </div>

            </div>

            <?php if ($user_role === 'admin'): ?>
                <!-- ADMIN (header) -->
                <div class="menu-section-header">Admin</div>
                <div class="menu-item menu-sub">
                    <div class="menu-link" onclick="showPage('kelola-user')">
                        <div class="menu-icon"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M22 21v-2a4 4 0 0 0-3-3.87" /><path d="M16 3.13a4 4 0 0 1 0 7.75" /></svg></div>
                        <div class="menu-text"><h3>Kelola User</h3><p>Manajemen Pengguna</p></div>
                    </div>
                </div>
                <?php endif; ?>

            <div class="sidebar-footer">
                <div class="logo-footer"><img src="assets/images/logo-bps.png" alt="BPS" style="width:32px;height:32px;object-fit:contain;"></div>
                
                

                <div class="copyright">
                    <strong>BPS Kota Bima</strong><br>
                    SOKAB v1.0 · © 2026
                </div>
            </div>
        </aside>
