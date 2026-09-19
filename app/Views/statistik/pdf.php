<?php
$data = $report['data'] ?? [];
$meta = $data['meta'] ?? [];
$executive = $data['executive'] ?? [];
$attendance = $data['attendance']['summary'] ?? [];
$ews = $data['ews'] ?? [];
$teaching = $data['teaching']['today'] ?? [];
$discipline = $data['discipline'] ?? [];
$achievement = $data['achievement'] ?? [];
$uks = $data['uks'] ?? [];
$ptsp = $data['ptsp'] ?? [];
$mobility = $data['mobility']['status'] ?? [];
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
@page { margin: 10mm; }
body { font-family: "DejaVu Sans", sans-serif; font-size: 9px; color:#263238; margin:0; }
h1 { font-size:18px; margin:0 0 2px; }
h2 { font-size:12px; margin:12px 0 6px; border-bottom:1px solid #cfd8dc; padding-bottom:3px; }
.meta { color:#607d8b; margin-bottom:8px; }
.kpis { width:100%; border-collapse:separate; border-spacing:5px; margin:-5px 0 6px -5px; }
.kpis td { width:16.66%; border:1px solid #cfd8dc; padding:7px; vertical-align:top; }
.kpis small { color:#607d8b; display:block; }
.kpis strong { font-size:16px; display:block; margin-top:2px; }
.grid { width:100%; border-collapse:separate; border-spacing:6px; margin:-6px; }
.grid td { width:50%; vertical-align:top; }
.card { border:1px solid #cfd8dc; padding:7px; page-break-inside:avoid; }
.card h3 { font-size:10px; margin:0 0 5px; }
.chart { width:100%; max-height:190px; }
table.data { width:100%; border-collapse:collapse; }
table.data th, table.data td { border-bottom:1px solid #eceff1; padding:4px 5px; text-align:left; }
table.data th { background:#f5f7f8; }
.right { text-align:right !important; }
.note { color:#607d8b; font-size:8px; }
.page-break { page-break-before:always; }
</style>
</head>
<body>
<h1>Statistik — <?= esc((string) ($meta['nama_sekolah'] ?? 'SisisFour')) ?></h1>
<div class="meta">
    Tahun Ajaran: <?= esc((string) ($meta['tahun_label'] ?? '-')) ?>
    · Periode: <?= esc((string) ($meta['periode_label'] ?? '-')) ?>
    · Kelas: <?= esc((string) ($meta['kelas_label'] ?? '-')) ?>
    · Export: <?= esc((string) ($meta['generated_at'] ?? '-')) ?>
</div>

<table class="kpis"><tr>
<?php foreach ([
    ['Siswa', $executive['siswa'] ?? 0],
    ['Guru', $executive['guru'] ?? 0],
    ['Pegawai', $executive['pegawai'] ?? 0],
    ['Kelas', $executive['kelas'] ?? 0],
    ['Mapel', $executive['mapel'] ?? 0],
    ['Kartu Aktif', $executive['kartu_aktif'] ?? 0],
] as $item): ?>
<td><small><?= esc($item[0]) ?></small><strong><?= number_format((int) $item[1], 0, ',', '.') ?></strong></td>
<?php endforeach; ?>
</tr></table>

<h2>Presensi & EWS</h2>
<table class="grid"><tr>
<td><div class="card"><h3>Presensi Siswa</h3>
<?php if (!empty($charts['attendance'])): ?><img class="chart" src="<?= esc($charts['attendance'], 'attr') ?>"><?php endif; ?>
</div></td>
<td><div class="card"><h3>Persentase Hadir per Kelas</h3>
<?php if (!empty($charts['attendance_class'])): ?><img class="chart" src="<?= esc($charts['attendance_class'], 'attr') ?>"><?php endif; ?>
</div></td>
</tr><tr>
<td><div class="card"><h3>Top Alpha 14 Hari</h3>
<?php if (!empty($charts['ews_alpha'])): ?><img class="chart" src="<?= esc($charts['ews_alpha'], 'attr') ?>"><?php else: ?><div class="note">Belum ada data.</div><?php endif; ?>
</div></td>
<td><div class="card">
<h3>Ringkasan</h3>
<table class="data">
<tr><th>Hadir</th><td class="right"><?= (int) ($attendance['Hadir'] ?? 0) ?></td></tr>
<tr><th>Sakit</th><td class="right"><?= (int) ($attendance['Sakit'] ?? 0) ?></td></tr>
<tr><th>Izin</th><td class="right"><?= (int) ($attendance['Izin'] ?? 0) ?></td></tr>
<tr><th>Alpha</th><td class="right"><?= (int) ($attendance['Alpha'] ?? 0) ?></td></tr>
<tr><th>EWS Alpha ≥3 / 14 hari</th><td class="right"><?= (int) ($ews['ews_alpha_count'] ?? 0) ?></td></tr>
</table>
</div></td>
</tr></table>

<h2>Pembelajaran & Pembinaan</h2>
<table class="grid"><tr>
<td><div class="card"><h3>Jadwal / Jurnal Hari Ini</h3>
<table class="data">
<tr><th>Wajib</th><td class="right"><?= (int) ($teaching['wajib'] ?? 0) ?></td></tr>
<tr><th>Sudah</th><td class="right"><?= (int) ($teaching['sudah'] ?? 0) ?></td></tr>
<tr><th>Belum</th><td class="right"><?= (int) ($teaching['belum'] ?? 0) ?></td></tr>
</table></div></td>
<td><div class="card"><h3>Kategori Pelanggaran</h3>
<?php if (!empty($charts['discipline'])): ?><img class="chart" src="<?= esc($charts['discipline'], 'attr') ?>"><?php endif; ?>
<div class="note">Total catatan: <?= (int) ($discipline['total'] ?? 0) ?> · tanpa sistem poin.</div>
</div></td>
</tr><tr>
<td><div class="card"><h3>Tingkat Prestasi</h3>
<?php if (!empty($charts['achievement'])): ?><img class="chart" src="<?= esc($charts['achievement'], 'attr') ?>"><?php endif; ?>
<div class="note">Total prestasi: <?= (int) ($achievement['total'] ?? 0) ?></div>
</div></td>
<td><div class="card"><h3>UKS Aggregate</h3>
<?php if (!empty($charts['uks'])): ?><img class="chart" src="<?= esc($charts['uks'], 'attr') ?>"><?php endif; ?>
<div class="note">Kunjungan <?= (int) ($uks['kunjungan'] ?? 0) ?> · CKG <?= (int) ($uks['ckg'] ?? 0) ?> · Rujukan Klinik <?= (int) ($uks['rujukan_klinik'] ?? 0) ?></div>
</div></td>
</tr></table>

<div class="page-break"></div>
<h2>PTSP & Mobilitas Siswa</h2>
<table class="grid"><tr>
<td><div class="card"><h3>Status Layanan PTSP</h3>
<?php if (!empty($charts['ptsp_service'])): ?><img class="chart" src="<?= esc($charts['ptsp_service'], 'attr') ?>"><?php endif; ?>
</div></td>
<td><div class="card"><h3>Kepuasan PTSP</h3>
<?php if (!empty($charts['polling'])): ?><img class="chart" src="<?= esc($charts['polling'], 'attr') ?>"><?php endif; ?>
<div class="note">Rata-rata skor: <?= esc((string) ($ptsp['polling']['avg_score'] ?? '-')) ?> · responden <?= (int) ($ptsp['polling']['total'] ?? 0) ?></div>
</div></td>
</tr><tr>
<td><div class="card"><h3>Pengaduan per Status</h3>
<table class="data">
<?php foreach (($ptsp['pengaduan_status'] ?? []) as $row): ?>
<tr><th><?= esc((string) ($row['label'] ?? '-')) ?></th><td class="right"><?= (int) ($row['total'] ?? 0) ?></td></tr>
<?php endforeach; ?>
<?php if (empty($ptsp['pengaduan_status'])): ?><tr><td class="note">Belum ada data.</td></tr><?php endif; ?>
</table></div></td>
<td><div class="card"><h3>Mobilitas / Status Siswa</h3>
<table class="data">
<?php foreach ($mobility as $row): ?>
<tr><th><?= esc((string) ($row['label'] ?? '-')) ?></th><td class="right"><?= (int) ($row['total'] ?? 0) ?></td></tr>
<?php endforeach; ?>
<?php if ($mobility === []): ?><tr><td class="note">Belum ada data.</td></tr><?php endif; ?>
</table></div></td>
</tr></table>

<p class="note">Laporan ini berisi agregat lintas-domain. Konseling BK tidak termasuk. UKS dan PTSP tidak memuat detail individual pada export Statistik.</p>
</body>
</html>
