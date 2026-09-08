<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <title>Maintenance | SisisFour</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="robots" content="noindex,nofollow" />
  <style>
    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
      background: #f5f5f9;
      color: #444050;
      font-family: Arial, Helvetica, sans-serif;
    }

    .maintenance-card {
      width: 100%;
      max-width: 520px;
      padding: 36px 32px;
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 8px 30px rgba(34, 48, 62, .08);
      text-align: center;
    }

    .maintenance-icon {
      font-size: 52px;
      line-height: 1;
      margin-bottom: 18px;
    }

    h1 {
      margin: 0 0 12px;
      font-size: 26px;
      line-height: 1.25;
    }

    p {
      margin: 0;
      font-size: 15px;
      line-height: 1.65;
      color: #6d6b77;
    }

    .maintenance-code {
      display: inline-block;
      margin-top: 22px;
      padding: 7px 12px;
      border-radius: 999px;
      background: #f0f0f5;
      color: #696cff;
      font-size: 12px;
      font-weight: 700;
      letter-spacing: .04em;
    }
  </style>
</head>
<body>
  <main class="maintenance-card">
    <div class="maintenance-icon" aria-hidden="true">🛠️</div>
    <h1>Sistem Sedang Dalam Pemeliharaan</h1>
    <p>
      <?= esc(
          $message
              ?? 'Sistem sedang dalam pemeliharaan. Silakan coba beberapa saat lagi.'
      ) ?>
    </p>
    <span class="maintenance-code">HTTP 503 · MAINTENANCE</span>
  </main>
</body>
</html>
