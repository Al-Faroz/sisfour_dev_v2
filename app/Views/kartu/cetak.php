<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 0;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            font-family: Poppins, Arial, "DejaVu Sans", sans-serif;
        }

        .page {
            position: relative;
            width: 1011px;
            height: 638px;
            overflow: hidden;
            background-size: 1011px 638px;
            background-repeat: no-repeat;
            background-position: 0 0;
        }

        .page-front {
            background-image: url('<?= esc($background_front_data_uri) ?>');
            page-break-after: always;
        }

        .page-back {
            background-image: url('<?= esc($background_back_data_uri) ?>');
        }

        .photo {
            position: absolute;
            left: 760px;
            top: 73px;
            width: 210px;
            height: 280px;
            border: 3px solid #fff;
            overflow: hidden;
        }

        .photo img {
            width: 210px;
            height: 280px;
            object-fit: cover;
        }

        .qr {
            position: absolute;
            left: 810px;
            top: 375px;
            width: 120px;
            height: 120px;
            background: #fff;
            padding: 4px;
        }

        .qr img {
            width: 120px;
            height: 120px;
        }

        .code {
            position: absolute;
            left: 790px;
            top: 505px;
            width: 160px;
            text-align: center;
            font-size: 9px;
            letter-spacing: .5px;
            color: #fff;
        }

        .name {
            position: absolute;
            left: 40px;
            top: 175px;
            width: 570px;
            max-height: 98px;
            font-weight: 800;
            line-height: 1.15;
            text-transform: uppercase;
            color: #fff;
            overflow: hidden;
        }

        .meta {
            position: absolute;
            left: 40px;
            top: 340px;
            width: 460px;
            color: #fff;
            font-size: 19px;
            font-weight: 700;
        }

        .meta table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta td {
            width: 50%;
            padding: 0 0 10px;
        }

        .label {
            display: block;
            font-size: 11px;
            font-weight: 500;
            opacity: .75;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .ttl {
            position: absolute;
            left: 40px;
            top: 460px;
            width: 600px;
            color: #fff;
            font-size: 17px;
            font-weight: 500;
        }

        .alamat {
            position: absolute;
            left: 40px;
            top: 490px;
            width: 600px;
            max-height: 44px;
            overflow: hidden;
            color: #fff;
            font-size: 17px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="page page-front">
        <?php if ($photo_data_uri): ?>
            <div class="photo">
                <img src="<?= esc($photo_data_uri) ?>" alt="">
            </div>
        <?php endif; ?>

        <div class="qr">
            <img src="<?= esc($qr_data_uri) ?>" alt="">
        </div>

        <div class="code">
            <?= esc($card['nomor_kartu']) ?>
        </div>

        <div
            class="name"
            style="font-size:<?= (int) $name_font_size ?>px"
        >
            <?= esc($nama_display) ?>
        </div>

        <div class="meta">
            <table>
                <tr>
                    <td>
                        <span class="label">NISN</span>
                        <?= esc($nisn_display) ?>
                    </td>
                    <td>
                        <span class="label">Kelas</span>
                        <?= esc($kelas_display) ?>
                    </td>
                </tr>
                <tr>
                    <td>
                        <span class="label">Jenis Kelamin</span>
                        <?= esc($jenis_kelamin_display) ?>
                    </td>
                    <td>
                        <span class="label">Tahun Ajaran</span>
                        <?= esc($tahun_ajaran_display) ?>
                    </td>
                </tr>
            </table>
        </div>

        <div class="ttl">
            <?= esc($ttl_display) ?>
        </div>

        <div class="alamat">
            <?= esc($alamat_display) ?>
        </div>
    </div>

    <div class="page page-back"></div>
</body>
</html>
