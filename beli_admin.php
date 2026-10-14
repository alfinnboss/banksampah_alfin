<?php
require 'config.php';
wajib_login();

// Pastikan hanya role pengepul yang bisa mengakses
if (strtolower($_SESSION['user']['role']) !== 'pengepul') {
    header("Location: dashboard.php");
    exit;
}

$id_pengepul = (int)$_SESSION['user']['id_user'];
$error       = '';
$success     = '';

// 1. PROSES SUBMIT PEMBELIAN SAMPAH OLEH PENGEPUL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_beli'])) {
    $id_sampah = (int)($_POST['id_sampah'] ?? 0);
    $berat_kg  = (float)($_POST['berat_kg'] ?? 0);

    if ($id_sampah <= 0 || $berat_kg <= 0) {
        $error = "Pilih jenis sampah dan masukkan jumlah berat yang valid!";
    } else {
        // Cek data dan stok sampah yang tersedia
        $stmt_check = $conn->prepare("SELECT * FROM sampah WHERE id_sampah = ?");
        $stmt_check->bind_param("i", $id_sampah);
        $stmt_check->execute();
        $sampah = $stmt_check->get_result()->fetch_assoc();

        if (!$sampah) {
            $error = "Jenis sampah tidak ditemukan!";
        } elseif ($berat_kg > $sampah['stok_kg']) {
            $error = "Stok sampah tidak mencukupi! Stok tersedia: " . $sampah['stok_kg'] . " kg";
        } else {
            $harga_per_kg = (float)$sampah['harga_per_kg'];
            $total_rp     = $harga_per_kg * $berat_kg;

            // Ambil ID admin (default ambil admin pertama)
            $res_admin = $conn->query("SELECT id_user FROM user WHERE role = 'admin' LIMIT 1");
            $admin = $res_admin ? $res_admin->fetch_assoc() : null;
            $id_admin = $admin ? (int)$admin['id_user'] : 1;

            $conn->begin_transaction();
            try {
                // Insert ke tabel penjualan_pengepul (atau transaksi)
                $stmt_ins = $conn->prepare("
                    INSERT INTO penjualan_pengepul (id_admin, id_pengepul, id_sampah, berat_kg, harga_per_kg, total_rp, status, tanggal)
                    VALUES (?, ?, ?, ?, ?, ?, 'selesai', NOW())
                ");
                $stmt_ins->bind_param("iiiddd", $id_admin, $id_pengepul, $id_sampah, $berat_kg, $harga_per_kg, $total_rp);
                $stmt_ins->execute();

                // Kurangi stok sampah di database
                $stmt_upd = $conn->prepare("UPDATE sampah SET stok_kg = stok_kg - ? WHERE id_sampah = ?");
                $stmt_upd->bind_param("di", $berat_kg, $id_sampah);
                $stmt_upd->execute();

                $conn->commit();
                $success = "Transaksi pembelian sampah berhasil!";
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Gagal memproses transaksi: " . $e->getMessage();
            }
        }
    }
}

// 2. AMBIL DAFTAR SAMPAH BERSTOK > 0
$data_sampah = $conn->query("SELECT * FROM sampah WHERE stok_kg > 0 ORDER BY jenis_sampah ASC");

// 3. AMBIL RIWAYAT PEMBELIAN PENGEPUL INI
$stmt_hist = $conn->prepare("
    SELECT p.*, s.jenis_sampah 
    FROM penjualan_pengepul p 
    JOIN sampah s ON p.id_sampah = s.id_sampah 
    WHERE p.id_pengepul = ? 
    ORDER BY p.tanggal DESC
");
$stmt_hist->bind_param("i", $id_pengepul);
$stmt_hist->execute();
$riwayat_beli = $stmt_hist->get_result();
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Beli Sampah - Gates of Bank Sampah</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Nunito:wght@600;700;800;900&display=swap">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background: #020b18 radial-gradient(circle at 50% 30%, #0d284f 0%, #030a16 100%);
            color: #fff;
            font-family: 'Nunito', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            padding-bottom: 50px;
        }

        .container {
            width: 100%;
            max-width: 900px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .panel {
            background: linear-gradient(180deg, #061935 0%, #020d1f 100%);
            border: 2px solid #e0a310;
            border-radius: 18px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.8), inset 0 0 15px rgba(224, 163, 16, 0.2);
            padding: 30px;
            margin-bottom: 30px;
        }

        h1 {
            font-family: 'Cinzel', serif;
            font-size: 26px;
            font-weight: 900;
            color: #ffe885;
            text-align: center;
            margin-bottom: 6px;
            letter-spacing: 1.5px;
            text-shadow: 0 2px 4px #000;
        }

        p.subtitle {
            text-align: center;
            color: #8bb2f9;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 25px;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 20px;
            text-align: center;
        }
        .alert-error {
            background: rgba(231, 76, 60, 0.2);
            border: 1px solid #e74c3c;
            color: #ff7b7b;
        }
        .alert-success {
            background: rgba(46, 204, 113, 0.2);
            border: 1px solid #2ecc71;
            color: #2ecc71;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            color: #ffe885;
            font-family: 'Cinzel', serif;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        input[type="number"], select {
            width: 100%;
            padding: 12px 14px;
            background: #031126;
            border: 1.5px solid #1a3c6d;
            border-radius: 10px;
            color: #ffffff;
            font-size: 15px;
            font-weight: 700;
            outline: none;
            transition: all 0.2s ease;
        }

        input[type="number"]:focus, select:focus {
            border-color: #e0a310;
            box-shadow: 0 0 10px rgba(224, 163, 16, 0.4);
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 18px;
        }

        .info-card {
            background: rgba(3, 17, 38, 0.8);
            border: 1px dashed #1a3c6d;
            border-radius: 10px;
            padding: 12px;
            text-align: center;
        }

        .info-card span {
            display: block;
            font-size: 11px;
            color: #8bb2f9;
            font-weight: 700;
            text-transform: uppercase;
        }

        .info-card strong {
            display: block;
            font-size: 16px;
            color: #ffffff;
            margin-top: 4px;
        }

        .total-box {
            background: linear-gradient(180deg, #092852 0%, #03142e 100%);
            border: 1.5px solid #e0a310;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            margin-bottom: 25px;
            box-shadow: inset 0 0 10px rgba(224, 163, 16, 0.2);
        }

        .total-box span {
            color: #8bb2f9;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .total-box h2 {
            color: #ffe885;
            font-size: 26px;
            font-weight: 900;
            margin-top: 4px;
            text-shadow: 0 0 10px rgba(224, 163, 16, 0.4);
        }

        .btn-submit {
            width: 100%;
            background: linear-gradient(180deg, #28a745 0%, #1e7e34 100%);
            border: 1px solid #2ecc71;
            color: #ffffff;
            font-family: 'Cinzel', serif;
            font-size: 16px;
            font-weight: 900;
            padding: 14px;
            border-radius: 10px;
            cursor: pointer;
            letter-spacing: 1px;
            transition: all 0.2s ease;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
        }

        .btn-submit:hover {
            filter: brightness(1.2);
            transform: translateY(-2px);
        }

        /* TABEL RIWAYAT */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 14px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #1a3c6d;
        }

        th {
            background: #04142b;
            color: #ffe885;
            font-family: 'Cinzel', serif;
        }

        .badge-status {
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 800;
            background: rgba(46, 204, 113, 0.2);
            color: #2ecc71;
            border: 1px solid #2ecc71;
            text-transform: uppercase;
        }
    </style>
</head>
<body>

<?php include 'nav.php'; ?>

<main class="container">
    <!-- FORM BELI SAMPAH -->
    <div class="panel">
        <h1>📦 BELI SAMPAH DARI BANK 📦</h1>
        <p class="subtitle">Beli stok sampah yang telah dikumpulkan oleh Bank Sampah.</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label for="id_sampah">PILIH SAMPAH DARI STOK BANK</label>
                <select name="id_sampah" id="id_sampah" required onchange="updateKalkulasi()">
                    <option value="" data-harga="0" data-stok="0">-- Pilih Jenis Sampah --</option>
                    <?php if ($data_sampah && $data_sampah->num_rows > 0): ?>
                        <?php while ($s = $data_sampah->fetch_assoc()): ?>
                            <option value="<?= $s['id_sampah'] ?>" 
                                    data-harga="<?= $s['harga_per_kg'] ?>" 
                                    data-stok="<?= $s['stok_kg'] ?>">
                                <?= e($s['jenis_sampah']) ?> (Stok: <?= $s['stok_kg'] ?> kg)
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="info-grid">
                <div class="info-card">
                    <span>Harga per Kg</span>
                    <strong id="display_harga">Rp 0</strong>
                </div>
                <div class="info-card">
                    <span>Stok Tersedia</span>
                    <strong id="display_stok">0 KG</strong>
                </div>
            </div>

            <div class="form-group">
                <label for="berat_kg">BERAT DIBELI (KG)</label>
                <input type="number" step="0.1" min="0.1" name="berat_kg" id="berat_kg" placeholder="Contoh: 10.0" required oninput="updateKalkulasi()">
            </div>

            <div class="total-box">
                <span>Total Pembayaran Pembelian</span>
                <h2 id="display_total">Rp 0</h2>
            </div>

            <button type="submit" name="submit_beli" class="btn-submit">
                🛒 PROSES PEMBELIAN SAMPAH
            </button>
        </form>
    </div>

    <!-- RIWAYAT PEMBELIAN -->
    <div class="panel">
        <h1>📜 RIWAYAT PEMBELIAN PENGEPUL</h1>
        <?php if ($riwayat_beli && $riwayat_beli->num_rows > 0): ?>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Jenis Sampah</th>
                            <th>Jumlah (Kg)</th>
                            <th>Total Rp</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; while ($r = $riwayat_beli->fetch_assoc()): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= date('d-m-Y H:i', strtotime($r['tanggal'])) ?></td>
                                <td><?= e($r['jenis_sampah']) ?></td>
                                <td><?= number_format($r['berat_kg'], 2, ',', '.') ?> kg</td>
                                <td>Rp <?= number_format($r['total_rp'], 0, ',', '.') ?></td>
                                <td><span class="badge-status"><?= e($r['status']) ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="subtitle" style="margin-top: 15px;">Belum ada transaksi pembelian sampah.</p>
        <?php endif; ?>
    </div>
</main>

<script>
function updateKalkulasi() {
    const select = document.getElementById('id_sampah');
    const selectedOption = select.options[select.selectedIndex];
    
    const harga = parseFloat(selectedOption.getAttribute('data-harga')) || 0;
    const stok  = parseFloat(selectedOption.getAttribute('data-stok')) || 0;
    const berat = parseFloat(document.getElementById('berat_kg').value) || 0;

    const total = harga * berat;

    document.getElementById('display_harga').innerText = 'Rp ' + harga.toLocaleString('id-ID');
    document.getElementById('display_stok').innerText  = stok.toLocaleString('id-ID') + ' KG';
    document.getElementById('display_total').innerText = 'Rp ' + total.toLocaleString('id-ID');
}
</script>

</body>
</html>