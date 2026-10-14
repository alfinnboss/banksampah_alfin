<?php
require 'config.php';
wajib_login();

$id_user = (int)$_SESSION['user']['id_user'];
$error   = '';
$success = '';

// PROSES SUBMIT SETORAN SAMPAH
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_setor'])) {
    $id_sampah = (int)($_POST['id_sampah'] ?? 0);
    $berat_kg  = (float)($_POST['berat_kg'] ?? 0);

    if ($id_sampah <= 0 || $berat_kg <= 0) {
        $error = "Pilih jenis sampah dan masukkan berat yang valid!";
    } else {
        // Ambil data harga sampah
        $stmt = $conn->prepare("SELECT harga_per_kg FROM sampah WHERE id_sampah = ?");
        $stmt->bind_param("i", $id_sampah);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();

        if (!$res) {
            $error = "Jenis sampah tidak ditemukan!";
        } else {
            $harga_per_kg = (float)$res['harga_per_kg'];
            $total_rp     = $harga_per_kg * $berat_kg;

            // Simpan transaksi setoran
            $stmt_insert = $conn->prepare("
                INSERT INTO transaksi (id_user, id_sampah, tipe, berat_kg, total_rp, status, tanggal) 
                VALUES (?, ?, 'setor', ?, ?, 'pending', NOW())
            ");
            $stmt_insert->bind_param("iidd", $id_user, $id_sampah, $berat_kg, $total_rp);

            if ($stmt_insert->execute()) {
                $success = "Setoran sampah berhasil dikirim! Menunggu verifikasi admin.";
            } else {
                $error = "Gagal memproses setoran: " . $conn->error;
            }
        }
    }
}

// AMBIL DAFTAR SAMPAH UNTUK OPTION FORM
$query_sampah = "SELECT * FROM sampah ORDER BY jenis_sampah ASC";
$data_sampah  = $conn->query($query_sampah);
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Setor Sampah - Gates of Bank Sampah</title>
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
            max-width: 650px;
            margin: 40px auto;
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

        /* ALERT */
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

        /* FORM FIELD */
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
            letter-spacing: 0.5px;
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
            color: #2ecc71;
            font-size: 26px;
            font-weight: 900;
            margin-top: 4px;
            text-shadow: 0 0 10px rgba(46, 204, 113, 0.4);
        }

        /* BUTTON */
        .btn-submit {
            width: 100%;
            background: linear-gradient(180deg, #00c6ff 0%, #0072ff 100%);
            border: 1px solid #38cbec;
            color: #ffffff;
            font-family: 'Cinzel', serif;
            font-size: 16px;
            font-weight: 900;
            padding: 14px;
            border-radius: 10px;
            cursor: pointer;
            letter-spacing: 1px;
            transition: all 0.2s ease;
            box-shadow: 0 4px 15px rgba(0, 114, 255, 0.4);
        }

        .btn-submit:hover {
            filter: brightness(1.2);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 114, 255, 0.6);
        }
    </style>
</head>
<body>

<?php include 'nav.php'; ?>

<main class="container">
    <div class="panel">
        <h1>♻️ SETOR SAMPAH ♻️</h1>
        <p class="subtitle">Setor sampah Anda untuk dikukuhkan menjadi saldo rupiah.</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <!-- PILIH JENIS SAMPAH -->
            <div class="form-group">
                <label for="id_sampah">JENIS SAMPAH</label>
                <select name="id_sampah" id="id_sampah" required onchange="updateKalkulasi()">
                    <option value="" data-harga="0" data-stok="0">-- Pilih Jenis Sampah --</option>
                    <?php while ($s = $data_sampah->fetch_assoc()): ?>
                        <option value="<?= $s['id_sampah'] ?>" 
                                data-harga="<?= $s['harga_per_kg'] ?>" 
                                data-stok="<?= $s['stok_kg'] ?>">
                            <?= e($s['jenis_sampah']) ?> (Rp <?= number_format($s['harga_per_kg'], 0, ',', '.') ?> / kg)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- INFO HARGA & STOK -->
            <div class="info-grid">
                <div class="info-card">
                    <span>Harga per Kg</span>
                    <strong id="display_harga">Rp 0</strong>
                </div>
                <div class="info-card">
                    <span>Stok Terkumpul</span>
                    <strong id="display_stok">0 KG</strong>
                </div>
            </div>

            <!-- INPUT BERAT SAMPAH -->
            <div class="form-group">
                <label for="berat_kg">BERAT SAMPAH (KG)</label>
                <input type="number" step="0.1" min="0.1" name="berat_kg" id="berat_kg" placeholder="Contoh: 2.5" required oninput="updateKalkulasi()">
            </div>

            <!-- TOTAL ESTIMASI SALDO -->
            <div class="total-box">
                <span>Total Estimasi Saldo</span>
                <h2 id="display_total">Rp 0</h2>
            </div>

            <!-- SUBMIT BUTTON -->
            <button type="submit" name="submit_setor" class="btn-submit">
                ⚡ KIRIM SETORAN ⚡
            </button>
        </form>
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

    // Update Tampilan Text
    document.getElementById('display_harga').innerText = 'Rp ' + harga.toLocaleString('id-ID');
    document.getElementById('display_stok').innerText  = stok.toLocaleString('id-ID') + ' KG';
    document.getElementById('display_total').innerText = 'Rp ' + total.toLocaleString('id-ID');
}
</script>

</body>
</html>