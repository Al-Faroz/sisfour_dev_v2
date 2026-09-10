<?php
$owner = $owner ?? [];
$formatDate = static function (?string $date): string {
    $date = trim((string) $date);
    if ($date === '') {
        return '-';
    }
    $ts = strtotime($date);
    if ($ts === false) {
        return $date;
    }
    $months = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return date('d', $ts) . ' ' . $months[(int) date('n', $ts)] . ' ' . date('Y', $ts);
};
$gender = ($owner['jenis_kelamin'] ?? '') === 'P' ? 'Perempuan' : (($owner['jenis_kelamin'] ?? '') === 'L' ? 'Laki-laki' : '-');
$ttl = trim((string) ($owner['tempat_lahir'] ?? ''));
if (! empty($owner['tanggal_lahir'])) {
    $ttl .= ($ttl !== '' ? ', ' : '') . $formatDate((string) $owner['tanggal_lahir']);
}
$ttl = $ttl !== '' ? $ttl : '-';
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 24mm 18mm 18mm 18mm; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #222; line-height: 1.35; }
    .header { width: 100%; border-bottom: 2px solid #222; padding-bottom: 12px; margin-bottom: 16px; }
    .header td { vertical-align: top; }
    .photo { width: 88px; height: 118px; object-fit: cover; border: 1px solid #aaa; }
    .photo-placeholder { width: 88px; height: 118px; border: 1px solid #aaa; text-align: center; padding-top: 48px; color: #888; }
    h1 { font-size: 18pt; margin: 0 0 4px 0; }
    .position { font-size: 11pt; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; }
    .school { font-size: 9pt; color: #555; margin-top: 5px; }
    .meta { font-size: 9pt; margin-top: 3px; }
    .section-title { margin: 18px 0 7px 0; font-size: 11pt; font-weight: bold; border-bottom: 1px solid #777; padding-bottom: 4px; page-break-after: avoid; }
    .identity { width: 100%; border-collapse: collapse; }
    .identity td { padding: 3px 4px; vertical-align: top; }
    .identity .label { width: 190px; color: #555; }
    table.data { width: 100%; border-collapse: collapse; margin-top: 4px; }
    table.data th, table.data td { border: 1px solid #aaa; padding: 5px 6px; vertical-align: top; }
    table.data th { background: #f2f2f2; font-size: 8.5pt; text-align: left; }
    table.data td { font-size: 8.7pt; }
    table.data tr { page-break-inside: avoid; }
    .empty { color: #777; text-align: center; font-style: italic; }
    .footer { margin-top: 22px; border-top: 1px solid #bbb; padding-top: 7px; font-size: 8pt; color: #666; }
</style>
</head>
<body>
<table class="header">
    <tr>
        <td style="width:105px">
            <?php if (! empty($photo_data_uri)): ?>
                <img src="<?= esc($photo_data_uri, 'attr') ?>" class="photo" alt="Foto">
            <?php else: ?>
                <div class="photo-placeholder">FOTO 3x4</div>
            <?php endif; ?>
        </td>
        <td>
            <h1><?= esc($owner['nama'] ?? '-') ?></h1>
            <div class="position"><?= esc($jabatan_display ?? ($owner_type === 'guru' ? 'Guru' : 'Pegawai')) ?></div>
            <?php if (! empty($owner['nip'])): ?>
                <div class="meta"><strong>NIP.</strong> <?= esc($owner['nip']) ?></div>
            <?php endif; ?>
            <div class="meta">
                <?php if (! empty($owner['nuptk'])): ?><strong>NUPTK:</strong> <?= esc($owner['nuptk']) ?> &nbsp; | &nbsp;<?php endif; ?>
                <strong>Status:</strong> <?= esc($owner['status_kepegawaian'] ?: '-') ?>
            </div>
            <div class="school"><strong><?= esc($nama_sekolah ?? '') ?></strong><?php if (! empty($alamat_sekolah)): ?><br><?= esc($alamat_sekolah) ?><?php endif; ?></div>
        </td>
    </tr>
</table>

<div class="section-title">1. DATA IDENTITAS PRIBADI</div>
<table class="identity">
    <tr><td class="label">Nomor Induk Kependudukan (NIK)</td><td>: <?= esc($owner['nik'] ?: '-') ?></td></tr>
    <tr><td class="label">Tempat, Tanggal Lahir</td><td>: <?= esc($ttl) ?></td></tr>
    <tr><td class="label">Jenis Kelamin</td><td>: <?= esc($gender) ?></td></tr>
    <tr><td class="label">Agama</td><td>: <?= esc($owner['agama'] ?: '-') ?></td></tr>
</table>

<div class="section-title">2. RIWAYAT PENDIDIKAN FORMAL</div>
<table class="data">
    <thead><tr><th style="width:12%">TINGKAT</th><th>INSTITUSI &amp; PROGRAM STUDI</th><th style="width:13%">TH. LULUS</th><th style="width:23%">NO. IJAZAH</th></tr></thead>
    <tbody>
    <?php if (empty($pendidikan)): ?>
        <tr><td colspan="4" class="empty">Belum ada riwayat pendidikan.</td></tr>
    <?php else: foreach ($pendidikan as $row): ?>
        <tr>
            <td><strong><?= esc($row['tingkat_pendidikan']) ?></strong></td>
            <td><?= esc($row['nama_institusi']) ?><?php if (! empty($row['program_studi'])): ?><br><?= esc($row['program_studi']) ?><?php endif; ?></td>
            <td><?= esc($row['tahun_lulus']) ?></td>
            <td><?= esc($row['no_ijazah'] ?: '-') ?></td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>

<div class="section-title">3. RIWAYAT PENUGASAN &amp; JABATAN</div>
<table class="data">
    <thead><tr><th style="width:25%">INSTANSI / SEKOLAH</th><th>TUGAS / MAPEL</th><th style="width:25%">PERIODE (TMT)</th><th style="width:22%">NO. SK PENUGASAN</th></tr></thead>
    <tbody>
    <?php if (empty($penugasan)): ?>
        <tr><td colspan="4" class="empty">Belum ada riwayat penugasan.</td></tr>
    <?php else: foreach ($penugasan as $row): ?>
        <tr>
            <td><?= esc($row['instansi_penugasan']) ?></td>
            <td><strong><?= esc($row['jabatan_tugas']) ?></strong><?php if (! empty($row['mata_pelajaran'])): ?><br>Mapel: <?= esc($row['mata_pelajaran']) ?><?php endif; ?></td>
            <td><?= esc($formatDate($row['tanggal_mulai'])) ?> - <?= esc(! empty($row['tanggal_selesai']) ? $formatDate($row['tanggal_selesai']) : 'Sdg. Menjabat') ?></td>
            <td><?= esc($row['no_sk_penugasan'] ?: '-') ?></td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>

<div class="section-title">4. RIWAYAT KEPANGKATAN</div>
<table class="data">
    <thead><tr><th>GOLONGAN RUANG</th><th>NAMA PANGKAT</th><th style="width:23%">TMT PANGKAT</th><th style="width:27%">NO. SK KENAIKAN PANGKAT</th></tr></thead>
    <tbody>
    <?php if (empty($pangkat)): ?>
        <tr><td colspan="4" class="empty">Belum ada riwayat kepangkatan.</td></tr>
    <?php else: foreach ($pangkat as $row): ?>
        <tr>
            <td><strong><?= esc($row['golongan_ruang']) ?></strong></td>
            <td><?= esc($row['nama_pangkat'] ?: '-') ?></td>
            <td><?= esc($formatDate($row['tmt_pangkat'])) ?></td>
            <td><?= esc($row['no_sk_pangkat'] ?: '-') ?></td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>

<div class="footer">
    Dokumen ini digenerate otomatis oleh SisisFour dari data personalia terbaru.<br>
    Dicetak pada: <?= esc($printed_at ?? '-') ?>
</div>
</body>
</html>
