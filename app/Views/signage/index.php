<?php
$info = $displayInfo ?? [];
$namaSekolah = trim((string) ($info['nama_sekolah'] ?? 'MTsN 4 Jombang'));
$logo = trim((string) ($info['logo_sekolah'] ?? ''));
$logoUrl = '';

if ($logo !== '' && is_file(FCPATH . ltrim($logo, '/\\'))) {
    $logoUrl = base_url(ltrim($logo, '/'));
}

$refreshMinutes = max(1, (int) ($info['refresh_minutes'] ?? 5));
$rotationSeconds = max(5, (int) ($info['rotation_seconds'] ?? 15));
$rankingDays = max(1, (int) ($info['ranking_days'] ?? 14));
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>EWS Digital Signage - <?= esc($namaSekolah) ?></title>
    <link rel="stylesheet" href="<?= sisfour_asset_url('assets/css/signage.css') ?>">
</head>
<body>
    <main
        id="signageApp"
        class="signage-shell"
        data-data-url="<?= esc(base_url('signage/data')) ?>"
        data-refresh-minutes="<?= $refreshMinutes ?>"
        data-rotation-seconds="<?= $rotationSeconds ?>"
        data-ranking-days="<?= $rankingDays ?>"
    >
        <header class="signage-header">
            <div class="signage-brand">
                <?php if ($logoUrl !== ''): ?>
                    <img
                        src="<?= esc($logoUrl, 'attr') ?>"
                        alt="Logo <?= esc($namaSekolah, 'attr') ?>"
                        class="signage-logo"
                    >
                <?php endif; ?>

                <div class="signage-brand__text">
                    <div class="signage-kicker">EWS DIGITAL SIGNAGE SISFOUR</div>
                    <h1><?= esc($namaSekolah) ?></h1>
                    <div id="signageTahun" class="signage-year">
                        <?= esc($info['tahun_ajaran'] ?? 'Tahun Ajaran aktif belum tersedia') ?>
                    </div>
                </div>
            </div>

            <div class="signage-clock-wrap">
                <div id="signageDate" class="signage-date"></div>
                <div id="signageClock" class="signage-clock">--:--:--</div>
                <div class="signage-update">
                    Update data: <span id="signageLastUpdate">-</span>
                </div>
            </div>
        </header>

        <section class="signage-summary" aria-label="Ringkasan ketidakhadiran hari ini">
            <div class="signage-summary__label">
                <span class="signage-summary__eyebrow">SESI AWAL • HARI INI</span>
                <strong id="todayTotal">0 siswa tidak masuk</strong>
            </div>

            <div class="signage-summary__items">
                <div class="signage-summary__item signage-summary__item--sakit">
                    <span>Sakit</span>
                    <strong id="todaySakit">0</strong>
                </div>
                <div class="signage-summary__item signage-summary__item--izin">
                    <span>Izin</span>
                    <strong id="todayIzin">0</strong>
                </div>
                <div class="signage-summary__item signage-summary__item--alpha">
                    <span>Alpha</span>
                    <strong id="todayAlpha">0</strong>
                </div>
            </div>
        </section>

        <section class="signage-stage" aria-live="polite">
            <article class="signage-panel">
                <div class="signage-panel__header">
                    <div>
                        <div id="signageSlideKicker" class="signage-panel__kicker">
                            PEMANTAUAN PRESENSI
                        </div>
                        <h2 id="signageSlideTitle">20 Siswa Alpha Tertinggi</h2>
                        <p id="signageSlideSubtitle">
                            Sesi Awal • <?= $rankingDays ?> hari terakhir
                        </p>
                    </div>

                    <span id="signageSlideCount" class="signage-badge">0</span>
                </div>

                <div id="signageTableScroll" class="signage-scroll">
                    <table class="signage-table">
                        <thead id="signageTableHead">
                            <tr>
                                <th class="col-no">No</th>
                                <th>Nama Siswa</th>
                                <th>Kelas</th>
                                <th class="col-center">Total Alpha</th>
                            </tr>
                        </thead>
                        <tbody id="signageTableBody">
                            <tr>
                                <td colspan="4" class="empty">Memuat data...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </article>
        </section>

        <footer class="signage-footer">
            <div class="signage-footer__left">
                <span>
                    Data read-only • refresh server setiap <?= $refreshMinutes ?> menit
                </span>
                <span class="signage-footer__separator">•</span>
                <span>
                    Tabel berganti setiap <?= $rotationSeconds ?> detik
                </span>
            </div>

            <div id="signageSlideDots" class="signage-dots" aria-label="Posisi tabel">
                <span class="signage-dot is-active"></span>
                <span class="signage-dot"></span>
                <span class="signage-dot"></span>
                <span class="signage-dot"></span>
            </div>

            <span id="signageStatus" class="signage-status">
                Menghubungkan ke server...
            </span>
        </footer>
    </main>

    <script src="<?= sisfour_asset_url('assets/js/signage.js') ?>"></script>
</body>
</html>
