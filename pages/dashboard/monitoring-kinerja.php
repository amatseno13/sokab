<?php /* Halaman: monitoring-kinerja */ ?>
            <div id="page-monitoring-kinerja" class="content-page">
                <div class="page-header-bar">
                    <div class="page-header-icon" style="background:#c6f6d5">📈</div>
                    <div><h2 class="page-title">Monitoring Capaian Kinerja</h2><p class="page-subtitle">Laporan Monitoring Periodik</p></div>
                </div>
                <!-- Container Dokumen Monitoring -->
                <div id="content-monitoring-kinerja" class="gdrive-container">
                    <div class="loading-state">Memuat dokumen...</div>
                </div>
                
                <!-- Container IKSS (Terpisah agar tidak tertimpa) -->
                <div id="container-ikss" class="gdrive-container" style="margin-top: 2rem;">
                    <div class="card-grid">
                        <div class="stat-card" style="grid-column: 1/-1; background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                                <div>
                                    <h3 style="font-size: 1.25rem; color: #1e293b; margin-bottom: 0.5rem; font-weight: 600;">📊 Tabel IKSS</h3>
                                    <p style="color: #64748b; font-size: 0.9rem;">Indikator Kinerja Sasaran Strategis</p>
                                </div>
                                <div style="display: flex; gap: 0.75rem; align-items: center;">
                                    <select id="ikssFilterTahun" style="padding: 0.5rem 1rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem; background: white;">
                                        <!-- Tahun akan di-generate oleh JavaScript -->
                                    </select>
                                    <button id="ikssFilterTW1" class="ikss-tw-btn active" data-tw="TW I" style="padding: 0.5rem 1rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.85rem; background: #1e40af; color: white; cursor: pointer; font-weight: 500;">TW I</button>
                                    <button id="ikssFilterTW2" class="ikss-tw-btn" data-tw="TW II" style="padding: 0.5rem 1rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.85rem; background: white; color: #64748b; cursor: pointer;">TW II</button>
                                    <button id="ikssFilterTW3" class="ikss-tw-btn" data-tw="TW III" style="padding: 0.5rem 1rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.85rem; background: white; color: #64748b; cursor: pointer;">TW III</button>
                                    <button id="ikssFilterTW4" class="ikss-tw-btn" data-tw="TW IV" style="padding: 0.5rem 1rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.85rem; background: white; color: #64748b; cursor: pointer;">TW IV</button>
                                    <?php if($user_role==='admin'): ?>
                                    <button onclick="kelolaIKSS()" style="padding: 0.5rem 1rem; background: #16a34a; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 0.85rem; font-weight: 500;">⚙️ Kelola IKSS</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Pagination controls -->
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; padding: 0.75rem; background: #f8fafc; border-radius: 8px;">
                                <div style="display: flex; gap: 0.5rem; align-items: center;">
                                    <span style="color: #64748b; font-size: 0.9rem;">Tampilkan:</span>
                                    <select id="ikssPerPage" style="padding: 0.35rem 0.75rem; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 0.85rem;">
                                        <option value="5">5</option>
                                        <option value="10" selected>10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                        <option value="-1">Semua</option>
                                    </select>
                                    <span style="color: #64748b; font-size: 0.9rem;">per halaman</span>
                                </div>
                                <div id="ikssPaginationInfo" style="color: #64748b; font-size: 0.9rem;"></div>
                                <div id="ikssPaginationButtons" style="display: flex; gap: 0.5rem;"></div>
                            </div>
                            
                            <div style="overflow-x: auto;">
                                <table id="tableIKSS" style="width: 100%; border-collapse: collapse;">
                                    <thead style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                                        <tr>
                                            <th style="padding: 1rem; text-align: center; font-weight: 600; color: #475569; width: 50px;">No</th>
                                            <th style="padding: 1rem; text-align: center; font-weight: 600; color: #475569;">Sasaran Kegiatan</th>
                                            <th style="padding: 1rem; text-align: center; font-weight: 600; color: #475569;">Indikator Kinerja</th>
                                            <th style="padding: 1rem; text-align: center; font-weight: 600; color: #475569; width: 200px;">Link Dokumen Sumber</th>
                                            <th style="padding: 1rem; text-align: center; font-weight: 600; color: #475569; width: 250px;">Link Bukti Tindak Lanjut TW Sebelumnya</th>
                                        </tr>
                                    </thead>
                                    <tbody id="ikssTableBody">
                                        <tr>
                                            <td colspan="5" style="padding: 2rem; text-align: center; color: #94a3b8;">
                                                Memuat data IKSS...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination Container -->
                            <div id="paginationContainer"></div>
                        </div>
                    </div>
                </div>
            </div>
