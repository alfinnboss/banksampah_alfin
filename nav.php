<?php
// Pastikan session sudah berjalan
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_login = $_SESSION['user'] ?? null;
$nama_user  = $user_login['nama'] ?? 'Pengguna';
$role_user  = $user_login['role'] ?? 'guest';
$username   = $user_login['username'] ?? '';

// Deteksi nama file yang sedang dibuka untuk efek menu aktif
$current_page = basename($_SERVER['PHP_SELF']);
?>

<style>
/* =====================================================
   GATES OF BANK SAMPAH - NAVBAR STYLES
   ===================================================== */
.navbar-wrapper {
    width: 100%;
    background: #030d1d linear-gradient(180deg, #071f43 0%, #020a17 100%);
    border-bottom: 2px solid #e0a310;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.7);
    position: sticky;
    top: 0;
    z-index: 999;
}

/* HEADER UTAMA (LOGO & PROFIL) */
.nav-header {
    max-width: 1200px;
    margin: 0 auto;
    padding: 12px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.nav-brand {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
}

.brand-icon {
    font-size: 26px;
    color: #ff9d00;
    filter: drop-shadow(0 0 8px #ff9d00);
}

.brand-title {
    font-family: 'Cinzel', serif;
    font-size: 22px;
    font-weight: 900;
    color: #ffffff;
    letter-spacing: 2px;
    text-shadow: 0 0 10px rgba(56, 203, 236, 0.6);
    line-height: 1.1;
}

.brand-title span {
    display: block;
    color: #ffe885;
    font-size: 16px;
}

/* KOTAK PROFIL USER (KANAN ATAS) */
.user-profile-box {
    background: linear-gradient(180deg, #072552 0%, #031126 100%);
    border: 1.5px solid #e0a310;
    border-radius: 12px;
    padding: 6px 14px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: inset 0 0 8px rgba(224, 163, 16, 0.2);
}

.user-info {
    display: flex;
    flex-direction: column;
    text-align: right;
}

.user-name {
    color: #ffffff;
    font-size: 13px;
    font-weight: 800;
}

.user-role {
    color: #8bb2f9;
    font-size: 11px;
    font-weight: 700;
}

.btn-logout {
    background: linear-gradient(180deg, #e74c3c 0%, #961c10 100%);
    border: 1px solid #ff7b7b;
    color: #ffffff;
    font-family: 'Cinzel', serif;
    font-size: 12px;
    font-weight: 900;
    padding: 5px 12px;
    border-radius: 6px;
    text-decoration: none;
    transition: all 0.2s ease;
    letter-spacing: 0.5px;
}

.btn-logout:hover {
    filter: brightness(1.2);
    transform: translateY(-1px);
    box-shadow: 0 0 8px rgba(231, 76, 60, 0.6);
}

/* MENU NAVIGASI BAWAH (STRIP EMAS) */
.nav-menu-bar {
    background: #020914;
    border-top: 1px solid rgba(224, 163, 16, 0.4);
    padding: 6px 0;
}

.nav-links {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 30px;
    list-style: none;
}

.nav-links a {
    color: #8bb2f9;
    font-family: 'Cinzel', serif;
    font-weight: 800;
    font-size: 14px;
    text-decoration: none;
    letter-spacing: 1px;
    padding: 4px 8px;
    transition: all 0.2s ease;
    position: relative;
}

.nav-links a:hover,
.nav-links a.active {
    color: #ffffff;
    text-shadow: 0 0 8px rgba(255, 232, 133, 0.8);
}

.nav-links a.active::after {
    content: '';
    position: absolute;
    bottom: -6px;
    left: 0;
    width: 100%;
    height: 3px;
    background: #ffe885;
    box-shadow: 0 0 8px #ffe885;
    border-radius: 2px;
}

@media (max-width: 768px) {
    .nav-header {
        flex-direction: column;
        gap: 10px;
    }
    .user-profile-box {
        width: 100%;
        justify-content: space-between;
    }
    .nav-links {
        gap: 15px;
        flex-wrap: wrap;
    }
}
</style>

<header class="navbar-wrapper">
    <!-- BARIS ATAS: LOGO & PROFIL USER -->
    <div class="nav-header">
        <a href="dashboard.php" class="nav-brand">
            <span class="brand-icon">⚡</span>
            <div class="brand-title">
                GATES OF
                <span>BANK SAMPAH</span>
            </div>
        </a>

        <?php if ($user_login): ?>
            <div class="user-profile-box">
                <div class="user-info">
                    <span class="user-name">👑 <?= e($nama_user) ?> (<?= e($username) ?>)</span>
                    <span class="user-role">Akses: <?= strtoupper(e($role_user)) ?></span>
                </div>
                <a href="logout.php" class="btn-logout">KELUAR</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- BARIS BAWAH: MENU NAVIGASI UTAMA -->
    <nav class="nav-menu-bar">
        <ul class="nav-links">
            <?php if ($role_user === 'pengepul'): ?>
                <li>
                    <a href="dashboard.php" class="<?= $current_page === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
                </li>
                <li>
                    <a href="beli_admin.php" class="<?= $current_page === 'beli_admin.php' ? 'active' : '' ?>">Beli Sampah</a>
                </li>
                <li>
                    <a href="pembelian.php" class="<?= $current_page === 'pembelian.php' ? 'active' : '' ?>">Pembelian</a>
                </li>
            <?php elseif ($role_user === 'admin'): ?>
                <li>
                    <a href="dashboard.php" class="<?= $current_page === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
                </li>
                <li>
                    <a href="setor.php" class="<?= $current_page === 'setor.php' ? 'active' : '' ?>">Setor Sampah</a>
                </li>
                <li>
                    <a href="kelola_sampah.php" class="<?= $current_page === 'kelola_sampah.php' ? 'active' : '' ?>">Kelola Sampah</a>
                </li>
                <li>
                    <a href="transaksi.php" class="<?= $current_page === 'transaksi.php' ? 'active' : '' ?>">Transaksi</a>
                </li>
            <?php else: ?>
                <!-- Menu Siswa / Guru -->
                <li>
                    <a href="dashboard.php" class="<?= $current_page === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
                </li>
                <li>
                    <a href="setor.php" class="<?= $current_page === 'setor.php' ? 'active' : '' ?>">Setor Sampah</a>
                </li>
                <li>
                    <a href="riwayat.php" class="<?= $current_page === 'riwayat.php' ? 'active' : '' ?>">Riwayat Setoran</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</header>