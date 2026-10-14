<?php
require 'config.php';

if (isset($_SESSION['user'])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT id_user,nama,username,password,role,saldo FROM user WHERE username=? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    if ($data && hash_equals($data['password'], $password)) {
        unset($data['password']);
        $_SESSION['user'] = $data;
        header("Location: dashboard.php");
        exit;
    }
    $error = "Username atau password salah.";
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Gates of Bank Sampah - Login</title>
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Nunito:wght@600;700;800;900&display=swap">
<style>
/* TEMA GATES OF BANK SAMPAH (INDEX / LOGIN) */
body.masuk-gates-page {
  margin: 0; 
  min-height: 100vh;
  display: grid; 
  grid-template-columns: 1.1fr 1fr;
  background: #020b18 radial-gradient(circle at 50% 30%, #0d284f 0%, #030a16 100%);
  font-family: 'Nunito', system-ui, sans-serif;
  color: #fff;
}

/* PANEL KIRI (HEADER / VISUAL) */
.masuk-kiri {
  position: relative; 
  overflow: hidden;
  display: flex; 
  flex-direction: column; 
  align-items: center; 
  justify-content: center;
  padding: 48px 36px; 
  text-align: center;
  border-right: 3px solid #f2c94c;
  box-shadow: 6px 0 25px rgba(0,0,0,0.8);
  background:
    radial-gradient(circle at 50% 40%, rgba(26, 115, 232, 0.15) 0%, transparent 70%),
    linear-gradient(180deg, #07172e 0%, #020a14 100%);
}

.gates-title-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-bottom: 16px;
}

.gates-title-top {
  font-family: 'Cinzel', serif;
  font-size: 28px;
  font-weight: 900;
  letter-spacing: 4px;
  color: #8bb2f9;
  text-shadow: 0 0 10px rgba(139, 178, 249, 0.5);
  margin-bottom: -5px;
}

.gates-title-main {
  font-family: 'Cinzel', serif;
  font-size: 52px;
  font-weight: 900;
  line-height: 1.1;
  letter-spacing: 2px;
  background: linear-gradient(180deg, #ffffff 0%, #ffe885 40%, #e0a310 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  filter: drop-shadow(0 4px 6px #000);
}

.kiri-teks {
  margin: 18px 0 28px;
  max-width: 380px;
  font-weight: 700;
  font-size: 16px;
  color: #b5d3ff;
}

/* BADGE MINI / UBIN INFO */
.ubin-baris {
  display: flex;
  gap: 14px;
  justify-content: center;
  flex-wrap: wrap;
}

.ubin-mini {
  width: 90px;
  padding: 10px 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  font-weight: 800;
  font-size: 13px;
  color: #fff;
  background: linear-gradient(180deg, #092c5a 0%, #03152d 100%);
  border: 1.5 solid #3ba0e6;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.5), inset 0 0 8px rgba(59, 160, 230, 0.3);
}

.ubin-mini span {
  font-size: 20px;
}

/* PANEL KANAN (FORM LOGIN) */
.masuk-kanan {
  display: grid;
  place-items: center;
  padding: 32px 24px;
  background: radial-gradient(circle at 50% 50%, #0a2142 0%, #020a16 100%);
}

.form-kotak {
  width: min(380px, 100%);
  background: linear-gradient(180deg, #061935 0%, #020d1f 100%);
  border: 2px solid #e0a310;
  border-radius: 18px;
  padding: 28px 24px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.8), inset 0 0 15px rgba(224, 163, 16, 0.2);
}

.form-kotak h1 {
  font-family: 'Cinzel', serif;
  font-size: 32px;
  font-weight: 900;
  text-align: center;
  color: #ffe885;
  margin: 0 0 4px;
  letter-spacing: 2px;
  text-shadow: 0 2px 4px #000;
}

.form-kotak .muted {
  text-align: center;
  color: #8bb2f9;
  margin: 0 0 20px;
  font-size: 14px;
  font-weight: 700;
}

.form-group {
  margin-bottom: 16px;
}

.form-group label {
  display: block;
  font-weight: 800;
  margin-bottom: 6px;
  color: #ffe885;
  font-size: 14px;
}

.form-input {
  width: 100%;
  padding: 12px;
  background: #031024;
  border: 2px solid #3ba0e6;
  border-radius: 10px;
  font-size: 15px;
  color: #fff;
  font-weight: 700;
  box-sizing: border-box;
  outline: none;
  transition: all 0.2s;
}

.form-input:focus {
  border-color: #ffe885;
  box-shadow: 0 0 8px rgba(255, 232, 133, 0.4);
}

.btn-masuk {
  width: 100%;
  padding: 12px;
  margin-top: 8px;
  background: linear-gradient(180deg, #38cbec 0%, #0087a2 100%);
  border: 2px solid #fff;
  border-radius: 10px;
  color: #fff;
  font-family: 'Cinzel', serif;
  font-weight: 900;
  font-size: 18px;
  cursor: pointer;
  letter-spacing: 1px;
  box-shadow: 0 4px 10px rgba(0, 135, 162, 0.5);
  transition: transform 0.1s, filter 0.2s;
}

.btn-masuk:hover {
  filter: brightness(1.15);
  transform: translateY(-2px);
}

.demo-box {
  margin-top: 20px;
  padding: 12px;
  background: rgba(3, 16, 36, 0.8);
  border: 1px dashed #e0a310;
  border-radius: 10px;
  color: #b5d3ff;
  font-size: 13px;
  line-height: 1.5;
}

.demo-box b {
  color: #ffe885;
}

@media(max-width:820px) {
  body.masuk-gates-page { grid-template-columns: 1fr; }
  .masuk-kiri { 
    padding: 30px 20px; 
    border-right: none; 
    border-bottom: 3px solid #f2c94c; 
  }
  .gates-title-top { font-size: 20px; }
  .gates-title-main { font-size: 36px; }
}
</style>
</head>
<body class="masuk-gates-page">

<!-- PANEL KIRI (Visual Header Style) -->
<section class="masuk-kiri">
    <div class="gates-title-box">
      <div class="gates-title-top">GATES OF</div>
      <div class="gates-title-main">BANK SAMPAH</div>
    </div>
    
    <p class="kiri-teks">Setor sampah, kumpulkan saldo, dan tingkatkan multiplier transaksi Anda!</p>
    
    <div class="ubin-baris">
        <div class="ubin-mini"><span>⚡</span> Setor</div>
        <div class="ubin-mini"><span>💰</span> Saldo</div>
        <div class="ubin-mini"><span>🏛️</span> Tarik</div>
    </div>
</section>

<!-- PANEL KANAN (Form Login) -->
<section class="masuk-kanan">
    <div class="form-kotak">
        <h1>MASUK</h1>
        <p class="muted">Sistem Pengelolaan Bank Sampah</p>
        
        <?php if ($error): ?>
          <div style="background: rgba(211, 47, 47, 0.2); color: #ff8a8a; padding: 10px; border-radius: 8px; margin-bottom: 14px; font-weight: 700; border: 1px solid #d32f2f; text-align: center; font-size: 14px;">
            <?=e($error)?>
          </div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
              <label>Username</label>
              <input type="text" name="username" class="form-input" required autofocus placeholder="Masukkan username">
            </div>

            <div class="form-group">
              <label>Password</label>
              <input type="password" name="password" class="form-input" required placeholder="Masukkan password">
            </div>

            <button type="submit" class="btn-masuk">MASUK SEKARANG</button>
        </form>

        <div class="demo-box">
            <b>Akun Demo:</b><br>
            • Admin: admin / admin123<br>
            • Siswa: siswa / siswa123<br>
            • Pengepul: pengepul / pengepul123
        </div>
    </div>
</section>

</body>
</html>