<?php /* Bilah atas: logo, identitas pengguna, tombol keluar */ ?>
    <header>
        <div class="logo-section">
            <div class="logo"><img src="assets/images/logo-bps.png" alt="BPS" style="width:100%;height:100%;object-fit:contain;"></div>
            <div class="logo-text">
                <h1>SOKAB</h1>
                <p>SAKIP Online BPS Kota Bima</p>
            </div>
        </div>
        <div class="user-section">
            <div class="user-info">
                <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                <span class="user-role"><?php echo ucfirst($user_role); ?></span>
            </div>
            <a href="includes/logout.php" class="btn-logout" onclick="return confirm('Yakin ingin keluar?')">
                <span>🚪</span> Keluar
            </a>
        </div>
    </header>
