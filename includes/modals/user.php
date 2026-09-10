<?php /* Modal: kelola pengguna (khusus admin) */ ?>
    <?php if ($user_role === 'admin'): ?>
    <!-- Modal Tambah/Edit User -->
    <div class="modal-overlay" id="modalUser">
        <div class="modal-box">
            <div class="modal-header">
                <h2 id="modalUserTitle">👤 Tambah User</h2>
                <button class="modal-close" onclick="tutupModalUser()">✕</button>
            </div>
            <form class="modal-form" id="formUser">
                <input type="hidden" id="inp_user_id">
                <div class="form-group">
                    <label>Username *</label>
                    <input type="text" id="inp_username" placeholder="username" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label>Nama Lengkap *</label>
                    <input type="text" id="inp_nama_lengkap" placeholder="Nama Lengkap" required>
                </div>
                <div class="form-group" id="groupPassword">
                    <label>Password *</label>
                    <input type="password" id="inp_password" placeholder="Minimal 6 karakter" autocomplete="new-password">
                    <small style="color:#666;display:block;margin-top:5px">Minimal 6 karakter</small>
                </div>
                <div class="form-group">
                    <label>Role *</label>
                    <select id="inp_role" required>
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <button type="submit" class="btn-submit-modal" id="btnSimpanUser">💾 Simpan User</button>
            </form>
        </div>
    </div>

    <!-- Modal Reset Password -->
    <div class="modal-overlay" id="modalResetPassword">
        <div class="modal-box" style="max-width:400px">
            <div class="modal-header">
                <h2>🔑 Reset Password</h2>
                <button class="modal-close" onclick="tutupModalResetPassword()">✕</button>
            </div>
            <form class="modal-form" id="formResetPassword">
                <input type="hidden" id="inp_reset_user_id">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" id="inp_reset_username" readonly style="background:#f7fafc">
                </div>
                <div class="form-group">
                    <label>Password Baru *</label>
                    <input type="password" id="inp_new_password" placeholder="Minimal 6 karakter" required autocomplete="new-password">
                    <small style="color:#666;display:block;margin-top:5px">Minimal 6 karakter</small>
                </div>
                <button type="submit" class="btn-submit-modal">🔑 Reset Password</button>
            </form>
        </div>
    </div>
    <?php endif; ?>
