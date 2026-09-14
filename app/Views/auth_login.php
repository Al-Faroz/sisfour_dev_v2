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

$logoVersion = $hasLogo ? @filemtime($logoPath) : false;
$iconVersion = $hasIcon ? @filemtime($iconPath) : false;

$logoUrl = $hasLogo
    ? base_url(ltrim($logoSekolah, '/'))
        . ($logoVersion ? '?v=' . $logoVersion : '')
    : null;

$iconUrl = $hasIcon
    ? base_url(ltrim($iconSekolah, '/'))
        . ($iconVersion ? '?v=' . $iconVersion : '')
    : base_url('assets/img/favicon/favicon.ico');

$iconType = $hasIcon ? 'image/png' : 'image/x-icon';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8" />

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0, viewport-fit=cover"
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
        type="<?= esc($iconType, 'attr') ?>"
        href="<?= esc($iconUrl, 'attr') ?>"
    />

    <?php if ($hasIcon): ?>
        <link
            rel="apple-touch-icon"
            href="<?= esc($iconUrl, 'attr') ?>"
        />
    <?php endif; ?>

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

        .password-toggle-button {
            min-width: 46px;
            justify-content: center;
            color: var(--login-muted);
            background: transparent;
        }

        .password-toggle-button:focus-visible {
            outline: 2px solid var(--login-primary);
            outline-offset: 2px;
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

                    <div class="mb-4">
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

                            <button
                                type="button"
                                class="input-group-text cursor-pointer password-toggle-button"
                                id="togglePassword"
                                aria-label="Tampilkan password"
                                aria-controls="password"
                                aria-pressed="false"
                            >
                                <i class="icon-base bx bx-hide" aria-hidden="true"></i>
                            </button>
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
<script>
(() => {
    'use strict';

    const password = document.getElementById('password');
    const toggle = document.getElementById('togglePassword');
    const icon = toggle?.querySelector('i');

    if (!password || !toggle || !icon) {
        return;
    }

    toggle.addEventListener('click', () => {
        const showing = password.type === 'text';
        password.type = showing ? 'password' : 'text';

        const visible = password.type === 'text';
        toggle.setAttribute('aria-pressed', visible ? 'true' : 'false');
        toggle.setAttribute(
            'aria-label',
            visible ? 'Sembunyikan password' : 'Tampilkan password'
        );

        icon.classList.toggle('bx-hide', !visible);
        icon.classList.toggle('bx-show', visible);

        password.focus({ preventScroll: true });
    });
})();
</script>
</body>
</html>
