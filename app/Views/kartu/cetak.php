<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
@page{margin:0}
body{margin:0;font-family:DejaVu Sans,sans-serif}
.card{width:758.25pt;height:478.5pt;box-sizing:border-box;padding:28pt;background:#fafaff;border:1px solid #ddd}
table{width:100%;height:100%;border-collapse:collapse}
.photo{width:125pt;height:165pt;object-fit:cover;border:1px solid #bbb}
.qr{width:150pt;height:150pt}
.title{font-size:12pt;color:#666;text-transform:uppercase}
.name{font-size:24pt;font-weight:bold;margin:8pt 0 16pt}
.info{font-size:11pt;line-height:1.7}
.small{font-size:8pt;color:#666}
</style>
</head>
<body>
<div class="card">
<table><tr>
<td style="width:145pt;vertical-align:middle">
<?php if ($photo_data_uri): ?><img class="photo" src="<?= esc($photo_data_uri) ?>"><?php else: ?><div class="photo"></div><?php endif; ?>
</td>
<td style="vertical-align:middle">
<div class="title">Kartu Pelajar</div>
<div class="name"><?= esc($card['nama']) ?></div>
<div class="info">
<strong>NISN:</strong> <?= esc($card['nisn']) ?><br>
<strong>Kelas:</strong> <?= esc($card['kelas']['nama_kelas'] ?? '-') ?><br>
<strong>Jenis Kelamin:</strong> <?= esc($card['jenis_kelamin']) ?><br>
<strong>TTL:</strong> <?= esc(($card['tempat_lahir'] ?? '-') . ', ' . ($card['tanggal_lahir'] ?? '-')) ?><br><br>
<strong>No. Kartu:</strong> <?= esc($card['nomor_kartu']) ?><br>
<strong>Status:</strong> <?= esc($card['status_aktif']) ?>
</div>
</td>
<td style="width:170pt;text-align:center;vertical-align:middle">
<img class="qr" src="<?= esc($qr_data_uri) ?>"><br>
<div class="small">Scan untuk verifikasi kartu</div>
</td>
</tr></table>
</div>
</body>
</html>
