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
                        <div class="menu-icon">🏠</div>
                        <div class="menu-text"><h3>Beranda</h3><p>Halaman Utama</p></div>
                    </div>
                </div>

                <!-- PERENCANAAN (header) -->
                <div class="menu-section-header">📋 Perencanaan</div>
                <div class="menu-item menu-sub">
                    <div class="menu-link" onclick="showPage('renstra')">
                        <div class="menu-icon">🌱</div>
                        <div class="menu-text"><h3>Renstra</h3><p>Rencana Strategis</p></div>
                    </div>
                </div>
                <div class="menu-item menu-sub">
                    <div class="menu-link" onclick="showPage('perjanjian-kinerja')">
                        <div class="menu-icon">📝</div>
                        <div class="menu-text"><h3>Perjanjian Kinerja</h3><p>Dokumen Komitmen</p></div>
                    </div>
                </div>

                <!-- PENGUKURAN (header) -->
                <div class="menu-section-header">📊 Pengukuran</div>
                <div class="menu-item menu-sub">
                    <div class="menu-link" onclick="showPage('monitoring-kinerja')">
                        <div class="menu-icon">📈</div>
                        <div class="menu-text"><h3>Monitoring Capaian Kinerja</h3><p>Triwulanan</p></div>
                    </div>
                </div>
                <div class="menu-item menu-sub">
                    <div class="menu-link" onclick="showPage('monitoring-renstra')">
                        <div class="menu-icon">🎯</div>
                        <div class="menu-text"><h3>Monitoring Capaian Renstra</h3><p>Capaian Akhir Renstra</p></div>
                    </div>
                </div>



                <!-- ENTRY CAPAIAN KINERJA -->
                <div class="menu-item menu-sub">
                    <div class="menu-link" onclick="showPage('capaian-kinerja')">
                        <div class="menu-icon">&#x270D;&#xFE0F;</div>
                        <div class="menu-text"><h3>Entry Capaian Kinerja</h3><p>Input IKU per Triwulan</p></div>
                    </div>
                </div>

                <!-- GENERATE NOTULA -->
                <div class="menu-item menu-sub">
                    <div class="menu-link" onclick="showPage('notula-kinerja')">
                        <div class="menu-icon">&#x1F4C4;</div>
                        <div class="menu-text"><h3>Generate Notula</h3><p>Notula Monitoring Kinerja</p></div>
                    </div>
                </div>

                <!-- PELAPORAN (header) -->
                <div class="menu-section-header">📄 Pelaporan</div>
                <div class="menu-item menu-sub">
                    <div class="menu-link has-dropdown" onclick="toggleSubmenu(this)">
                        <div class="menu-icon">⚒️</div>
                        <div class="menu-text"><h3>LAKIN</h3><p>Laporan Akuntabilitas</p></div>
                        <span class="menu-arrow">▶</span>
                    </div>
                    <div class="submenu">
                        <div class="submenu-item" onclick="showPage('lakin-draft')">📝 Draft</div>
                        <div class="submenu-item" onclick="showPage('lakin-final')">✅ Final</div>
                    </div>
                </div>

                <!-- EVALUASI (header) -->
                <div class="menu-section-header">🔍 Evaluasi</div>
                <div class="menu-item menu-sub">
                    <div class="menu-link" onclick="showPage('permindok')">
                        <div class="menu-icon">📄</div>
                        <div class="menu-text"><h3>Permintaan Dokumen</h3><p>Permindok by Tahun</p></div>
                    </div>
                </div>
                <!-- -->

                <!-- APLIKASI MONITORING (header) -->
                <div class="menu-section-header">💻 Aplikasi Monitoring</div>
                <div class="menu-item menu-sub">
                    <a href="https://esr.menpan.go.id" target="_blank" class="menu-link">
                        <div class="menu-icon">🔍</div>
                        <div class="menu-text"><h3>ESR</h3><p>Evaluasi Sistem Review</p></div>
                    </a>
                </div>
                <div class="menu-item menu-sub">
                    <a href="https://sinergi.web.bps.go.id" target="_blank" class="menu-link">
                        <div class="menu-icon">🤝</div>
                        <div class="menu-text"><h3>SINERGI</h3><p>Kolaborasi Unit</p></div>
                    </a>
                </div>

                <!-- MATERI & PANDUAN -->
                <div class="menu-section-header">📚 Referensi</div>
                <div class="menu-item menu-sub">
                    <div class="menu-link" onclick="showPage('materi-panduan')">
                        <div class="menu-icon">✨</div>
                        <div class="menu-text"><h3>Materi &amp; Panduan</h3><p>Referensi SAKIP</p></div>
                    </div>
                </div>

            </div>

            <?php if ($user_role === 'admin'): ?>
                <!-- ADMIN (header) -->
                <div class="menu-section-header">⚙️ Admin</div>
                <div class="menu-item menu-sub">
                    <div class="menu-link" onclick="showPage('kelola-user')">
                        <div class="menu-icon">👥</div>
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
