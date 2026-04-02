        // ═════════════════════════════════════════════════════════════════════
        // USER MANAGEMENT (Admin Only)
        // ═════════════════════════════════════════════════════════════════════
        
        let allUsers = [];

        // Load users saat page kelola-user dibuka
        function loadUsers() {
            fetch('api/users.php')
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        allUsers = res.data;
                        updateUserStats(res.data);
                        renderUserTable(res.data);
                    } else {
                        showToast('❌ ' + res.message, 'error');
                    }
                })
                .catch(err => {
                    showToast('❌ Gagal memuat data user', 'error');
                    console.error(err);
                });
        }

        // Update stats
        function updateUserStats(users) {
            const totalUser = users.length;
            const totalAdmin = users.filter(u => u.role === 'admin').length;
            const totalUserRole = users.filter(u => u.role === 'user').length;
            
            document.getElementById('statTotalUser').textContent = totalUser;
            document.getElementById('statAdmin').textContent = totalAdmin;
            document.getElementById('statUser').textContent = totalUserRole;
        }

        // Render table
        function renderUserTable(users) {
            const tbody = document.getElementById('userTableBody');
            
            if (!users.length) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:2rem;color:#999">Belum ada user</td></tr>';
                return;
            }

            tbody.innerHTML = users.map((u, i) => {
                const roleBadge = u.role === 'admin' 
                    ? '<span class="role-badge role-admin">👑 Admin</span>' 
                    : '<span class="role-badge role-user">👤 User</span>';
                
                const lastLogin = u.last_login 
                    ? formatTgl(u.last_login) 
                    : '<span style="color:#999">Belum pernah</span>';

                const currentUserId = <?= json_encode($_SESSION['user_id']) ?>;
                const isSelf = u.id == currentUserId;
                
                return `
                    <tr>
                        <td>${i + 1}</td>
                        <td><strong>${escHtml(u.username)}</strong></td>
                        <td>${escHtml(u.nama_lengkap)}</td>
                        <td>${roleBadge}</td>
                        <td style="font-size:0.8rem;color:#666">${lastLogin}</td>
                        <td style="text-align:center">
                            <button class="btn-user-action btn-edit-user" onclick="editUser(${u.id})" title="Edit">✏️ Edit</button>
                            <button class="btn-user-action btn-reset-pw" onclick="bukaResetPassword(${u.id}, '${escHtml(u.username)}')" title="Reset Password">🔑 Reset</button>
                            ${isSelf ? '<span style="font-size:0.75rem;color:#999">(Anda)</span>' : `<button class="btn-user-action btn-delete-user" onclick="hapusUser(${u.id}, '${escHtml(u.username)}')" title="Hapus">🗑️ Hapus</button>`}
                        </td>
                    </tr>
                `;
            }).join('');
        }

        // Buka modal tambah user
        function bukaModalTambahUser() {
            document.getElementById('formUser').reset();
            document.getElementById('inp_user_id').value = '';
            document.getElementById('modalUserTitle').textContent = '👤 Tambah User';
            document.getElementById('btnSimpanUser').textContent = '💾 Simpan User';
            document.getElementById('groupPassword').style.display = 'block';
            document.getElementById('inp_password').required = true;
            document.getElementById('modalUser').classList.add('show');
        }

        // Edit user
        function editUser(id) {
            const user = allUsers.find(u => u.id == id);
            if (!user) {
                showToast('❌ User tidak ditemukan', 'error');
                return;
            }

            document.getElementById('inp_user_id').value = user.id;
            document.getElementById('inp_username').value = user.username;
            document.getElementById('inp_nama_lengkap').value = user.nama_lengkap;
            document.getElementById('inp_role').value = user.role;
            
            document.getElementById('modalUserTitle').textContent = '✏️ Edit User';
            document.getElementById('btnSimpanUser').textContent = '💾 Update User';
            document.getElementById('groupPassword').style.display = 'none';
            document.getElementById('inp_password').required = false;
            document.getElementById('inp_password').value = '';
            
            document.getElementById('modalUser').classList.add('show');
        }

        // Tutup modal user
        function tutupModalUser() {
            document.getElementById('modalUser').classList.remove('show');
            document.getElementById('formUser').reset();
        }

        // Submit form user
        document.getElementById('formUser')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const id = document.getElementById('inp_user_id').value;
            const isEdit = !!id;
            
            const payload = {
                username: document.getElementById('inp_username').value.trim(),
                nama_lengkap: document.getElementById('inp_nama_lengkap').value.trim(),
                role: document.getElementById('inp_role').value,
            };

            if (!isEdit) {
                // Tambah user - password required
                const password = document.getElementById('inp_password').value.trim();
                if (!password || password.length < 6) {
                    showToast('❌ Password minimal 6 karakter', 'error');
                    return;
                }
                payload.password = password;
            } else {
                // Edit user
                payload.id = parseInt(id);
            }

            const action = isEdit ? 'edit' : 'tambah';
            const successMsg = isEdit ? '✅ User berhasil diperbarui!' : '✅ User berhasil ditambahkan!';

            fetch(`api/users.php?action=${action}`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    tutupModalUser();
                    loadUsers();
                    showToast(successMsg, 'success');
                } else {
                    showToast('❌ ' + res.message, 'error');
                }
            })
            .catch(err => {
                showToast('❌ Terjadi kesalahan', 'error');
                console.error(err);
            });
        });

        // Reset Password
        function bukaResetPassword(id, username) {
            document.getElementById('inp_reset_user_id').value = id;
            document.getElementById('inp_reset_username').value = username;
            document.getElementById('inp_new_password').value = '';
            document.getElementById('modalResetPassword').classList.add('show');
        }

        function tutupModalResetPassword() {
            document.getElementById('modalResetPassword').classList.remove('show');
            document.getElementById('formResetPassword').reset();
        }

        document.getElementById('formResetPassword')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const id = document.getElementById('inp_reset_user_id').value;
            const newPassword = document.getElementById('inp_new_password').value.trim();

            if (!newPassword || newPassword.length < 6) {
                showToast('❌ Password minimal 6 karakter', 'error');
                return;
            }

            if (!confirm('Yakin ingin reset password user ini?')) return;

            fetch('api/users.php?action=reset_password', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ id: parseInt(id), new_password: newPassword })
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    tutupModalResetPassword();
                    showToast('✅ Password berhasil direset!', 'success');
                } else {
                    showToast('❌ ' + res.message, 'error');
                }
            })
            .catch(err => {
                showToast('❌ Terjadi kesalahan', 'error');
                console.error(err);
            });
        });

        // Hapus user
        function hapusUser(id, username) {
            if (!confirm(`Yakin ingin menghapus user "${username}"?\n\nTindakan ini tidak dapat dibatalkan!`)) return;

            fetch('api/users.php?action=hapus', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ id: parseInt(id) })
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    loadUsers();
                    showToast('✅ User berhasil dihapus', 'success');
                } else {
                    showToast('❌ ' + res.message, 'error');
                }
            })
            .catch(err => {
                showToast('❌ Terjadi kesalahan', 'error');
                console.error(err);
            });
        }
