<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (function_exists('wajib_login')) {
    wajib_login();
} else {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user'])) {
        header("Location: login.php");
        exit;
    }
}

if (!isset($_SESSION['user']['role']) || strtolower($_SESSION['user']['role']) !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

$error   = '';
$success = '';

// PROSES VERIFIKASI TRANSAKSI
if (isset($_GET['aksi']) && isset($_GET['id'])) {
    $id_transaksi = (int)$_GET['id'];
    $aksi         = $_GET['aksi'];

    if ($aksi === 'setujui') {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("SELECT * FROM transaksi WHERE id_transaksi = ? AND status = 'pending'");
            $stmt->bind_param("i", $id_transaksi);
            $stmt->execute();
            $trx = $stmt->get_result()->fetch_assoc();

            if ($trx) {
                $stmt_upd = $conn->prepare("UPDATE transaksi SET status = 'diverifikasi' WHERE id_transaksi = ?");
                $stmt_upd->bind_param("i", $id_transaksi);
                $stmt_upd->execute();

                $stmt_user = $conn->prepare("UPDATE user SET saldo = saldo + ? WHERE id_user = ?");
                $stmt_user->bind_param("di", $trx['total_rp'], $trx['id_user']);
                $stmt_user->execute();

                $stmt_stok = $conn->prepare("UPDATE sampah SET stok_kg = stok_kg + ? WHERE id_sampah = ?");
                $stmt_stok->bind_param("di", $trx['berat_kg'], $trx['id_sampah']);
                $stmt_stok->execute();

                $conn->commit();
                $success = "Setoran berhasil diverifikasi!";
            } else {
                $error = "Transaksi tidak ditemukan atau sudah diproses!";
            }
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Gagal memproses transaksi: " . $e->getMessage();
        }
    } elseif ($aksi === 'tolak') {
        $stmt_upd = $conn->prepare("UPDATE transaksi SET status = 'ditolak' WHERE id_transaksi = ?");
        $stmt_upd->bind_param("i", $id_transaksi);
        if ($stmt_upd->execute()) {
            $success = "Setoran sampah berhasil ditolak.";
        } else {
            $error = "Gagal menolak transaksi.";
        }
    }
}

// FETCH DATA PENDING
$data_pending = $conn->query("
    SELECT t.*, u.nama, u.username, s.jenis_sampah 
    FROM transaksi t
    LEFT JOIN user u ON t.id_user = u.id_user
    LEFT JOIN sampah s ON t.id_sampah = s.id_sampah
    WHERE t.status = 'pending'
    ORDER BY t.tanggal DESC
");

// FETCH DATA RIWAYAT
$data_riwayat = $conn->query("
    SELECT t.*, u.nama, u.username, s.jenis_sampah 
    FROM transaksi t
    LEFT JOIN user u ON t.id_user = u.id_user
    LEFT JOIN sampah s ON t.id_sampah = s.id_sampah
    WHERE t.status != 'pending'
    ORDER BY t.tanggal DESC LIMIT 10
");
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Setoran - Gates of Bank Sampah</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Nunito:wght@600;700;800;900&display=swap">

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            background: #020b18 radial-gradient(circle at 50% 30%, #0d284f 0%, #030a16 100%) !important;
            color: #fff !important;
            font-family: 'Nunito', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            padding-bottom: 50px;
        }

        .container { width: 100%; max-width: 1100px; margin: 30px auto; padding: 0 20px; }
        
        .panel {
            background: linear-gradient(180deg, #061935 0%, #020d1f 100%);
            border: 2px solid #e0a310;
            border-radius: 18px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.8), inset 0 0 15px rgba(224, 163, 16, 0.2);
            padding: 25px;
            margin-bottom: 30px;
        }

        h1 { 
            font-family: 'Cinzel', serif; 
            font-size: 24px; 
            font-weight: 900; 
            color: #ffe885; 
            text-align: center; 
            margin-bottom: 6px;
            text-shadow: 0 2px 4px #000;
        }

        p.subtitle {
            text-align: center;
            color: #8bb2f9;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .alert { padding: 12px; border-radius: 8px; font-size: 14px; text-align: center; margin-bottom: 15px; font-weight: bold; }
        .alert-error { background: rgba(231, 76, 60, 0.2); border: 1px solid #e74c3c; color: #ff7b7b; }
        .alert-success { background: rgba(46, 204, 113, 0.2); border: 1px solid #2ecc71; color: #2ecc71; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 14px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #1a3c6d; color: #fff; }
        th { background: #04142b; color: #ffe885; font-family: 'Cinzel', serif; font-size: 13px; letter-spacing: 0.5px; }
        
        tr:hover { background: rgba(255, 255, 255, 0.03); }

        .badge { padding: 5px 10px; border-radius: 6px; font-size: 11px; font-weight: 800; text-transform: uppercase; display: inline-block; }
        .badge-pending { background: rgba(241, 196, 15, 0.2); color: #f1c40f; border: 1px solid #f1c40f; }
        .badge-diverifikasi, .badge-selesai { background: rgba(46, 204, 113, 0.2); color: #2ecc71; border: 1px solid #2ecc71; }
        .badge-ditolak { background: rgba(231, 76, 60, 0.2); color: #e74c3c; border: 1px solid #e74c3c; }

        .btn-acc {
            background: linear-gradient(180deg, #28a745 0%, #1e7e34 100%);
            border: 1px solid #2ecc71;
            color: white !important;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 800;
            font-size: 12px;
            box-shadow: 0 0 8px rgba(46, 204, 113, 0.4);
            display: inline-block;
        }

        .btn-reject {
            background: linear-gradient(180deg, #dc3545 0%, #bd2130 100%);
            border: 1px solid #e74c3c;
            color: white !important;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 800;
            font-size: 12px;
            box-shadow: 0 0 8px rgba(231, 76, 60, 0.4);
            display: inline-block;
            margin-left: 4px;
        }

        .btn-acc:hover, .btn-reject:hover { filter: brightness(1.2); transform: translateY(-1px); }
    </style>
</head>
<body>

<?php if (file_exists('nav.php')) include 'nav.php'; ?>

<main class="container">
    
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

    <div class="panel">
        <h1>⚡ VERIFIKASI SETORAN ⚡</h1>
        <p class="subtitle">Setujui setoran agar saldo pengguna dan stok sampah bertambah.</p>

        <h3 style="color: #ffe885; font-family: 'Cinzel', serif; font-size: 16px; margin-bottom: 12px;">⏳ Menunggu Verifikasi</h3>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Pengguna</th>
                        <th>Sampah</th>
                        <th>Berat</th>
                        <th>Total</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($data_pending && $data_pending->num_rows > 0): ?>
                        <?php while ($t = $data_pending->fetch_assoc()): ?>
                            <tr>
                                <td><?= date('Y-m-d H:i:s', strtotime($t['tanggal'])) ?></td>
                                <td><strong><?= e($t['nama'] ?? $t['username'] ?? 'Pengguna') ?></strong></td>
                                <td><?= e($t['jenis_sampah'] ?? '-') ?></td>
                                <td><?= number_format($t['berat_kg'] ?? 0, 2, ',', '.') ?> kg</td>
                                <td style="color: #ffe885; font-weight: 800;">Rp <?= number_format($t['total_rp'] ?? 0, 0, ',', '.') ?></td>
                                <td>
                                    <a href="?aksi=setujui&id=<?= $t['id_transaksi'] ?>" class="btn-acc" onclick="return confirm('ACC setoran ini?')">Verifikasi</a>
                                    <a href="?aksi=tolak&id=<?= $t['id_transaksi'] ?>" class="btn-reject" onclick="return confirm('Tolak setoran ini?')">Tolak</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align: center; color: #8bb2f9; padding: 20px;">Tidak ada setoran sampah yang menunggu verifikasi.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel">
        <h3 style="color: #ffe885; font-family: 'Cinzel', serif; font-size: 16px; margin-bottom: 12px;">📜 Riwayat Terakhir</h3>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Pengguna</th>
                        <th>Sampah</th>
                        <th>Berat</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($data_riwayat && $data_riwayat->num_rows > 0): ?>
                        <?php while ($r = $data_riwayat->fetch_assoc()): ?>
                            <tr>
                                <td><?= date('Y-m-d H:i:s', strtotime($r['tanggal'])) ?></td>
                                <td><strong><?= e($r['nama'] ?? $r['username'] ?? 'Pengguna') ?></strong></td>
                                <td><?= e($r['jenis_sampah'] ?? '-') ?></td>
                                <td><?= number_format($r['berat_kg'] ?? 0, 2, ',', '.') ?> kg</td>
                                <td style="color: #ffe885; font-weight: 800;">Rp <?= number_format($r['total_rp'] ?? 0, 0, ',', '.') ?></td>
                                <td>
                                    <span class="badge badge-<?= strtolower(e($r['status'] ?? 'diverifikasi')) ?>">
                                        <?= e($r['status'] ?? 'diverifikasi') ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align: center; color: #8bb2f9; padding: 20px;">Belum ada riwayat transaksi.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>

</body>
</html>