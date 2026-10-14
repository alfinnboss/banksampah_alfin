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

// PROSES TAMBAH SAMPAH
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_sampah'])) {
    $jenis_sampah = trim($_POST['jenis_sampah'] ?? '');
    $harga_per_kg = (float)($_POST['harga_per_kg'] ?? 0);
    $stok_kg      = (float)($_POST['stok_kg'] ?? 0);

    if (empty($jenis_sampah) || $harga_per_kg <= 0) {
        $error = "Nama jenis sampah dan harga per kg wajib diisi!";
    } else {
        $stmt = $conn->prepare("INSERT INTO sampah (jenis_sampah, harga_per_kg, stok_kg) VALUES (?, ?, ?)");
        $stmt->bind_param("sdd", $jenis_sampah, $harga_per_kg, $stok_kg);
        if ($stmt->execute()) {
            $success = "Jenis sampah berhasil ditambahkan!";
        } else {
            $error = "Gagal menambah data: " . $conn->error;
        }
    }
}

// PROSES HAPUS SAMPAH
if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    $stmt = $conn->prepare("DELETE FROM sampah WHERE id_sampah = ?");
    $stmt->bind_param("i", $id_hapus);
    if ($stmt->execute()) {
        $success = "Data sampah berhasil dihapus!";
    } else {
        $error = "Gagal menghapus data! Sampah ini mungkin terikat dengan transaksi.";
    }
}

$data_sampah = $conn->query("SELECT * FROM sampah ORDER BY id_sampah DESC");
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kelola Sampah - Gates of Bank Sampah</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Nunito:wght@600;700;800;900&display=swap">

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: #020b18 radial-gradient(circle at 50% 30%, #0d284f 0%, #030a16 100%);
            color: #fff;
            font-family: 'Nunito', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            padding-bottom: 50px;
        }
        .container { width: 100%; max-width: 1000px; margin: 30px auto; padding: 0 20px; }
        .panel {
            background: linear-gradient(180deg, #061935 0%, #020d1f 100%);
            border: 2px solid #e0a310;
            border-radius: 18px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.8), inset 0 0 15px rgba(224, 163, 16, 0.2);
            padding: 25px;
            margin-bottom: 30px;
        }
        h1 { font-family: 'Cinzel', serif; font-size: 24px; font-weight: 900; color: #ffe885; text-align: center; margin-bottom: 20px; }
        .alert { padding: 12px; border-radius: 8px; font-size: 14px; text-align: center; margin-bottom: 15px; font-weight: bold; }
        .alert-error { background: rgba(231, 76, 60, 0.2); border: 1px solid #e74c3c; color: #ff7b7b; }
        .alert-success { background: rgba(46, 204, 113, 0.2); border: 1px solid #2ecc71; color: #2ecc71; }
        
        .form-grid { display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 10px; align-items: end; }
        .form-group label { display: block; color: #ffe885; font-size: 12px; font-family: 'Cinzel', serif; margin-bottom: 5px; }
        input[type="text"], input[type="number"] {
            width: 100%; padding: 10px; background: #031126; border: 1.5px solid #1a3c6d; border-radius: 8px; color: #fff; font-weight: bold; outline: none;
        }
        .btn-add { background: linear-gradient(180deg, #28a745 0%, #1e7e34 100%); border: 1px solid #2ecc71; color: #fff; font-family: 'Cinzel', serif; padding: 11px 18px; border-radius: 8px; font-weight: 900; cursor: pointer; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 14px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #1a3c6d; }
        th { background: #04142b; color: #ffe885; font-family: 'Cinzel', serif; }
        .btn-danger { background: #e74c3c; color: white; padding: 5px 10px; border-radius: 5px; text-decoration: none; font-size: 12px; font-weight: bold; }
    </style>
</head>
<body>

<?php if (file_exists('nav.php')) include 'nav.php'; ?>

<main class="container">
    <div class="panel">
        <h1>⚙️ KELOLA DATA SAMPAH ⚙️</h1>
        <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

        <form action="" method="POST" class="form-grid">
            <div class="form-group">
                <label>JENIS SAMPAH</label>
                <input type="text" name="jenis_sampah" placeholder="Contoh: Plastik Pet" required>
            </div>
            <div class="form-group">
                <label>HARGA / KG (RP)</label>
                <input type="number" name="harga_per_kg" placeholder="3000" required>
            </div>
            <div class="form-group">
                <label>STOK AWAL (KG)</label>
                <input type="number" step="0.1" name="stok_kg" value="0" required>
            </div>
            <button type="submit" name="tambah_sampah" class="btn-add">+ TAMBAH</button>
        </form>
    </div>

    <div class="panel">
        <h1>📋 DAFTAR STOK SAMPAH BANK</h1>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Jenis Sampah</th>
                        <th>Harga / Kg</th>
                        <th>Stok Tersedia</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($data_sampah && $data_sampah->num_rows > 0): ?>
                        <?php $no = 1; while ($s = $data_sampah->fetch_assoc()): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><strong><?= e($s['jenis_sampah']) ?></strong></td>
                                <td>Rp <?= number_format($s['harga_per_kg'], 0, ',', '.') ?></td>
                                <td><?= number_format($s['stok_kg'], 2, ',', '.') ?> Kg</td>
                                <td>
                                    <a href="?hapus=<?= $s['id_sampah'] ?>" class="btn-danger" onclick="return confirm('Hapus data sampah ini?')">Hapus</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align: center; color: #8bb2f9;">Belum ada data sampah.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

</body>
</html>