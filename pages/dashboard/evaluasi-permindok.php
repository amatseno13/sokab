<?php /* Halaman: evaluasi-permindok */ ?>
            <div id="page-evaluasi-permindok" class="content-page">
                <div class="page-header-bar">
                    <div class="page-header-icon" style="background:#e9d8fd">📑</div>
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
