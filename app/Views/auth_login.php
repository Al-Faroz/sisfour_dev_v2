<!doctype html>
<html lang="id" class="layout-wide">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
  <title>Login | SisisFour - MTsN 4 Jombang</title>

  <link rel="icon" type="image/x-icon" href="<?= base_url('assets/img/favicon/favicon.ico') ?>" />
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="<?= base_url('assets/vendor/fonts/iconify-icons.css') ?>" />
  <link rel="stylesheet" href="<?= base_url('assets/vendor/css/core.css') ?>" />
  <link rel="stylesheet" href="<?= base_url('assets/css/demo.css') ?>" />
  <script src="<?= base_url('assets/vendor/js/helpers.js') ?>"></script>
  <script src="<?= base_url('assets/js/config.js') ?>"></script>
</head>
<body>
  <div class="container-xxl">
    <div class="authentication-wrapper authentication-basic container-p-y">
      <div class="authentication-inner">

        <div class="card">
          <div class="card-body">

            <div class="app-brand justify-content-center mb-6 mt-2">
              <a href="<?= base_url('/') ?>" class="app-brand-link gap-2">
                <i class="bx bx-buildings bx-lg text-primary"></i>
                <span class="app-brand-text demo text-heading fw-bold">SisisFour</span>
              </a>
            </div>

            <h4 class="mb-1 text-center">MTsN 4 Jombang 🏫</h4>
            <p class="mb-6 text-center">Silakan login menggunakan akun Anda</p>

            <?php if (session()->getFlashdata('error')): ?>
              <div class="alert alert-danger" role="alert">
                <?= esc(session()->getFlashdata('error')) ?>
              </div>
            <?php endif; ?>

            <form id="formAuthentication" class="mb-6" action="<?= base_url('auth/login') ?>" method="post">
              <?= csrf_field() ?>

              <div class="mb-6">
                <label for="username" class="form-label">Username</label>
                <input
                  type="text"
                  class="form-control"
                  id="username"
                  name="username"
                  placeholder="NIP / NISN / username"
                  value="<?= esc(old('username')) ?>"
                  autofocus
                  required />
              </div>

              <div class="mb-6 form-password-toggle">
                <label class="form-label" for="password">Password</label>
                <div class="input-group input-group-merge">
                  <input
                    type="password"
                    id="password"
                    class="form-control"
                    name="password"
                    placeholder="············"
                    required />
                  <span class="input-group-text cursor-pointer"><i class="icon-base bx bx-hide"></i></span>
                </div>
              </div>

              <div class="mb-8">
                <button class="btn btn-primary d-grid w-100" type="submit">Login</button>
              </div>
            </form>

            <p class="text-center small text-muted mb-0">
              Lupa password? Hubungi Admin madrasah.
            </p>

          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="<?= base_url('assets/vendor/libs/jquery/jquery.js') ?>"></script>
  <script src="<?= base_url('assets/vendor/libs/popper/popper.js') ?>"></script>
  <script src="<?= base_url('assets/vendor/js/bootstrap.js') ?>"></script>
  <script src="<?= base_url('assets/vendor/js/main.js') ?>"></script>
</body>
</html>
