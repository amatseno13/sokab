<?php /* Halaman: permindok */ ?>
            <div id="page-permindok" class="content-page">
                <div class="page-header-bar">
                    <div class="page-header-icon" style="background:#dbeafe">📄</div>
                    <div><h2 class="page-title">Permintaan Dokumen</h2><p class="page-subtitle">Kelola permintaan dokumen berdasarkan tahun</p></div>
                </div>

                <!-- Toolbar -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding: 1.5rem; background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <label style="font-weight: 500; color: #64748b;">Tahun:</label>
                        <select id="filterPermindokTahun" 
                                onchange="loadPermindokData(this.value)"
                                style="padding: 0.5rem 1rem; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 0.95rem; cursor: pointer;">
                            <option value="2024">2024</option>
                            <option value="2025">2025</option>
                            <option value="2026" selected>2026</option>
                            <option value="2027">2027</option>
                        </select>
                    </div>
                    
                    <?php if ($user_role === 'admin'): ?>
                    <button onclick="kelolaPermindok()" 
                            style="padding: 0.5rem 1rem; background: #16a34a; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 0.85rem; font-weight: 500;">
                        ⚙️ Kelola Permindok
                    </button>
                    <?php endif; ?>
                </div>

                <!-- Main Table -->
                <div style="background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <!-- Pagination Controls Top -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <span style="color: #64748b; font-size: 0.875rem;">Tampilkan:</span>
                            <select onchange="changePermindokRowsPerPage(this.value)" 
                                    style="padding: 0.4rem 0.75rem; border: 1px solid #e2e8f0; border-radius: 6px; cursor: pointer; font-size: 0.875rem;">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                            <span style="color: #64748b; font-size: 0.875rem;">per halaman</span>
                        </div>
                        <div id="permindokPaginationInfo" style="color: #64748b; font-size: 0.875rem;"></div>
                    </div>

                    <!-- Table -->
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                                    <th style="padding: 1rem; text-align: center; border-top-left-radius: 8px; width: 80px;">No</th>
                                    <th style="padding: 1rem; text-align: center;">Judul Permindok</th>
                                    <th style="padding: 1rem; text-align: center; border-top-right-radius: 8px; width: 200px;">Link Permindok</th>
                                </tr>
                            </thead>
                            <tbody id="permindokTableBody">
                                <tr>
                                    <td colspan="3" style="text-align: center; padding: 2rem; color: #94a3b8;">
                                        <div style="font-size: 3rem; margin-bottom: 1rem;">📄</div>
                                        <div style="font-size: 1.1rem; font-weight: 500; color: #64748b;">Loading data...</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Controls Bottom -->
                    <div style="display: flex; justify-content: center; align-items: center; margin-top: 1.5rem; gap: 0.5rem;" id="permindokPagination"></div>
                </div>
            </div>
