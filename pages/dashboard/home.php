<?php /* Halaman: home */ ?>
            <div id="page-home" class="content-page active">
                <div class="hero" style="background: linear-gradient(135deg, #1e293b, #0ea5e9); border-radius: 16px; padding: 2.5rem 3rem; margin-bottom: 0;">
                    <h1 style="color:white; font-size:2rem; margin-bottom:0.5rem"><span style="color:#f6ad55">SOKAB</span> — SAKIP Online BPS Kota Bima</h1>
                    <p style="color:rgba(255,255,255,0.85); font-size:0.95rem; margin:10">Sistem Manajemen SAKIP Terintegrasi BPS Kota Bima</p>
                </div>

                <!-- Stats ringkas -->
                <div class="stats-grid" id="statsGrid">
                    <div class="stat-card"><div class="stat-icon">⏳</div><div class="stat-number" id="statBelum">-</div><div class="stat-label">Belum Mulai</div></div>
                    <div class="stat-card"><div class="stat-icon">🔄</div><div class="stat-number" id="statProses">-</div><div class="stat-label">Sedang Berjalan</div></div>
                    <div class="stat-card"><div class="stat-icon">✅</div><div class="stat-number" id="statSelesai">-</div><div class="stat-label">Selesai</div></div>
                    <div class="stat-card"><div class="stat-icon">🚨</div><div class="stat-number" id="statDeadline">-</div><div class="stat-label">Deadline 30 Hari</div></div>
                </div>

                <!-- Kalender + Panel Jadwal -->
                <div class="jadwal-wrapper">
                    <!-- Kalender -->
                    <div class="kalender-card">
                        <h3>📅 Kalender Jadwal SAKIP</h3>
                        <div id="kalender"></div>
                    </div>

                    <!-- Panel kanan: jadwal mendatang + kelola -->
                    <div class="jadwal-panel">

                        <!-- Kelola jadwal (admin only) -->
                        <?php if ($user_role === 'admin'): ?>
                        <div class="jadwal-panel-card">
                            <h3>
                                ⚙️ Kelola Jadwal
                                <button class="btn-tambah-jadwal" onclick="bukaModalTambah()">+ Tambah</button>
                            </h3>
                            <div class="jadwal-list" id="listSemuaJadwal">
                                <div style="text-align:center;padding:1rem;color:#718096;font-size:0.85rem">Memuat jadwal...</div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Jadwal mendatang -->
                        <div class="jadwal-panel-card">
                            <h3>🔔 Deadline Mendatang <span style="font-size:0.72rem;color:#718096;font-weight:400">(30 hari)</span></h3>
                            <div class="jadwal-list" id="listUpcoming">
                                <div style="text-align:center;padding:1rem;color:#718096;font-size:0.85rem">Memuat...</div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
