<?php
$info = $displayInfo ?? [];
$namaSekolah = trim((string) ($info['nama_sekolah'] ?? 'MTsN 4 Jombang'));
$logo = trim((string) ($info['logo_sekolah'] ?? ''));
$logoUrl = '';

if ($logo !== '' && is_file(FCPATH . ltrim($logo, '/\\'))) {
    $logoUrl = base_url(ltrim($logo, '/'));
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>EWS Digital Signage - <?= esc($namaSekolah) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/signage.css') ?>">
</head>
<body>
    <main
        id="signageApp"
        class="signage-shell"
        data-data-url="<?= esc(base_url('signage/data')) ?>"
        data-refresh-minutes="<?= (int) ($info['refresh_minutes'] ?? 20) ?>"
    >
        <header class="signage-header">
            <div class="signage-brand">
                <?php if ($logoUrl !== ''): ?>
                    <img src="<?= esc($logoUrl, 'attr') ?>" alt="Logo" class="signage-logo">
                <?php endif; ?>
                <div>
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

        <section class="signage-grid">
            <article class="signage-panel signage-panel--wide">
                <div class="signage-panel__header">
                    <div>
                        <h2>EWS Presensi Siswa</h2>
                        <p>Alpha ≥ 3 pada Sesi Awal dalam 14 hari terakhir</p>
                    </div>
                    <span id="ewsCount" class="signage-badge">0</span>
                </div>
                <div class="signage-scroll" id="ewsScroll">
                    <table class="signage-table">
                        <thead>
                            <tr>
                                <th class="col-no">No</th>
                                <th>Nama Siswa</th>
                                <th>Kelas</th>
                                <th class="col-center">Total Alpha</th>
                            </tr>
                        </thead>
                        <tbody id="ewsBody">
                            <tr><td colspan="4" class="empty">Memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>
            </article>

            <div class="signage-grid__bottom">
                <article class="signage-panel">
                    <div class="signage-panel__header">
                        <div>
                            <h2>Kelas Belum Presensi</h2>
                            <p>Khusus Sesi Awal hari ini</p>
                        </div>
                        <span id="kelasCount" class="signage-badge">0</span>
                    </div>
                    <div class="signage-scroll" id="kelasScroll">
                        <table class="signage-table">
                            <thead>
                                <tr>
                                    <th class="col-no">No</th>
                                    <th>Kelas</th>
                                    <th>Wali Kelas</th>
                                </tr>
                            </thead>
                            <tbody id="kelasBody">
                                <tr><td colspan="3" class="empty">Memuat data...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </article>

                <article class="signage-panel">
                    <div class="signage-panel__header">
                        <div>
                            <h2>Guru Belum Presensi Mengajar</h2>
                            <p>Lewat jam selesai + 15 menit</p>
                        </div>
                        <span id="guruCount" class="signage-badge">0</span>
                    </div>
                    <div class="signage-scroll" id="guruScroll">
                        <table class="signage-table">
                            <thead>
                                <tr>
                                    <th class="col-no">No</th>
                                    <th>Guru</th>
                                    <th>Kelas</th>
                                    <th>Mapel</th>
                                    <th>Jam</th>
                                </tr>
                            </thead>
                            <tbody id="guruBody">
                                <tr><td colspan="5" class="empty">Memuat data...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </article>
            </div>
        </section>

        <footer class="signage-footer">
            <span>Data read-only • refresh otomatis setiap <?= (int) ($info['refresh_minutes'] ?? 20) ?> menit</span>
            <span id="signageStatus">Menghubungkan ke server...</span>
        </footer>
    </main>

    <script src="<?= base_url('assets/js/signage.js') ?>"></script>
</body>
</html>
