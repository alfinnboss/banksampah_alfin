<?php
require 'config.php';
wajib_login();

$id_user = (int)$_SESSION['user']['id_user'];

/*
|--------------------------------------------------------------------------
| AMBIL DATA RIWAYAT TRANSAKSI SETOR USER
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare("
    SELECT 
        t.id_transaksi,
        t.tanggal,
        t.berat_kg,
        t.total_rp,
        t.status,
        s.jenis_sampah,
        s.harga_per_kg
    FROM transaksi t
    JOIN sampah s ON t.id_sampah = s.id_sampah
    WHERE t.id_user = ? AND t.tipe = 'setor'
    ORDER BY t.tanggal DESC
");

$stmt->bind_param("i", $id_user);
$stmt->execute();
$riwayat = $stmt->get_result();
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Riwayat Setoran - Gates of Bank Sampah</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Nunito:wght@600;700;800;900&display=swap">

    <style>
        /* =====================================================
           TEMA GATES OF BANK SAMPAH (DARK / GOLD / ELECTRIC BLUE)
           ===================================================== */
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
            max-width: 1100px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .panel {
            background: linear-gradient(180deg, #061935 0%, #020d1f 100%);
            border: 2px solid #e0a310;
            border-radius: 18px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.8), inset 0 0 15px rgba(224, 163, 16, 0.2);
            padding: 30px;
        }

        h1 {
            font-family: 'Cinzel', serif;
            font-size: 28px;
            font-weight: 900;
            color: #ffe885;
            text-align: center;
            margin-bottom: 8px;
            letter-spacing: 1.5px;
            text-shadow: 0 2px 4px #000;
        }

        p.muted {
            text-align: center;
            color: #8bb2f9;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 25px;
        }

        /* TABEL STYLES */
        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 14px;
        }

        th, td {
            padding: 12px 14px;
            text-align: left;
            border-bottom: 1px solid #1a3c6d;
        }

        th {
            background: #04142b;
            color: #ffe885;
            font-family: 'Cinzel', serif;
            font-weight: 700;
        }

        tr:hover {
            background: rgba(59, 160, 230, 0.08);
        }

        /* BADGES */
        .badge {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            display: inline-block;
        }

        .badge.selesai, .badge.disetujui {
            background: rgba(46, 204, 113, 0.2);
            color: #2ecc71;
            border: 1px solid #2ecc71;
        }

        .badge.pending {
            background: rgba(241, 196, 15, 0.2);
            color: #f1c40f;
            border: 1px solid #f1c40f;
        }

        .badge.ditolak {
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
            border: 1px solid #e74c3c;
        }
    </style>
</head>
<body>

<?php include 'nav.php'; ?>

<main class="container">
    <div class="panel table-wrap">
        <h1>📜 RIWAYAT SETORAN SAMPAH 📜</h1>
        <p class="muted">Daftar transaksi setoran sampah Anda beserta status verifikasinya.</p>

        <?php if ($riwayat && $riwayat->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Jenis Sampah</th>
                        <th>Berat (kg)</th>
                        <th>Harga/kg</th>
                        <th>Total Rp</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    while ($r = $riwayat->fetch_assoc()): 
                    ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= date('d-m-Y H:i', strtotime($r['tanggal'])) ?></td>
                            <td><?= e($r['jenis_sampah']) ?></td>
                            <td><?= number_format($r['berat_kg'], 2, ',', '.') ?> kg</td>
                            <td>Rp <?= number_format($r['harga_per_kg'], 0, ',', '.') ?></td>
                            <td>Rp <?= number_format($r['total_rp'], 0, ',', '.') ?></td>
                            <td>
                                <span class="badge <?= strtolower(e($r['status'])) ?>">
                                    <?= ucfirst(e($r['status'])) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="muted" style="margin-top: 15px;">Belum ada riwayat setoran sampah.</p>
        <?php endif; ?>
    </div>
</main>

</body>
</html>