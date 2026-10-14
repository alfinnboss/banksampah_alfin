<?php
require 'config.php'; wajib_login();
$uid=(int)$_SESSION['user']['id_user']; $msg="";
if($_SERVER['REQUEST_METHOD']==='POST'){
    $nominal=(float)$_POST['nominal'];
    if($nominal<=0) $msg="Nominal tidak valid.";
    else {
        $stmt=$conn->prepare("SELECT saldo FROM user WHERE id_user=?"); $stmt->bind_param("i",$uid); $stmt->execute();
        $saldo=(float)$stmt->get_result()->fetch_assoc()['saldo'];
        if($nominal>$saldo) $msg="Saldo tidak mencukupi.";
        else {
            $stmt=$conn->prepare("INSERT INTO tarik_saldo(id_user,nominal) VALUES(?,?)");
            $stmt->bind_param("id",$uid,$nominal); $stmt->execute();
            $msg="Permintaan penarikan berhasil dikirim.";
        }
    }
}
$stmt=$conn->prepare("SELECT * FROM tarik_saldo WHERE id_user=? ORDER BY tanggal DESC"); $stmt->bind_param("i",$uid); $stmt->execute(); $rows=$stmt->get_result();
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Tarik Saldo</title><link rel="stylesheet" href="style.css"></head>
<body><?php include 'nav.php'; ?><main class="container"><div class="panel narrow"><h1>Tarik Saldo</h1><p>Saldo saat ini: <b>Rp <?=number_format($_SESSION['user']['saldo'],0,',','.')?></b></p>
<?php if($msg): ?><div class="alert"><?=e($msg)?></div><?php endif; ?><form method="post"><label>Nominal</label><input type="number" name="nominal" min="1" step="1000" required><button class="btn primary full">Ajukan Penarikan</button></form></div>
<div class="panel table-wrap"><h2>Riwayat Penarikan</h2><table><tr><th>Tanggal</th><th>Nominal</th><th>Status</th></tr><?php while($r=$rows->fetch_assoc()): ?><tr><td><?=e($r['tanggal'])?></td><td>Rp <?=number_format($r['nominal'],0,',','.')?></td><td><?=e($r['status'])?></td></tr><?php endwhile; ?></table></div>
</main></body></html>