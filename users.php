<?php
require 'config.php'; wajib_login(); if(!role('admin')){header("Location: dashboard.php");exit;}
$rows=$conn->query("SELECT id_user,nama,username,role,saldo FROM user ORDER BY id_user");
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Data User</title><link rel="stylesheet" href="style.css"></head>
<body><?php include 'nav.php'; ?><main class="container"><h1>Data User</h1><div class="panel table-wrap"><table><tr><th>ID</th><th>Nama</th><th>Username</th><th>Role</th><th>Saldo</th></tr><?php while($r=$rows->fetch_assoc()): ?><tr><td><?=$r['id_user']?></td><td><?=e($r['nama'])?></td><td><?=e($r['username'])?></td><td><?=e($r['role'])?></td><td>Rp <?=number_format($r['saldo'],0,',','.')?></td></tr><?php endwhile; ?></table></div></main></body></html>