<?php
$branding = $loginBranding ?? [];

$namaSekolah = trim(
    (string) (
        $branding['nama_sekolah']
        ?? 'MTsN 4 Jombang'
    )
);

$logoSekolah = trim(
    (string) (
        $branding['logo_sekolah']
        ?? ''
    )
);

$iconSekolah = trim(
    (string) (
        $branding['icon_sekolah']
        ?? ''
    )
);

if ($namaSekolah === '') {
    $namaSekolah = 'MTsN 4 Jombang';
}

$logoPath = $logoSekolah !== ''
    ? FCPATH . ltrim($logoSekolah, '/\\')
    : '';

$iconPath = $iconSekolah !== ''
    ? FCPATH . ltrim($iconSekolah, '/\\')
    : '';

$hasLogo = $logoPath !== ''
    && is_file($logoPath);

$hasIcon = $iconPath !== ''
    && is_file($iconPath);

$logoUrl = $hasLogo
    ? base_url(ltrim($logoSekolah, '/'))
    : null;

$iconUrl = $hasIcon
    ? base_url(ltrim($iconSekolah, '/'))
    : base_url('assets/img/favicon/favicon.ico');
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8" />

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    />

    <meta
        name="theme-color"
        content="#696cff"
    />

    <title>
        Login | SisFour Dev - <?= esc($namaSekolah) ?>
    </title>

    <link
        rel="icon"
        type="image/png"
        href="<?= esc($iconUrl, 'attr') ?>"
    />

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    />

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    />

    <link
        href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    />

    <link
        rel="stylesheet"
        href="<?= base_url('assets/vendor/fonts/iconify-icons.css') ?>"
    />

    <link
        rel="stylesheet"
        href="<?= base_url('assets/vendor/css/core.css') ?>"
    />

    <link
        rel="stylesheet"
        href="<?= base_url('assets/css/demo.css') ?>"
    />

    <style>
        :root {
            --login-primary: #696cff;
            --login-bg: #f5f5f9;
            --login-text: #444050;
            --login-muted: #6d6b77;
            --login-border: rgba(34, 48, 62, .10);
        }

        html,
        body {
            min-height: 100%;
        }

        body {
            min-height: 100vh;
            margin: 0;
            background:
                radial-gradient(
                    circle at top left,
                    rgba(105, 108, 255, .14),
                    transparent 34%
                ),
                radial-gradient(
                    circle at bottom right,
                    rgba(3, 195, 236, .10),
                    transparent 30%
                ),
                var(--login-bg);
            color: var(--login-text);
            font-family: "Public Sans", sans-serif;
        }

        .login-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 20px;
        }

        .login-shell {
            width: 100%;
            max-width: 430px;
        }

        .login-card {
            border: 1px solid var(--login-border);
            border-radius: 20px;
            background: rgba(255, 255, 255, .98);
            box-shadow:
                0 20px 45px rgba(34, 48, 62, .10);
            overflow: hidden;
        }

        .login-card-body {
            padding: 36px 36px 30px;
        }

        .login-logo-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 18px;
        }

        .login-logo {
            width: 92px;
            height: 92px;
            object-fit: contain;
            display: block;
            padding: 4px;
        }

        .login-logo-fallback {
            width: 92px;
            height: 92px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 18px;
            background: rgba(105, 108, 255, .10);
            color: var(--login-primary);
        }

        .login-logo-fallback i {
            font-size: 3rem;
        }

        .login-title {
            margin: 0;
            text-align: center;
            font-size: 1.65rem;
            line-height: 1.25;
            font-weight: 700;
            letter-spacing: -.02em;
        }

        .login-school {
            margin-top: 8px;
            margin-bottom: 0;
            text-align: center;
            color: var(--login-muted);
            font-size: .95rem;
            line-height: 1.5;
        }

        .login-subtitle {
            margin: 6px 0 28px;
            text-align: center;
            color: var(--login-muted);
            font-size: .9rem;
        }

        .form-label {
            font-weight: 600;
            margin-bottom: 7px;
        }

        .form-control,
        .input-group-text {
            min-height: 46px;
        }

        .form-control {
            border-radius: 10px;
        }

        .input-group .form-control {
            border-radius: 10px 0 0 10px;
        }

        .input-group-text {
            border-radius: 0 10px 10px 0;
        }

        .btn-login {
            min-height: 46px;
            border-radius: 10px;
            font-weight: 600;
        }

        .login-help {
            margin: 18px 0 0;
            text-align: center;
            color: var(--login-muted);
            font-size: .82rem;
        }

        .location-note {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-top: 22px;
            padding: 14px 16px;
            border-radius: 12px;
            background: rgba(105, 108, 255, .08);
            color: #555770;
            font-size: .82rem;
            line-height: 1.5;
        }

        .location-note i {
            flex: 0 0 auto;
            margin-top: 1px;
            font-size: 1.15rem;
            color: var(--login-primary);
        }

        .login-footer {
            padding: 18px 20px 0;
            text-align: center;
            color: var(--login-muted);
            font-size: .78rem;
        }

        @media (max-width: 575.98px) {
            .login-page {
                align-items: flex-start;
                padding: 18px 14px 24px;
            }

            .login-shell {
                max-width: 100%;
            }

            .login-card {
                border-radius: 16px;
            }

            .login-card-body {
                padding: 26px 20px 24px;
            }

            .login-logo {
                width: 78px;
                height: 78px;
            }

            .login-logo-fallback {
                width: 78px;
                height: 78px;
            }

            .login-logo-fallback i {
                font-size: 2.6rem;
            }

            .login-title {
                font-size: 1.45rem;
            }

            .login-subtitle {
                margin-bottom: 22px;
            }
        }

        @media (max-height: 700px) and (min-width: 576px) {
            .login-page {
                align-items: flex-start;
                padding-top: 24px;
                padding-bottom: 24px;
            }
        }
    </style>

    <script src="<?= base_url('assets/vendor/js/helpers.js') ?>"></script>
    <script src="<?= base_url('assets/js/config.js') ?>"></script>
</head>

<body>
<main class="login-page">
    <div class="login-shell">
        <section
            class="login-card"
            aria-labelledby="loginTitle"
        >
            <div class="login-card-body">
                <div class="login-logo-wrap">
                    <?php if ($logoUrl !== null): ?>
                        <img
                            src="<?= esc($logoUrl, 'attr') ?>"
                            alt="Logo <?= esc($namaSekolah, 'attr') ?>"
                            class="login-logo"
                        />
                    <?php else: ?>
                        <div
                            class="login-logo-fallback"
                            aria-label="Logo sekolah belum diatur"
                            title="Upload Logo Sekolah melalui Setting Sistem"
                        >
                            <i class="bx bx-buildings"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <h1
                    id="loginTitle"
                    class="login-title"
                >
                    SisFour Dev
                </h1>

                <p class="login-school">
                    <?= esc($namaSekolah) ?>
                </p>

                <p class="login-subtitle">
                    Silakan masuk menggunakan akun Anda
                </p>

                <?php if (session()->getFlashdata('error')): ?>
                    <div
                        class="alert alert-danger alert-dismissible"
                        role="alert"
                    >
                        <?= esc(
                            (string) session()->getFlashdata('error')
                        ) ?>

                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="alert"
                            aria-label="Tutup"
                        ></button>
                    </div>
                <?php endif; ?>

                <form
                    id="formAuthentication"
                    action="<?= base_url('auth/login') ?>"
                    method="post"
                    autocomplete="on"
                >
                    <?= csrf_field() ?>

                    <div class="mb-4">
                        <label
                            for="username"
                            class="form-label"
                        >
                            Username
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="username"
                            name="username"
                            placeholder="NIP / NISN / username"
                            value="<?= esc(
                                (string) session()->getFlashdata(
                                    'login_username'
                                )
                            ) ?>"
                            autocomplete="username"
                            autofocus
                            required
                        />
                    </div>

                    <div class="mb-4 form-password-toggle">
                        <label
                            class="form-label"
                            for="password"
                        >
                            Password
                        </label>

                        <div class="input-group input-group-merge">
                            <input
                                type="password"
                                id="password"
                                class="form-control"
                                name="password"
                                placeholder="••••••••••••"
                                autocomplete="current-password"
                                required
                            />

                            <span
                                class="input-group-text cursor-pointer"
                                aria-label="Tampilkan atau sembunyikan password"
                            >
                                <i class="icon-base bx bx-hide"></i>
                            </span>
                        </div>
                    </div>

                    <button
                        class="btn btn-primary btn-login d-grid w-100"
                        type="submit"
                    >
                        Login
                    </button>
                </form>

                <p class="login-help">
                    Lupa password? Hubungi Admin madrasah.
                </p>

                <div class="location-note">
                    <i class="bx bx-current-location"></i>
                    <span>
                        Aktifkan lokasi di perangkat saat menggunakan aplikasi
                    </span>
                </div>
            </div>
        </section>

        <div class="login-footer">
            © <?= date('Y') ?> SisFour Dev
        </div>
    </div>
</main>

<script src="<?= base_url('assets/vendor/libs/jquery/jquery.js') ?>"></script>
<script src="<?= base_url('assets/vendor/libs/popper/popper.js') ?>"></script>
<script src="<?= base_url('assets/vendor/js/bootstrap.js') ?>"></script>
<script src="<?= base_url('assets/vendor/js/main.js') ?>"></script>
</body>
</html>
