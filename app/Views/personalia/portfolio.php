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

    $months = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    return date('d', $ts)
        . ' ' . $months[(int) date('n', $ts)]
        . ' ' . date('Y', $ts);
};

$gender = ($owner['jenis_kelamin'] ?? '') === 'P'
    ? 'Perempuan'
    : (($owner['jenis_kelamin'] ?? '') === 'L' ? 'Laki-laki' : '-');

$ttl = trim((string) ($owner['tempat_lahir'] ?? ''));

if (! empty($owner['tanggal_lahir'])) {
    $ttl .= ($ttl !== '' ? ', ' : '')
        . $formatDate((string) $owner['tanggal_lahir']);
}

$ttl = $ttl !== '' ? $ttl : '-';

$ownerType = $owner_type ?? 'guru';
$roleLabel = $jabatan_display
    ?? ($ownerType === 'guru' ? 'Guru' : 'Pegawai');
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
    @page {
        margin: 18mm 17mm 17mm 17mm;
    }

    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 9.5pt;
        color: #25313c;
        line-height: 1.4;
    }

    .hero {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 14px;
        padding-bottom: 12px;
        border-bottom: 2px solid #34495e;
    }

    .hero td {
        vertical-align: top;
    }

    .photo-cell {
        width: 102px;
        padding-right: 14px;
    }

    .photo {
        width: 88px;
        height: 118px;
        object-fit: cover;
        border: 1px solid #c7ced6;
        border-radius: 4px;
    }

    .photo-placeholder {
        width: 88px;
        height: 70px;
        padding-top: 48px;
        text-align: center;
        color: #87929c;
        border: 1px solid #c7ced6;
        border-radius: 4px;
        background: #f4f6f8;
    }

    .eyebrow {
        margin-bottom: 4px;
        color: #6c7781;
        font-size: 7.5pt;
        text-transform: uppercase;
        letter-spacing: 0.6px;
    }

    h1 {
        margin: 0 0 4px 0;
        font-size: 18pt;
        line-height: 1.15;
        color: #1f2d3a;
    }

    .position {
        margin-bottom: 7px;
        font-size: 10.5pt;
        font-weight: bold;
        color: #34495e;
    }

    .meta {
        margin-top: 3px;
        font-size: 8.5pt;
        color: #4c5965;
    }

    .school {
        margin-top: 7px;
        font-size: 8.3pt;
        color: #66737e;
    }

    .section-title {
        margin: 16px 0 7px 0;
        padding: 6px 8px;
        border-left: 3px solid #34495e;
        background: #f2f4f6;
        font-size: 10.5pt;
        font-weight: bold;
        color: #25313c;
        page-break-after: avoid;
    }

    .identity {
        width: 100%;
        border-collapse: collapse;
    }

    .identity td {
        padding: 4px 5px;
        vertical-align: top;
        border-bottom: 1px solid #edf0f2;
    }

    .identity .label {
        width: 185px;
        color: #687580;
    }

    table.data {
        width: 100%;
        border-collapse: collapse;
        margin-top: 4px;
    }

    table.data thead {
        display: table-header-group;
    }

    table.data tr {
        page-break-inside: avoid;
    }

    table.data th,
    table.data td {
        border: 1px solid #cfd5da;
        padding: 5px 6px;
        vertical-align: top;
    }

    table.data th {
        background: #eef1f4;
        color: #44515c;
        font-size: 8pt;
        text-align: left;
    }

    table.data td {
        font-size: 8.4pt;
    }

    .empty {
        color: #7a8791;
        text-align: center;
        font-style: italic;
        padding: 10px !important;
    }

    .footer {
        margin-top: 18px;
        padding-top: 7px;
        border-top: 1px solid #d7dce0;
        font-size: 7.7pt;
        color: #7a8791;
        page-break-inside: avoid;
    }
</style>
</head>
<body>

<table class="hero">
    <tr>
        <td class="photo-cell">
            <?php if (! empty($photo_data_uri)): ?>
                <img src="<?= esc($photo_data_uri, 'attr') ?>" class="photo" alt="Foto">
            <?php else: ?>
                <div class="photo-placeholder">FOTO 3x4</div>
            <?php endif; ?>
        </td>
        <td>
            <div class="eyebrow">Portofolio Personalia</div>
            <h1><?= esc($owner['nama'] ?? '-') ?></h1>
            <div class="position"><?= esc($roleLabel) ?></div>

            <?php if (! empty($owner['nip'])): ?>
                <div class="meta"><strong>NIP:</strong> <?= esc($owner['nip']) ?></div>
            <?php endif; ?>

            <?php if (! empty($owner['nuptk'])): ?>
                <div class="meta"><strong>NUPTK:</strong> <?= esc($owner['nuptk']) ?></div>
            <?php endif; ?>

            <div class="meta">
                <strong>Status:</strong> <?= esc($owner['status_kepegawaian'] ?: '-') ?>
            </div>

            <div class="school">
                <strong><?= esc($nama_sekolah ?? '') ?></strong>
                <?php if (! empty($alamat_sekolah)): ?>
                    <br><?= esc($alamat_sekolah) ?>
                <?php endif; ?>
            </div>
        </td>
    </tr>
</table>

<div class="section-title">1. Data Identitas Pribadi</div>
<table class="identity">
    <tr><td class="label">Nomor Induk Kependudukan (NIK)</td><td>: <?= esc($owner['nik'] ?: '-') ?></td></tr>
    <tr><td class="label">Tempat, Tanggal Lahir</td><td>: <?= esc($ttl) ?></td></tr>
    <tr><td class="label">Jenis Kelamin</td><td>: <?= esc($gender) ?></td></tr>
    <tr><td class="label">Agama</td><td>: <?= esc($owner['agama'] ?: '-') ?></td></tr>
    <tr><td class="label">Telepon</td><td>: <?= esc($owner['no_telepon'] ?: '-') ?></td></tr>
    <tr><td class="label">Email</td><td>: <?= esc($owner['email'] ?: '-') ?></td></tr>
</table>

<div class="section-title">2. Riwayat Pendidikan Formal</div>
<table class="data">
    <thead>
        <tr>
            <th style="width:12%">TINGKAT</th>
            <th>INSTITUSI &amp; PROGRAM STUDI</th>
            <th style="width:13%">TH. LULUS</th>
            <th style="width:23%">NO. IJAZAH</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($pendidikan)): ?>
        <tr><td colspan="4" class="empty">Belum ada riwayat pendidikan.</td></tr>
    <?php else: foreach ($pendidikan as $row): ?>
        <tr>
            <td><strong><?= esc($row['tingkat_pendidikan']) ?></strong></td>
            <td>
                <?= esc($row['nama_institusi']) ?>
                <?php if (! empty($row['program_studi'])): ?>
                    <br><?= esc($row['program_studi']) ?>
                <?php endif; ?>
            </td>
            <td><?= esc($row['tahun_lulus']) ?></td>
            <td><?= esc($row['no_ijazah'] ?: '-') ?></td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>

<div class="section-title">3. Riwayat Penugasan &amp; Jabatan</div>
<table class="data">
    <thead>
        <tr>
            <th style="width:25%">INSTANSI / SEKOLAH</th>
            <th>TUGAS / MAPEL</th>
            <th style="width:25%">PERIODE</th>
            <th style="width:22%">NO. SK PENUGASAN</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($penugasan)): ?>
        <tr><td colspan="4" class="empty">Belum ada riwayat penugasan.</td></tr>
    <?php else: foreach ($penugasan as $row): ?>
        <tr>
            <td><?= esc($row['instansi_penugasan']) ?></td>
            <td>
                <strong><?= esc($row['jabatan_tugas']) ?></strong>
                <?php if (! empty($row['mata_pelajaran'])): ?>
                    <br>Mapel: <?= esc($row['mata_pelajaran']) ?>
                <?php endif; ?>
            </td>
            <td>
                <?= esc($formatDate($row['tanggal_mulai'])) ?>
                -
                <?= esc(
                    ! empty($row['tanggal_selesai'])
                        ? $formatDate($row['tanggal_selesai'])
                        : 'Sekarang'
                ) ?>
            </td>
            <td><?= esc($row['no_sk_penugasan'] ?: '-') ?></td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>

<div class="section-title">4. Riwayat Kepangkatan</div>
<table class="data">
    <thead>
        <tr>
            <th>GOLONGAN / RUANG</th>
            <th>NAMA PANGKAT</th>
            <th style="width:23%">TMT PANGKAT</th>
            <th style="width:27%">NO. SK PANGKAT</th>
        </tr>
    </thead>
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
    Dokumen ini dibuat otomatis oleh SisisFour dari data personalia terbaru.
    <br>
    Dicetak pada: <?= esc($printed_at ?? '-') ?>
</div>

</body>
</html>
