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
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
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
                <img src="<?= esc($logoUrl, 'attr') ?>" alt="Logo <?= esc($namaSekolah, 'attr') ?>" class="signage-logo">
            <?php endif; ?>
            <div class="signage-brand__copy">
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
            <div class="signage-update">Update: <span id="signageLastUpdate">-</span></div>
        </div>
    </header>

    <section class="signage-attendance" aria-label="Jumlah dan persentase kehadiran siswa">
        <div class="signage-attendance__title">
            <span>Jumlah dan Persentase Kehadiran Siswa</span>
            <small id="coverageText">Coverage kelas: 0 / 0</small>
        </div>
        <div class="signage-attendance__metrics">
            <?php foreach ([
                ['Hadir', 'hadir'],
                ['Sakit', 'sakit'],
                ['Izin', 'izin'],
                ['Alpha', 'alpha'],
            ] as $metric): ?>
                <div class="attendance-metric attendance-metric--<?= esc($metric[1]) ?>">
                    <span><?= esc($metric[0]) ?></span>
                    <strong id="summary<?= esc($metric[0]) ?>Count">0</strong>
                    <em id="summary<?= esc($metric[0]) ?>Percent">0%</em>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="signage-panels">
        <article class="signage-panel signage-panel--ews">
            <header class="signage-panel__header">
                <div>
                    <h2>EWS Presensi Siswa</h2>
                    <small id="ewsSubtitle">S/I/A tertinggi • <?= $rankingDays ?> hari</small>
                </div>
                <span id="ewsPageLabel" class="panel-page">-</span>
            </header>
            <div class="signage-panel__body">
                <table class="panel-table">
                    <thead>
                    <tr><th class="no">No</th><th>Nama</th><th>Kelas</th><th class="value" id="ewsTotalHeader">Total</th></tr>
                    </thead>
                    <tbody id="ewsBody"><tr><td colspan="4" class="empty">Memuat data...</td></tr></tbody>
                </table>
            </div>
        </article>

        <article class="signage-panel">
            <header class="signage-panel__header">
                <div>
                    <h2>Kelas Belum Presensi</h2>
                    <small>Sesi Awal • hari ini</small>
                </div>
                <span id="kelasPageLabel" class="panel-page">-</span>
            </header>
            <div class="signage-panel__body">
                <table class="panel-table">
                    <thead><tr><th class="no">No</th><th>Kelas</th><th>Wali Kelas</th></tr></thead>
                    <tbody id="kelasBody"><tr><td colspan="3" class="empty">Memuat data...</td></tr></tbody>
                </table>
            </div>
        </article>

        <article class="signage-panel">
            <header class="signage-panel__header">
                <div>
                    <h2>Jadwal Belum Jurnal</h2>
                    <small>Selesai + 15 menit • hari ini</small>
                </div>
                <span id="jurnalPageLabel" class="panel-page">-</span>
            </header>
            <div class="signage-panel__body">
                <table class="panel-table panel-table--jurnal">
                    <thead><tr><th class="no">No</th><th>Guru</th><th>Kelas</th><th>Mapel</th><th class="jam">Jam</th></tr></thead>
                    <tbody id="jurnalBody"><tr><td colspan="5" class="empty">Memuat data...</td></tr></tbody>
                </table>
            </div>
        </article>
    </section>

    <footer class="signage-footer">
        <div>Refresh data <?= $refreshMinutes ?> menit • rotasi <?= $rotationSeconds ?> detik</div>
        <div id="signageStatus" class="signage-status">Menghubungkan ke server...</div>
        <div class="signage-footer__right">SisisFour • read-only public display</div>
    </footer>
</main>
<script src="<?= sisfour_asset_url('assets/js/signage.js') ?>"></script>
</body>
</html>
