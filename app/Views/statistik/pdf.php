<?php
$data = $report['data'] ?? [];
$meta = $data['meta'] ?? [];
$executive = $data['executive'] ?? [];
$attendance = $data['attendance'] ?? [];
$ews = $data['ews'] ?? [];
$teaching = $data['teaching'] ?? [];
$discipline = $data['discipline'] ?? [];
$achievement = $data['achievement'] ?? [];
$counseling = $data['counseling'] ?? [];
$uks = $data['uks'] ?? [];
$ptsp = $data['ptsp'] ?? [];
$mobility = $data['mobility'] ?? [];

$chart = static fn (string $key): ?string =>
    isset($charts[$key]) && is_string($charts[$key]) && $charts[$key] !== ''
        ? $charts[$key]
        : null;
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
@page { margin: 8mm; }
* { box-sizing:border-box; }
body {
    margin:0;
    color:#566a7f;
    font-family:"DejaVu Sans",sans-serif;
    font-size:8.2pt;
    background:#fff;
}
.page { page-break-after:always; }
.page:last-child { page-break-after:auto; }
.header {
    display:table;
    width:100%;
    margin-bottom:5mm;
}
.header-copy,.header-meta { display:table-cell; vertical-align:top; }
.header-meta { width:44%; text-align:right; }
h1 { margin:0 0 1mm; font-size:19pt; color:#566a7f; line-height:1.05; }
.subtitle { color:#8592a3; font-size:8.5pt; }
.badge {
    display:inline-block;
    margin-left:1.5mm;
    margin-bottom:1mm;
    padding:1.5mm 2.4mm;
    border-radius:10mm;
    background:#eef0ff;
    color:#696cff;
    font-size:7.4pt;
    font-weight:700;
}
.section-title {
    margin:0 0 2.5mm;
    color:#566a7f;
    font-size:12pt;
    font-weight:700;
}
.grid {
    width:100%;
    border-collapse:separate;
    border-spacing:2mm;
    margin:-2mm;
}
.grid td { vertical-align:top; }
.card {
    border:1px solid #d9dee3;
    border-radius:2.4mm;
    padding:3mm;
    background:#fff;
    page-break-inside:avoid;
}
.card-title {
    margin:0 0 2mm;
    color:#566a7f;
    font-size:9pt;
    font-weight:700;
}
.kpi-table {
    width:100%;
    border-collapse:separate;
    border-spacing:2mm;
    margin:-2mm;
}
.kpi-table td {
    width:16.66%;
    border:1px solid #d9dee3;
    border-radius:2.4mm;
    padding:3mm;
    vertical-align:top;
}
.kpi-label { color:#8592a3; font-size:7.4pt; }
.kpi-value { margin-top:1mm; color:#566a7f; font-size:17pt; font-weight:700; }
.chart {
    display:block;
    width:100%;
    max-height:61mm;
    object-fit:contain;
}
.chart-compact { max-height:47mm; }
.chart-wide { max-height:68mm; }
.chart-tall { max-height:72mm; }
.mini-kpis { width:100%; border-collapse:collapse; }
.mini-kpis td { width:33.33%; text-align:center; padding:2mm; }
.mini-kpis small { display:block; color:#8592a3; }
.mini-kpis strong { font-size:15pt; color:#566a7f; }
.note { margin-top:1.5mm; color:#8592a3; font-size:7.2pt; }
.alert {
    margin-top:4mm;
    padding:3mm;
    border:1px solid #d9dee3;
    border-radius:2.4mm;
    background:#f5f5f9;
    color:#697a8d;
}
</style>
</head>
<body>

<section class="page">
    <div class="header">
        <div class="header-copy">
            <h1>Statistik</h1>
            <div class="subtitle"><?= esc((string) ($meta['nama_sekolah'] ?? 'SisisFour')) ?></div>
        </div>
        <div class="header-meta">
            <span class="badge"><?= esc((string) ($meta['tahun_label'] ?? '-')) ?></span>
            <span class="badge"><?= esc((string) ($meta['periode_label'] ?? '-')) ?></span>
            <span class="badge"><?= esc((string) ($meta['kelas_label'] ?? '-')) ?></span>
            <div class="note">Export <?= esc((string) ($meta['generated_at'] ?? '-')) ?></div>
        </div>
    </div>

    <div class="section-title">Executive Summary</div>
    <table class="kpi-table"><tr>
    <?php foreach ([
        ['Siswa', $executive['siswa'] ?? 0],
        ['Guru', $executive['guru'] ?? 0],
        ['Pegawai', $executive['pegawai'] ?? 0],
        ['Kelas', $executive['kelas'] ?? 0],
        ['Mapel', $executive['mapel'] ?? 0],
        ['Kartu Aktif', $executive['kartu_aktif'] ?? 0],
    ] as $item): ?>
        <td><div class="kpi-label"><?= esc($item[0]) ?></div><div class="kpi-value"><?= number_format((int) $item[1], 0, ',', '.') ?></div></td>
    <?php endforeach; ?>
    </tr></table>

    <div class="section-title" style="margin-top:5mm">Komposisi Siswa</div>
    <table class="grid"><tr>
        <td style="width:50%"><div class="card">
            <div class="card-title">Siswa per Tingkat</div>
            <?php if ($chart('chartLevel')): ?><img class="chart chart-tall" src="<?= esc($chart('chartLevel'), 'attr') ?>"><?php else: ?><div class="note">Belum ada data grafik.</div><?php endif; ?>
        </div></td>
        <td style="width:50%"><div class="card">
            <div class="card-title">Jenis Kelamin</div>
            <?php if ($chart('chartGender')): ?><img class="chart chart-tall" src="<?= esc($chart('chartGender'), 'attr') ?>"><?php else: ?><div class="note">Belum ada data grafik.</div><?php endif; ?>
        </div></td>
    </tr></table>
</section>

<section class="page">
    <div class="section-title">Presensi Siswa</div>
    <table class="grid"><tr>
        <td style="width:38%"><div class="card">
            <div class="card-title">Komposisi H/S/I/A</div>
            <?php if ($chart('chartAttendanceSummary')): ?><img class="chart" src="<?= esc($chart('chartAttendanceSummary'), 'attr') ?>"><?php endif; ?>
        </div></td>
        <td style="width:62%"><div class="card">
            <div class="card-title">Tren Presensi Sesi Awal</div>
            <?php if ($chart('chartAttendanceTrend')): ?><img class="chart" src="<?= esc($chart('chartAttendanceTrend'), 'attr') ?>"><?php endif; ?>
        </div></td>
    </tr></table>
    <div class="card" style="margin-top:3mm">
        <div class="card-title">Persentase Hadir per Kelas</div>
        <?php if ($chart('chartAttendanceClass')): ?><img class="chart chart-wide" src="<?= esc($chart('chartAttendanceClass'), 'attr') ?>"><?php endif; ?>
    </div>
</section>

<section class="page">
    <div class="section-title">EWS Presensi — 14 Hari</div>
    <div class="note" style="margin-bottom:3mm">
        <?= esc((string) ($ews['window']['mulai'] ?? '-')) ?> s.d.
        <?= esc((string) ($ews['window']['selesai'] ?? '-')) ?>
        · EWS Alpha ≥3 = <?= (int) ($ews['ews_alpha_count'] ?? 0) ?> siswa
    </div>
    <table class="grid"><tr>
        <td style="width:33.33%"><div class="card">
            <div class="card-title">Top Sakit</div>
            <?php if ($chart('chartEwsSakit')): ?><img class="chart chart-tall" src="<?= esc($chart('chartEwsSakit'), 'attr') ?>"><?php endif; ?>
        </div></td>
        <td style="width:33.33%"><div class="card">
            <div class="card-title">Top Izin</div>
            <?php if ($chart('chartEwsIzin')): ?><img class="chart chart-tall" src="<?= esc($chart('chartEwsIzin'), 'attr') ?>"><?php endif; ?>
        </div></td>
        <td style="width:33.33%"><div class="card">
            <div class="card-title">Top Alpha</div>
            <?php if ($chart('chartEwsAlpha')): ?><img class="chart chart-tall" src="<?= esc($chart('chartEwsAlpha'), 'attr') ?>"><?php endif; ?>
        </div></td>
    </tr></table>
</section>

<section class="page">
    <div class="section-title">Pembelajaran</div>
    <table class="grid"><tr>
        <td style="width:34%"><div class="card">
            <div class="card-title">Jadwal / Jurnal Hari Ini</div>
            <table class="mini-kpis"><tr>
                <td><small>Wajib</small><strong><?= !empty($teaching['today']['applicable']) ? (int) ($teaching['today']['wajib'] ?? 0) : '—' ?></strong></td>
                <td><small>Sudah</small><strong><?= !empty($teaching['today']['applicable']) ? (int) ($teaching['today']['sudah'] ?? 0) : '—' ?></strong></td>
                <td><small>Belum</small><strong><?= !empty($teaching['today']['applicable']) ? (int) ($teaching['today']['belum'] ?? 0) : '—' ?></strong></td>
            </tr></table>
            <?php if ($chart('chartTeachingStatus')): ?><img class="chart chart-compact" src="<?= esc($chart('chartTeachingStatus'), 'attr') ?>"><?php endif; ?>
        </div></td>
        <td style="width:66%"><div class="card">
            <div class="card-title">Tren Presensi Mengajar / Jurnal</div>
            <?php if ($chart('chartTeachingTrend')): ?><img class="chart chart-wide" src="<?= esc($chart('chartTeachingTrend'), 'attr') ?>"><?php endif; ?>
        </div></td>
    </tr></table>
</section>

<section class="page">
    <div class="section-title">Pembinaan — Pelanggaran & Prestasi</div>
    <table class="grid"><tr>
        <td style="width:50%"><div class="card">
            <div class="card-title">Pelanggaran · <?= (int) ($discipline['total'] ?? 0) ?> catatan</div>
            <?php if ($chart('chartDiscipline')): ?><img class="chart chart-compact" src="<?= esc($chart('chartDiscipline'), 'attr') ?>"><?php endif; ?>
            <?php if ($chart('chartDisciplineTrend')): ?><img class="chart chart-compact" src="<?= esc($chart('chartDisciplineTrend'), 'attr') ?>"><?php endif; ?>
            <div class="note">Berbasis jumlah catatan/kategori, tanpa poin.</div>
        </div></td>
        <td style="width:50%"><div class="card">
            <div class="card-title">Prestasi · <?= (int) ($achievement['total'] ?? 0) ?> data</div>
            <?php if ($chart('chartAchievement')): ?><img class="chart chart-compact" src="<?= esc($chart('chartAchievement'), 'attr') ?>"><?php endif; ?>
            <?php if ($chart('chartAchievementTrend')): ?><img class="chart chart-compact" src="<?= esc($chart('chartAchievementTrend'), 'attr') ?>"><?php endif; ?>
        </div></td>
    </tr></table>
</section>

<section class="page">
    <div class="section-title">Konseling BK — Aggregate Confidential</div>
    <div class="note" style="margin-bottom:3mm">
        Total <?= (int) ($counseling['total'] ?? 0) ?> konseling · statistik sekolah saja · filter Tingkat/Kelas tidak diterapkan.
    </div>
    <table class="grid"><tr>
        <td style="width:33.33%"><div class="card">
            <div class="card-title">Status Konseling</div>
            <?php if ($chart('chartCounselingStatus')): ?><img class="chart chart-tall" src="<?= esc($chart('chartCounselingStatus'), 'attr') ?>"><?php endif; ?>
        </div></td>
        <td style="width:33.33%"><div class="card">
            <div class="card-title">Bidang Konseling</div>
            <?php if ($chart('chartCounselingFields')): ?><img class="chart chart-tall" src="<?= esc($chart('chartCounselingFields'), 'attr') ?>"><?php endif; ?>
        </div></td>
        <td style="width:33.33%"><div class="card">
            <div class="card-title">Tren Konseling</div>
            <?php if ($chart('chartCounselingTrend')): ?><img class="chart chart-tall" src="<?= esc($chart('chartCounselingTrend'), 'attr') ?>"><?php endif; ?>
        </div></td>
    </tr></table>
    <div class="alert">
        Tidak menampilkan nama siswa, topik, catatan, Guru BK, tindak lanjut, atau jadwal individual.
    </div>
</section>

<section class="page">
    <div class="section-title">UKS — Aggregate</div>
    <table class="grid"><tr>
        <td style="width:34%"><div class="card">
            <table class="mini-kpis"><tr>
                <td><small>Kunjungan</small><strong><?= (int) ($uks['kunjungan'] ?? 0) ?></strong></td>
                <td><small>CKG</small><strong><?= (int) ($uks['ckg'] ?? 0) ?></strong></td>
                <td><small>Rujukan</small><strong><?= (int) ($uks['rujukan_klinik'] ?? 0) ?></strong></td>
            </tr></table>
            <div class="note">Tidak menampilkan detail kesehatan individual.</div>
        </div></td>
        <td style="width:66%"><div class="card">
            <div class="card-title">Distribusi Hasil Kunjungan</div>
            <?php if ($chart('chartUks')): ?><img class="chart chart-wide" src="<?= esc($chart('chartUks'), 'attr') ?>"><?php endif; ?>
        </div></td>
    </tr></table>
</section>

<section class="page">
    <div class="section-title">PTSP — Aggregate</div>
    <table class="grid"><tr>
        <td style="width:33.33%"><div class="card"><div class="card-title">Layanan</div><?php if ($chart('chartPtspLayanan')): ?><img class="chart" src="<?= esc($chart('chartPtspLayanan'), 'attr') ?>"><?php endif; ?></div></td>
        <td style="width:33.33%"><div class="card"><div class="card-title">Pengaduan per Status</div><?php if ($chart('chartPtspPengaduan')): ?><img class="chart" src="<?= esc($chart('chartPtspPengaduan'), 'attr') ?>"><?php endif; ?></div></td>
        <td style="width:33.33%"><div class="card"><div class="card-title">Klasifikasi Pengaduan</div><?php if ($chart('chartPtspKlasifikasi')): ?><img class="chart" src="<?= esc($chart('chartPtspKlasifikasi'), 'attr') ?>"><?php endif; ?></div></td>
    </tr></table>
    <div class="card" style="margin-top:3mm">
        <div class="card-title">Polling Kepuasan · rata-rata <?= esc((string) ($ptsp['polling']['avg_score'] ?? '—')) ?>/5 · <?= (int) ($ptsp['polling']['total'] ?? 0) ?> responden</div>
        <?php if ($chart('chartPtspPolling')): ?><img class="chart chart-wide" src="<?= esc($chart('chartPtspPolling'), 'attr') ?>"><?php endif; ?>
    </div>
</section>

<section class="page">
    <div class="section-title">Mobilitas / Status Siswa</div>
    <div class="card">
        <?php if ($chart('chartMobility')): ?><img class="chart chart-wide" src="<?= esc($chart('chartMobility'), 'attr') ?>"><?php else: ?><div class="note">Belum ada data grafik.</div><?php endif; ?>
    </div>
    <div class="alert">
        Konseling BK, UKS, dan PTSP hanya ditampilkan sebagai agregat. Tidak ada detail individual pada export Statistik.
    </div>
</section>

</body>
</html>
