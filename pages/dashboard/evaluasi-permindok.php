<?php /* Halaman: evaluasi-permindok */ ?>
            <div id="page-evaluasi-permindok" class="content-page">
                <div class="page-header-bar">
                    <div class="page-header-icon"><svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 9V5a2 2 0 0 1 2-2h3.9a2 2 0 0 1 1.69.9l.81 1.2a2 2 0 0 0 1.67.9H20a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-1" /><path d="M2 13h10" /><path d="m9 16 3-3-3-3" /></svg></div>
                    <div><h2 class="page-title">Evaluasi Per Permindok</h2><p class="page-subtitle">Evaluasi berdasarkan Permenpan RB</p></div>
                    <?php if ($user_role === 'admin'): ?>
                    <button class="btn-tambah-jadwal" onclick="bukaModalUploadPermindok()">+ Upload Dokumen</button>
                    <?php endif; ?>
                </div>
                <div id="content-evaluasi-permindok">
                    <table id="table-permindok" class="data-table" style="margin-top:1rem">
                        <thead>
                            <tr>
                                <th style="width:50px">No</th>
                                <th>Judul Dokumen</th>
                                <th style="width:100px;text-align:center">Tahun</th>
                                <th style="width:120px;text-align:center">Metode</th>
                                <th style="width:150px;text-align:center">Tanggal Upload</th>
                                <th style="width:120px;text-align:center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="6" style="text-align:center;padding:2rem">Memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
