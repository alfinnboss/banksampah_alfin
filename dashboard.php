<?php
require 'config.php'; 
wajib_login();

$u = $_SESSION['user'];

// Update saldo terbaru dari database
$stmt = $conn->prepare("SELECT saldo FROM user WHERE id_user=?");
$stmt->bind_param("i", $u['id_user']); 
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
if ($row) { 
    $u['saldo'] = $row['saldo']; 
    $_SESSION['user']['saldo'] = $row['saldo']; 
}

$role = $u['role']; // 'admin', 'pengepul', atau 'siswa'/'user'/'guru'

// Kueri data sesuai role
if ($role === 'admin') {
    $totalUser = $conn->query("SELECT COUNT(*) c FROM user")->fetch_assoc()['c'];
    $pending = $conn->query("SELECT COUNT(*) c FROM transaksi WHERE status='pending'")->fetch_assoc()['c'];
    $totalSetor = $conn->query("SELECT COALESCE(SUM(total_rp),0) c FROM transaksi WHERE tipe='setor' AND status='diverifikasi'")->fetch_assoc()['c'];

    $recent = $conn->query("SELECT t.tanggal, u.nama, s.jenis_sampah, t.berat_kg, t.total_rp, t.status
        FROM transaksi t
        JOIN sampah s ON s.id_sampah=t.id_sampah
        JOIN user u ON u.id_user=t.id_user
        ORDER BY t.tanggal DESC LIMIT 5");

} elseif ($role === 'pengepul') {
    $totalBeli = $conn->query("SELECT COALESCE(SUM(total_rp),0) c FROM transaksi WHERE tipe='jual' AND status='disetujui'")->fetch_assoc()['c'];
    $totalKg = $conn->query("SELECT COALESCE(SUM(berat_kg),0) c FROM transaksi WHERE tipe='jual' AND status='disetujui'")->fetch_assoc()['c'];
    $pendingPengepul = $conn->query("SELECT COUNT(*) c FROM transaksi WHERE tipe='jual' AND status='pending'")->fetch_assoc()['c'];

    $recent = $conn->query("SELECT t.tanggal, s.jenis_sampah, t.berat_kg, t.total_rp, t.status
        FROM transaksi t
        JOIN sampah s ON s.id_sampah=t.id_sampah
        WHERE t.tipe='jual'
        ORDER BY t.tanggal DESC LIMIT 5");

} else {
    $stmt = $conn->prepare("SELECT COUNT(*) c FROM transaksi WHERE id_user=?");
    $stmt->bind_param("i", $u['id_user']); 
    $stmt->execute();
    $totalTransaksi = $stmt->get_result()->fetch_assoc()['c'];

    $stmt = $conn->prepare("SELECT COALESCE(SUM(berat_kg),0) c FROM transaksi WHERE id_user=? AND tipe='setor' AND status='diverifikasi'");
    $stmt->bind_param("i", $u['id_user']); 
    $stmt->execute();
    $totalKg = $stmt->get_result()->fetch_assoc()['c'];

    $stmt = $conn->prepare("SELECT t.tanggal, s.jenis_sampah, t.berat_kg, t.total_rp, t.status
        FROM transaksi t
        JOIN sampah s ON s.id_sampah=t.id_sampah
        WHERE t.id_user=?
        ORDER BY t.tanggal DESC LIMIT 5");
    $stmt->bind_param("i", $u['id_user']); 
    $stmt->execute();
    $recent = $stmt->get_result();
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard - Gates of Bank Sampah</title>
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Nunito:wght@600;700;800;900&display=swap">
</head>
<body class="bandito-theme">

<?php include 'nav.php'; ?>

<main class="container-bandito">

  <!-- BANNER HERO -->
  <section class="hero-bandito">
    <div class="hero-left">
      <small>Selamat datang</small>
      <h2><?=e($u['nama'])?> <span class="badge-role"><?=ucfirst(e($role))?></span></h2>
    </div>
    <div class="hero-right">
      <div class="pistol-icon">⚡</div>
      <div class="hero-action">
        <?php if ($role === 'admin'): ?>
          <a class="btn-hero" href="admin_transaksi.php">+ Verifikasi Setoran</a>
          <span class="hero-subtext">Pending: <?=(int)$pending?> transaksi</span>
        <?php elseif ($role === 'pengepul'): ?>
          <a class="btn-hero" href="beli_admin.php">+ Beli Sampah</a>
          <span class="hero-subtext">Menunggu: <?=(int)$pendingPengepul?> Transaksi</span>
        <?php else: ?>
          <a class="btn-hero" href="setor.php">+ Setor Sampah</a>
          <span class="hero-subtext">Saldo: Rp <?=number_format($u['saldo'],0,',','.')?></span>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- KARTU STATISTIK DINAMIS -->
  <section class="stats-bandito">
    <?php if ($role === 'admin'): ?>
      <div class="card-bandito">
        <div class="card-top-row">
          <div class="card-icon">👥</div>
          <div class="card-title-val">
            <span>Total User</span>
            <strong><?=$totalUser?></strong>
          </div>
        </div>
        <div class="card-bottom-table">
          <div class="sub-row"><span>Status</span><span class="pill-val">Aktif</span></div>
          <div class="sub-row cyan"><span>Role</span><span class="pill-val">Semua</span></div>
        </div>
      </div>

      <div class="card-bandito">
        <div class="card-top-row">
          <div class="card-icon">⏳</div>
          <div class="card-title-val">
            <span>Pending Verifikasi</span>
            <strong><?=$pending?></strong>
          </div>
        </div>
        <div class="card-bottom-table">
          <div class="sub-row"><span>Antrean</span><span class="pill-val">Setor</span></div>
          <div class="sub-row cyan"><span>Aksi</span><span class="pill-val">Verifikasi</span></div>
        </div>
      </div>

      <div class="card-bandito">
        <div class="card-top-row">
          <div class="card-icon">🏛️</div>
          <div class="card-title-val">
            <span>Total Setoran (Rp)</span>
            <strong>Rp <?=number_format($totalSetor,0,',','.')?></strong>
          </div>
        </div>
        <div class="card-bottom-table">
          <div class="sub-row"><span>Status</span><span class="pill-val">Verified</span></div>
          <div class="sub-row cyan"><span>Tipe</span><span class="pill-val">Setor</span></div>
        </div>
      </div>

    <?php elseif ($role === 'pengepul'): ?>
      <div class="card-bandito">
        <div class="card-top-row">
          <div class="card-icon">🛒</div>
          <div class="card-title-val">
            <span>Total Pembelian</span>
            <strong>Rp <?=number_format($totalBeli,0,',','.')?></strong>
          </div>
        </div>
        <div class="card-bottom-table">
          <div class="sub-row"><span>Tipe</span><span class="pill-val">Beli Sampah</span></div>
          <div class="sub-row cyan"><span>Status</span><span class="pill-val">Sukses</span></div>
        </div>
      </div>

      <div class="card-bandito">
        <div class="card-top-row">
          <div class="card-icon">⚖️️</div>
          <div class="card-title-val">
            <span>Total Sampah</span>
            <strong><?=number_format($totalKg,1,',','.')?> Kg</strong>
          </div>
        </div>
        <div class="card-bottom-table">
          <div class="sub-row"><span>Volume</span><span class="pill-val">Kg</span></div>
          <div class="sub-row cyan"><span>Kategori</span><span class="pill-val">Semua</span></div>
        </div>
      </div>

      <div class="card-bandito">
        <div class="card-top-row">
          <div class="card-icon">⏳</div>
          <div class="card-title-val">
            <span>Pending Pembelian</span>
            <strong><?=$pendingPengepul?></strong>
          </div>
        </div>
        <div class="card-bottom-table">
          <div class="sub-row"><span>Status</span><span class="pill-val">Proses</span></div>
          <div class="sub-row cyan"><span>Aksi</span><span class="pill-val">Cek Admin</span></div>
        </div>
      </div>

    <?php else: ?>
      <div class="card-bandito">
        <div class="card-top-row">
          <div class="card-icon">💰</div>
          <div class="card-title-val">
            <span>Saldo Utama</span>
            <strong>Rp <?=number_format($u['saldo'],0,',','.')?></strong>
          </div>
        </div>
        <div class="card-bottom-table">
          <div class="sub-row"><span>Status</span><span class="pill-val">Tersedia</span></div>
          <div class="sub-row cyan"><span>Tipe</span><span class="pill-val">Rupiah</span></div>
        </div>
      </div>

      <div class="card-bandito">
        <div class="card-top-row">
          <div class="card-icon">♻️</div>
          <div class="card-title-val">
            <span>Sampah Disetor</span>
            <strong><?=number_format($totalKg,1,',','.')?> Kg</strong>
          </div>
        </div>
        <div class="card-bottom-table">
          <div class="sub-row"><span>Kategori</span><span class="pill-val">Tergabung</span></div>
          <div class="sub-row cyan"><span>Aktivitas</span><span class="pill-val">Setor</span></div>
        </div>
      </div>

      <div class="card-bandito">
        <div class="card-top-row">
          <div class="card-icon">📜</div>
          <div class="card-title-val">
            <span>Total Transaksi</span>
            <strong><?=$totalTransaksi?> kali</strong>
          </div>
        </div>
        <div class="card-bottom-table">
          <div class="sub-row"><span>Status</span><span class="pill-val">Selesai</span></div>
          <div class="sub-row cyan"><span>Aksi</span><span class="pill-val">Tarik Saldo</span></div>
        </div>
      </div>
    <?php endif; ?>
  </section>

  <!-- MENU CEPAT & TRANSAKSI -->
  <div class="dashboard-grid">
    
    <section class="section-menu-cepat">
      <h3 class="section-title">Menu Cepat</h3>
      <div class="menu-list-vertical">
        <?php if ($role === 'admin'): ?>
          <a class="menu-btn-item" href="admin_transaksi.php">
            <div class="icon-box">⚡</div>
            <span class="btn-label label-green">Verifikasi</span>
          </a>
          <a class="menu-btn-item" href="users.php">
            <div class="icon-box">👥</div>
            <span class="btn-label label-green">Data User</span>
          </a>
          <a class="menu-btn-item" href="sampah.php">
            <div class="icon-box">♻️</div>
            <span class="btn-label label-green">Data Sampah</span>
          </a>
        <?php elseif ($role === 'pengepul'): ?>
          <a class="menu-btn-item" href="beli_admin.php">
            <div class="icon-box">🛒</div>
            <span class="btn-label label-green">Beli Sampah</span>
          </a>
          <a class="menu-btn-item" href="pembelian.php">
            <div class="icon-box">📜</div>
            <span class="btn-label label-green">Riwayat Beli</span>
          </a>
        <?php else: ?>
          <a class="menu-btn-item" href="setor.php">
            <div class="icon-box">⚡</div>
            <span class="btn-label label-green">Setor</span>
          </a>
          <a class="menu-btn-item" href="transaksi.php">
            <div class="icon-box">📜</div>
            <span class="btn-label label-green">Transaksi</span>
          </a>
          <a class="menu-btn-item" href="tarik.php">
            <div class="icon-box">🏛️</div>
            <span class="btn-label label-green">Tarik Saldo</span>
          </a>
        <?php endif; ?>
      </div>
    </section>

    <section class="section-transaksi">
      <h3 class="section-title">Transaksi Terbaru</h3>
      <div class="table-container-bandito">
        <?php if ($recent && $recent->num_rows > 0): ?>
          <table class="table-bandito">
            <thead>
              <tr>
                <th>Tanggal</th>
                <?php if ($role === 'admin'): ?><th>Pengguna</th><?php endif; ?>
                <th>Sampah</th>
                <th>Berat</th>
                <th>Total</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php while($r = $recent->fetch_assoc()): ?>
              <tr>
                <td><?=date('d-m-Y', strtotime($r['tanggal']))?></td>
                <?php if ($role === 'admin'): ?><td><?=e($r['nama'])?></td><?php endif; ?>
                <td><?=e($r['jenis_sampah'])?></td>
                <td><?=$r['berat_kg']?> kg</td>
                <td>Rp <?=number_format($r['total_rp'],2,',','.')?></td>
                <td>
                  <span class="status-pill status-<?=strtolower(e($r['status']))?>">
                    <?=ucfirst(e($r['status']))?>
                  </span>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        <?php else: ?>
          <div style="text-align:center; padding:20px; color:#0d122b; font-weight:800;">Belum ada transaksi.</div>
        <?php endif; ?>
      </div>
    </section>

  </div>

</main>

</body>
</html>