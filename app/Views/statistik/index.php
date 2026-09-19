<?= $this->extend('main') ?>
<?= $this->section('content') ?>
<?php
$result = is_array($statistik ?? null) ? $statistik : [];
$success = !empty($result['success']);
$filters = $result['filters'] ?? [];
$options = $result['options'] ?? [];
$data = $result['data'] ?? [];
$canExport = !empty($result['can_export']);
$initialJson = json_encode(
    $result,
    JSON_UNESCAPED_UNICODE
    | JSON_UNESCAPED_SLASHES
    | JSON_HEX_TAG
    | JSON_HEX_AMP
    | JSON_HEX_APOS
    | JSON_HEX_QUOT
);
?>
<link rel="stylesheet" href="<?= sisfour_asset_url('assets/vendor/libs/apex-charts/apexcharts.css') ?>">
<link rel="stylesheet" href="<?= sisfour_asset_url('assets/css/statistik.css') ?>">

<div id="statistikApp"
     data-data-url="<?= esc(base_url('statistik/data')) ?>"
     data-export-url="<?= esc(base_url('statistik/export/pdf')) ?>">
    <div class="sisfour-page-header">
        <div class="sisfour-page-header__copy">
            <h4 class="fw-bold mb-1">Statistik</h4>
            <p class="text-muted mb-0">Visualisasi agregat lintas-domain untuk Admin, Operator, dan Pimpinan.</p>
        </div>
        <div class="sisfour-page-actions">
            <?php if ($canExport): ?>
                <a id="statistikExportPdf"
                   class="btn btn-outline-primary sisfour-touch-target"
                   href="<?= esc(base_url('statistik/export/pdf')) ?>">
                    <i class="bx bx-file me-1"></i>Export PDF
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!$success): ?>
        <div class="alert alert-danger">
            <?= esc((string) ($result['message'] ?? 'Data Statistik tidak tersedia.')) ?>
        </div>
    <?php else: ?>
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Filter Global</h5></div>
            <div class="card-body">
                <form id="statistikFilter" class="row g-3 align-items-end">
                    <div class="col-md-4 col-xl-3">
                        <label class="form-label" for="filterTahun">Tahun Ajaran</label>
                        <select class="form-select" id="filterTahun" name="id_tahun">
                            <?php foreach (($options['tahun'] ?? []) as $row): ?>
                                <option value="<?= (int) ($row['id'] ?? 0) ?>"
                                    <?= (int) ($filters['id_tahun'] ?? 0) === (int) ($row['id'] ?? 0) ? 'selected' : '' ?>>
                                    <?= esc((string) ($row['label'] ?? '-')) ?><?= !empty($row['aktif']) ? ' · Aktif' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 col-xl-2">
                        <label class="form-label" for="filterPeriode">Rentang</label>
                        <select class="form-select" id="filterPeriode" name="periode">
                            <?php foreach ([
                                'all' => 'Seluruh Periode',
                                'bulan_ini' => 'Bulan Ini',
                                '30_hari' => '30 Hari Terakhir',
                                'custom' => 'Custom',
                            ] as $value => $label): ?>
                                <option value="<?= esc($value) ?>" <?= ($filters['periode'] ?? 'all') === $value ? 'selected' : '' ?>>
                                    <?= esc($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 col-xl-2 statistik-custom-date">
                        <label class="form-label" for="filterMulai">Mulai</label>
                        <input class="form-control" type="date" id="filterMulai" name="tanggal_mulai"
                               value="<?= esc((string) ($filters['tanggal_mulai'] ?? '')) ?>">
                    </div>
                    <div class="col-md-4 col-xl-2 statistik-custom-date">
                        <label class="form-label" for="filterSelesai">Selesai</label>
                        <input class="form-control" type="date" id="filterSelesai" name="tanggal_selesai"
                               value="<?= esc((string) ($filters['tanggal_selesai'] ?? '')) ?>">
                    </div>
                    <div class="col-md-4 col-xl-1">
                        <label class="form-label" for="filterTingkat">Tingkat</label>
                        <select class="form-select" id="filterTingkat" name="tingkat">
                            <option value="">Semua</option>
                            <?php foreach (['7','8','9'] as $level): ?>
                                <option value="<?= $level ?>" <?= ($filters['tingkat'] ?? '') === $level ? 'selected' : '' ?>><?= $level ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 col-xl-2">
                        <label class="form-label" for="filterKelas">Kelas</label>
                        <select class="form-select" id="filterKelas" name="id_kelas">
                            <option value="">Semua Kelas</option>
                            <?php foreach (($options['kelas'] ?? []) as $row): ?>
                                <option value="<?= (int) ($row['id'] ?? 0) ?>"
                                        data-tingkat="<?= esc((string) ($row['tingkat'] ?? '')) ?>"
                                    <?= (int) ($filters['id_kelas'] ?? 0) === (int) ($row['id'] ?? 0) ? 'selected' : '' ?>>
                                    <?= esc((string) ($row['nama'] ?? '-')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 d-flex flex-wrap gap-2">
                        <button id="statistikApply" class="btn btn-primary sisfour-touch-target" type="submit">
                            <i class="bx bx-filter-alt me-1"></i>Terapkan
                        </button>
                        <a class="btn btn-outline-secondary sisfour-touch-target" href="<?= esc(base_url('statistik')) ?>">
                            Reset
                        </a>
                        <span id="statistikStatus" class="small text-muted align-self-center ms-md-2"></span>
                    </div>
                </form>
            </div>
        </div>

        <div class="statistik-context mb-4">
            <span class="badge bg-label-primary" id="statistikTahunLabel"><?= esc((string) ($data['meta']['tahun_label'] ?? '-')) ?></span>
            <span class="badge bg-label-secondary" id="statistikPeriodeLabel"><?= esc((string) ($data['meta']['periode_label'] ?? '-')) ?></span>
            <span class="badge bg-label-info" id="statistikKelasLabel"><?= esc((string) ($data['meta']['kelas_label'] ?? '-')) ?></span>
        </div>

        <section class="mb-4">
            <div class="statistik-section-title"><h5>Executive Summary</h5></div>
            <div class="row g-3" id="executiveKpis">
                <?php foreach ([
                    ['siswa','Siswa','bx-group'],
                    ['guru','Guru','bx-chalkboard'],
                    ['pegawai','Pegawai','bx-id-card'],
                    ['kelas','Kelas','bx-door-open'],
                    ['mapel','Mapel','bx-book'],
                    ['kartu_aktif','Kartu Aktif','bx-id-card'],
                ] as $item): ?>
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="card h-100"><div class="card-body">
                            <i class="bx <?= esc($item[2]) ?> fs-4 text-primary"></i>
                            <small class="text-muted d-block mt-2"><?= esc($item[1]) ?></small>
                            <div class="fs-3 fw-bold" data-kpi="<?= esc($item[0]) ?>">0</div>
                        </div></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="mb-4">
            <div class="statistik-section-title"><h5>Komposisi Siswa</h5></div>
            <div class="row g-3">
                <div class="col-lg-6"><div class="card h-100"><div class="card-header"><h6 class="mb-0">Siswa per Tingkat</h6></div><div class="card-body"><div id="chartLevel" class="statistik-chart"></div></div></div></div>
                <div class="col-lg-6"><div class="card h-100"><div class="card-header"><h6 class="mb-0">Jenis Kelamin</h6></div><div class="card-body"><div id="chartGender" class="statistik-chart"></div></div></div></div>
            </div>
        </section>

        <section class="mb-4">
            <div class="statistik-section-title d-flex justify-content-between flex-wrap gap-2">
                <h5>Presensi & EWS</h5>
                <small class="text-muted" id="ewsWindowLabel"></small>
            </div>
            <div class="row g-3">
                <div class="col-lg-5"><div class="card h-100"><div class="card-header"><h6 class="mb-0">Komposisi H/S/I/A</h6></div><div class="card-body"><div id="chartAttendanceSummary" class="statistik-chart"></div></div></div></div>
                <div class="col-lg-7"><div class="card h-100"><div class="card-header"><h6 class="mb-0">Tren Presensi Sesi Awal</h6></div><div class="card-body"><div id="chartAttendanceTrend" class="statistik-chart"></div></div></div></div>
                <div class="col-12"><div class="card"><div class="card-header"><h6 class="mb-0">Persentase Hadir per Kelas</h6></div><div class="card-body"><div id="chartAttendanceClass" class="statistik-chart statistik-chart--wide"></div></div></div></div>
                <div class="col-md-4"><div class="card h-100"><div class="card-header"><h6 class="mb-0">Top Sakit 14 Hari</h6></div><div class="card-body"><div id="chartEwsSakit" class="statistik-chart"></div></div></div></div>
                <div class="col-md-4"><div class="card h-100"><div class="card-header"><h6 class="mb-0">Top Izin 14 Hari</h6></div><div class="card-body"><div id="chartEwsIzin" class="statistik-chart"></div></div></div></div>
                <div class="col-md-4"><div class="card h-100"><div class="card-header d-flex justify-content-between"><h6 class="mb-0">Top Alpha 14 Hari</h6><span class="badge bg-label-danger" id="ewsAlphaCount">0 EWS</span></div><div class="card-body"><div id="chartEwsAlpha" class="statistik-chart"></div></div></div></div>
            </div>
        </section>

        <section class="mb-4">
            <div class="statistik-section-title"><h5>Pembelajaran</h5></div>
            <div class="row g-3">
                <div class="col-lg-4">
                    <div class="card h-100"><div class="card-header d-flex justify-content-between align-items-center gap-2"><h6 class="mb-0">Jadwal / Jurnal Hari Ini</h6><small id="teachingTodayContext" class="text-muted"></small></div>
                        <div class="card-body"><div class="row text-center g-2">
                            <?php foreach ([['wajib','Wajib'],['sudah','Sudah'],['belum','Belum']] as $item): ?>
                                <div class="col-4"><small class="text-muted d-block"><?= $item[1] ?></small><strong class="fs-4" data-teaching="<?= $item[0] ?>">0</strong></div>
                            <?php endforeach; ?>
                        </div><div id="chartTeachingStatus" class="statistik-chart statistik-chart--compact mt-3"></div></div>
                    </div>
                </div>
                <div class="col-lg-8"><div class="card h-100"><div class="card-header"><h6 class="mb-0">Tren Presensi Mengajar / Jurnal</h6></div><div class="card-body"><div id="chartTeachingTrend" class="statistik-chart"></div></div></div></div>
            </div>
        </section>

        <section class="mb-4">
            <div class="statistik-section-title"><h5>Pembinaan</h5></div>
            <div class="row g-3">
                <div class="col-lg-6"><div class="card h-100"><div class="card-header d-flex justify-content-between"><h6 class="mb-0">Pelanggaran</h6><span class="badge bg-label-warning"><span id="disciplineTotal">0</span> catatan</span></div><div class="card-body"><div id="chartDiscipline" class="statistik-chart"></div><div id="chartDisciplineTrend" class="statistik-chart statistik-chart--compact"></div><small class="text-muted">Berdasarkan jumlah catatan/kategori, tanpa poin.</small></div></div></div>
                <div class="col-lg-6"><div class="card h-100"><div class="card-header d-flex justify-content-between"><h6 class="mb-0">Prestasi</h6><span class="badge bg-label-success"><span id="achievementTotal">0</span> data</span></div><div class="card-body"><div id="chartAchievement" class="statistik-chart"></div><div id="chartAchievementTrend" class="statistik-chart statistik-chart--compact"></div></div></div></div>
            </div>
        </section>

        <section class="mb-4">
            <div class="statistik-section-title"><h5>UKS — Aggregate</h5></div>
            <div class="row g-3">
                <div class="col-lg-4"><div class="card h-100"><div class="card-body"><div class="row g-2 text-center">
                    <div class="col-4"><small class="text-muted d-block">Kunjungan</small><strong id="uksKunjungan" class="fs-4">0</strong></div>
                    <div class="col-4"><small class="text-muted d-block">CKG</small><strong id="uksCkg" class="fs-4">0</strong></div>
                    <div class="col-4"><small class="text-muted d-block">Rujukan</small><strong id="uksRujukan" class="fs-4">0</strong></div>
                </div><p class="small text-muted mt-3 mb-0">Tidak menampilkan detail kesehatan individual.</p></div></div></div>
                <div class="col-lg-8"><div class="card h-100"><div class="card-header"><h6 class="mb-0">Distribusi Hasil Kunjungan</h6></div><div class="card-body"><div id="chartUks" class="statistik-chart"></div></div></div></div>
            </div>
        </section>

        <section class="mb-4">
            <div class="statistik-section-title"><h5>PTSP — Aggregate</h5></div>
            <div class="row g-3">
                <div class="col-lg-4"><div class="card h-100"><div class="card-header"><h6 class="mb-0">Layanan</h6></div><div class="card-body"><div id="chartPtspLayanan" class="statistik-chart"></div></div></div></div>
                <div class="col-lg-4"><div class="card h-100"><div class="card-header"><h6 class="mb-0">Pengaduan per Status</h6></div><div class="card-body"><div id="chartPtspPengaduan" class="statistik-chart"></div></div></div></div>
                <div class="col-lg-4"><div class="card h-100"><div class="card-header"><h6 class="mb-0">Klasifikasi Pengaduan</h6></div><div class="card-body"><div id="chartPtspKlasifikasi" class="statistik-chart"></div></div></div></div>
                <div class="col-12"><div class="card"><div class="card-header d-flex justify-content-between"><h6 class="mb-0">Polling Kepuasan</h6><span class="badge bg-label-primary">Rata-rata <span id="ptspAvgScore">—</span> / 5 · <span id="ptspPollingTotal">0</span> responden</span></div><div class="card-body"><div id="chartPtspPolling" class="statistik-chart statistik-chart--wide"></div></div></div></div>
            </div>
        </section>

        <section class="mb-4">
            <div class="statistik-section-title"><h5>Mobilitas / Status Siswa</h5></div>
            <div class="card"><div class="card-body"><div id="chartMobility" class="statistik-chart statistik-chart--wide"></div></div></div>
        </section>

        <div class="alert alert-secondary mb-0">
            <i class="bx bx-shield-quarter me-1"></i>
            Konseling BK tidak termasuk halaman Statistik. UKS dan PTSP hanya ditampilkan sebagai agregat.
        </div>

        <script type="application/json" id="statistikInitialData"><?= $initialJson ?: '{}' ?></script>
    <?php endif; ?>
</div>

<?php if ($success): ?>
<script src="<?= sisfour_asset_url('assets/vendor/libs/apex-charts/apexcharts.js') ?>"></script>
<script src="<?= sisfour_asset_url('assets/js/statistik.js') ?>"></script>
<?php endif; ?>
<?= $this->endSection() ?>
