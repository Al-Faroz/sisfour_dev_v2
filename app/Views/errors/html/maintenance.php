<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <title>Maintenance | SisisFour</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <style>
    body{font-family:sans-serif;background:#f5f5f9;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;text-align:center;color:#444}
    .box{max-width:420px;padding:2rem}
    i{font-size:3rem;color:#696cff}
  </style>
</head>
<body>
  <div class="box">
    <h2>🛠️ Sedang Pemeliharaan</h2>
    <p><?= esc($message ?? 'Sistem sedang dalam pemeliharaan. Silakan coba beberapa saat lagi.') ?></p>
  </div>
</body>
</html>
