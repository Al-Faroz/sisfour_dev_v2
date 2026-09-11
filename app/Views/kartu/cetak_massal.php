<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">

    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            width: 210mm;
            height: 297mm;
            font-family: Arial, "DejaVu Sans", sans-serif;
        }

        /*
         * A4 portrait = 210 x 297 mm
         * ID-1        = 85.60 x 53.98 mm
         *
         * 2 kolom:
         * 2 x 85.60 + 1 x 2.00 = 173.20 mm
         * sisa horizontal = 36.80 mm
         * margin kiri/kanan = 18.40 mm
         *
         * 5 baris:
         * 5 x 53.98 + 4 x 2.00 = 277.90 mm
         * sisa vertikal = 19.10 mm
         * margin atas/bawah = 9.55 mm
         *
         * Posisi dibuat absolute agar Dompdf tidak bergantung
         * pada perilaku whitespace/inline-block CSS.
         */
        .sheet {
            position: relative;
            width: 210mm;
            height: 297mm;
            margin: 0;
            padding: 0;
            page-break-after: always;
            overflow: hidden;
        }

        .sheet:last-child {
            page-break-after: auto;
        }

        .card-slot {
            position: absolute;
            width: 85.60mm;
            height: 53.98mm;
            overflow: hidden;
            background-repeat: no-repeat;
            background-position: 0 0;
            background-size: 85.60mm 53.98mm;
            font-size: 3mm;
        }

        /*
         * Koordinat grid:
         * X1 = 18.40
         * X2 = 18.40 + 85.60 + 2.00 = 106.00
         *
         * Y1 = 9.55
         * Y berikutnya = Y sebelumnya + 53.98 + 2.00
         */
        .slot-0 { left: 18.40mm; top: 9.55mm; }
        .slot-1 { left: 106.00mm; top: 9.55mm; }

        .slot-2 { left: 18.40mm; top: 65.53mm; }
        .slot-3 { left: 106.00mm; top: 65.53mm; }

        .slot-4 { left: 18.40mm; top: 121.51mm; }
        .slot-5 { left: 106.00mm; top: 121.51mm; }

        .slot-6 { left: 18.40mm; top: 177.49mm; }
        .slot-7 { left: 106.00mm; top: 177.49mm; }

        .slot-8 { left: 18.40mm; top: 233.47mm; }
        .slot-9 { left: 106.00mm; top: 233.47mm; }

        .front {
            color: #fff;
            background-image:
                url('<?= esc($background_front_data_uri ?? '') ?>');
        }

        .back {
            background-image:
                url('<?= esc($background_back_data_uri ?? '') ?>');
        }

        .photo {
            position: absolute;
            left: 64.33mm;
            top: 6.18mm;
            width: 17.79mm;
            height: 23.70mm;
            border: .25mm solid #fff;
            overflow: hidden;
        }

        .photo img {
            width: 17.79mm;
            height: 23.70mm;
            object-fit: cover;
        }

        .qr {
            position: absolute;
            left: 68.60mm;
            top: 31.72mm;
            width: 10.16mm;
            height: 10.16mm;
            padding: .34mm;
            background: #fff;
        }

        .qr img {
            width: 10.16mm;
            height: 10.16mm;
        }

        .code {
            position: absolute;
            left: 66.90mm;
            top: 42.76mm;
            width: 13.55mm;
            text-align: center;
            font-size: .76mm;
            color: #fff;
        }

        .name {
            position: absolute;
            left: 3.39mm;
            top: 14.82mm;
            width: 48.24mm;
            max-height: 8.30mm;
            overflow: hidden;
            font-weight: bold;
            line-height: 1.1;
            text-transform: uppercase;
            color: #fff;
        }

        .meta {
            position: absolute;
            left: 3.39mm;
            top: 28.78mm;
            width: 38.95mm;
            color: #fff;
            font-size: 1.60mm;
            font-weight: bold;
        }

        .meta table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta td {
            width: 50%;
            padding: 0 0 .85mm;
        }

        .label {
            display: block;
            font-size: .93mm;
            font-weight: normal;
            opacity: .8;
            text-transform: uppercase;
        }

        .ttl {
            position: absolute;
            left: 3.39mm;
            top: 38.94mm;
            width: 50.80mm;
            color: #fff;
            font-size: 1.44mm;
        }

        .alamat {
            position: absolute;
            left: 3.39mm;
            top: 41.48mm;
            width: 50.80mm;
            max-height: 3.72mm;
            overflow: hidden;
            color: #fff;
            font-size: 1.44mm;
        }

        /*
         * Garis sangat tipis hanya sebagai batas potong visual.
         * Tidak menambah ukuran fisik card-slot.
         */
        .crop-border {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            border: .08mm solid rgba(0, 0, 0, .18);
        }
    </style>
</head>

<body>
<?php foreach ($pages as $page): ?>
    <div class="sheet">
        <?php foreach ($page as $index => $item): ?>
            <?php if ($side === 'back'): ?>
                <div class="card-slot back slot-<?= (int) $index ?>">
                    <div class="crop-border"></div>
                </div>
            <?php else: ?>
                <div class="card-slot front slot-<?= (int) $index ?>">
                    <?php if (!empty($item['photo_data_uri'])): ?>
                        <div class="photo">
                            <img
                                src="<?= esc($item['photo_data_uri']) ?>"
                                alt=""
                            >
                        </div>
                    <?php endif; ?>

                    <div class="qr">
                        <img
                            src="<?= esc($item['qr_data_uri']) ?>"
                            alt=""
                        >
                    </div>

                    <div class="code">
                        <?= esc($item['card']['nomor_kartu']) ?>
                    </div>

                    <div
                        class="name"
                        style="font-size:<?= max(
                            2.6,
                            ((int) $item['name_font_size']) * 0.085
                        ) ?>mm"
                    >
                        <?= esc($item['nama_display']) ?>
                    </div>

                    <div class="meta">
                        <table>
                            <tr>
                                <td>
                                    <span class="label">NISN</span>
                                    <?= esc($item['nisn_display']) ?>
                                </td>

                                <td>
                                    <span class="label">Kelas</span>
                                    <?= esc($item['kelas_display']) ?>
                                </td>
                            </tr>

                            <tr>
                                <td>
                                    <span class="label">JK</span>
                                    <?= esc($item['jenis_kelamin_display']) ?>
                                </td>

                                <td>
                                    <span class="label">Tahun</span>
                                    <?= esc($item['tahun_ajaran_display']) ?>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="ttl">
                        <?= esc($item['ttl_display']) ?>
                    </div>

                    <div class="alamat">
                        <?= esc($item['alamat_display']) ?>
                    </div>

                    <div class="crop-border"></div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>
</body>
</html>
