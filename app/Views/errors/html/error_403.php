<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>403 — Akses Ditolak</title>
</head>
<body style="font-family: sans-serif; text-align:center; padding-top: 80px;">
    <h1 style="font-size:64px; margin:0;">403</h1>
    <p><?= esc($message ?? 'Anda tidak memiliki akses untuk halaman ini.') ?></p>
    <a href="<?= base_url('dashboard') ?>">&larr; Kembali ke Dashboard</a>
</body>
</html>
