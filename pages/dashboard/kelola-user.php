<?php /* Halaman: kelola-user */ ?>
            <?php if ($user_role === 'admin'): ?>
            <!-- ═══════════════════════════════ KELOLA USER ═══════════════════════════ -->
            <div id="page-kelola-user" class="content-page">
                <div class="page-header-bar">
                    <div class="page-header-icon" style="background:#fbd38d">👥</div>
                    <div><h2 class="page-title">Kelola User</h2><p class="page-subtitle">Manajemen Pengguna Sistem</p></div>
                    <button class="btn-tambah-jadwal" onclick="bukaModalTambahUser()">+ Tambah User</button>
                </div>
                
                <div class="content-wrap">
                    <!-- Stats User -->
                    <div class="stats-grid" style="margin-bottom:1.5rem">
                        <div class="stat-card stat-blue">
                            <div class="stat-icon">👥</div>
                            <div class="stat-info">
                                <div class="stat-label">Total User</div>
                                <div class="stat-value" id="statTotalUser">0</div>
                            </div>
                        </div>
                        <div class="stat-card stat-green">
                            <div class="stat-icon">👑</div>
                            <div class="stat-info">
                                <div class="stat-label">Admin</div>
                                <div class="stat-value" id="statAdmin">0</div>
                            </div>
                        </div>
                        <div class="stat-card stat-orange">
                            <div class="stat-icon">👤</div>
                            <div class="stat-info">
                                <div class="stat-label">User</div>
                                <div class="stat-value" id="statUser">0</div>
                            </div>
                        </div>
                    </div>

                    <!-- Table User -->
                    <div style="background:white;border-radius:12px;box-shadow:0 2px 4px rgba(0,0,0,0.05);overflow:hidden">
                        <table class="user-table" id="tableUser">
                            <thead>
                                <tr>
                                    <th style="width:50px">No</th>
                                    <th>Username</th>
                                    <th>Nama Lengkap</th>
                                    <th style="width:100px">Role</th>
                                    <th style="width:180px">Last Login</th>
                                    <th style="width:200px;text-align:center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="userTableBody">
                                <tr><td colspan="6" style="text-align:center;padding:2rem;color:#999">Memuat data...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
