<!doctype html>
<html lang="id">
<head>
<?= $this->include('_header') ?>
</head>
<body class="bg-body-tertiary">
<div class="container py-4 py-md-5" id="ptspPublicApp" data-base-url="<?= esc(base_url()) ?>">
    <div class="text-center mb-4">
        <span class="badge bg-label-primary mb-2">Pelayanan Terpadu Satu Pintu</span>
        <h1 class="h3 fw-bold mb-2"><?= esc($systemSettings['nama_sekolah'] ?? 'MTsN 4 Jombang') ?></h1>
        <?php if (!empty($systemSettings['alamat_sekolah'])): ?><p class="text-muted mb-0"><?= esc($systemSettings['alamat_sekolah']) ?></p><?php endif; ?>
    </div>

    <div id="ptspPublicAlert" class="alert d-none" role="alert"></div>

    <ul class="nav nav-pills nav-fill gap-2 mb-4" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#ptspLayanan" type="button">Layanan</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#ptspPolling" type="button">Polling Kepuasan</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#ptspPengaduan" type="button">Pengaduan</button></li>
    </ul>

    <div class="tab-content p-0 bg-transparent shadow-none">
        <div class="tab-pane fade show active" id="ptspLayanan">
            <div class="card"><div class="card-body p-4">
                <h2 class="h5 fw-bold">Form Layanan PTSP</h2><p class="text-muted">Isi data berikut untuk mengajukan layanan.</p>
                <form id="formPublicLayanan" class="row g-3">
                    <div class="col-12 col-md-6"><label class="form-label">Nama Lengkap</label><input class="form-control" name="nama_lengkap" maxlength="150" required></div>
                    <div class="col-12 col-md-6"><label class="form-label">Kategori Pemohon</label><select class="form-select" name="kategori_pemohon" required><option value="">Pilih kategori</option><?php foreach (($options['kategori'] ?? []) as $opt): ?><option value="<?= esc($opt) ?>"><?= esc($opt) ?></option><?php endforeach; ?></select></div>
                    <div class="col-12 col-md-6"><label class="form-label">Nomor WhatsApp</label><input class="form-control" name="nomor_whatsapp" maxlength="25" inputmode="tel" required></div>
                    <div class="col-12 col-md-6"><label class="form-label">Jenis Layanan</label><select class="form-select" name="jenis_layanan" required><option value="">Pilih layanan</option><?php foreach (($options['jenis_layanan'] ?? []) as $opt): ?><option value="<?= esc($opt) ?>"><?= esc($opt) ?></option><?php endforeach; ?></select></div>
                    <div class="col-12"><label class="form-label">Tujuan / Keterangan</label><textarea class="form-control" name="tujuan_keterangan" rows="4" maxlength="2000" required></textarea></div>
                    <div class="col-12"><button class="btn btn-primary w-100 w-md-auto" type="submit">Kirim Pengajuan</button></div>
                </form>
            </div></div>
        </div>

        <div class="tab-pane fade" id="ptspPolling">
            <div class="card"><div class="card-body p-4">
                <h2 class="h5 fw-bold">Polling Kepuasan</h2><p class="text-muted">Masukan Anda membantu peningkatan pelayanan. Pengisian berulang diperbolehkan.</p>
                <form id="formPublicPolling" class="row g-3">
                    <div class="col-12 col-md-6"><label class="form-label">Nama Lengkap <span class="text-muted">(opsional)</span></label><input class="form-control" name="nama_lengkap" maxlength="150"></div>
                    <div class="col-12 col-md-6"><label class="form-label">Kategori Responden <span class="text-muted">(opsional)</span></label><select class="form-select" name="kategori_responden"><option value="">Tidak diisi</option><?php foreach (($options['kategori'] ?? []) as $opt): ?><option value="<?= esc($opt) ?>"><?= esc($opt) ?></option><?php endforeach; ?></select></div>
                    <div class="col-12 col-md-6"><label class="form-label">Nomor WhatsApp <span class="text-muted">(opsional)</span></label><input class="form-control" name="nomor_whatsapp" maxlength="25" inputmode="tel"></div>
                    <div class="col-12 col-md-6"><label class="form-label">Tingkat Kepuasan</label><select class="form-select" name="tingkat_kepuasan" required><option value="">Pilih tingkat kepuasan</option><?php foreach (($options['kepuasan'] ?? []) as $opt): ?><option value="<?= esc($opt) ?>"><?= esc($opt) ?></option><?php endforeach; ?></select></div>
                    <div class="col-12"><label class="form-label">Masukan &amp; Saran <span class="text-muted">(opsional)</span></label><textarea class="form-control" name="masukan_saran" rows="4" maxlength="3000"></textarea></div>
                    <div class="col-12"><button class="btn btn-primary w-100 w-md-auto" type="submit">Kirim Polling</button></div>
                </form>
            </div></div>
        </div>

        <div class="tab-pane fade" id="ptspPengaduan">
            <div class="card"><div class="card-body p-4">
                <h2 class="h5 fw-bold">Form Pengaduan</h2><p class="text-muted">Form ini anonim dan tidak meminta nama atau kontak pelapor.</p>
                <form id="formPublicPengaduan" class="row g-3" enctype="multipart/form-data">
                    <div class="col-12"><label class="form-label d-block">Klasifikasi Laporan</label><div class="row g-2"><?php foreach (($options['klasifikasi'] ?? []) as $i => $opt): ?><div class="col-12 col-md-4"><div class="form-check"><input class="form-check-input" type="checkbox" name="klasifikasi[]" value="<?= esc($opt) ?>" id="pubKlas<?= (int) $i ?>"><label class="form-check-label" for="pubKlas<?= (int) $i ?>"><?= esc($opt) ?></label></div></div><?php endforeach; ?></div></div>
                    <div class="col-12"><label class="form-label">Judul Laporan</label><input class="form-control" name="judul_laporan" maxlength="200" required></div>
                    <div class="col-12"><label class="form-label">Isi Laporan</label><textarea class="form-control" name="isi_laporan" rows="6" maxlength="5000" required></textarea></div>
                    <div class="col-12 col-md-6"><label class="form-label">Tanggal Kejadian <span class="text-muted">(opsional)</span></label><input class="form-control" type="date" name="tanggal_kejadian"></div>
                    <div class="col-12 col-md-6"><label class="form-label">Lampiran <span class="text-muted">(opsional)</span></label><input class="form-control" type="file" name="lampiran" accept=".pdf,.png,.jpg,.jpeg,application/pdf,image/png,image/jpeg"><div class="form-text">PDF/PNG/JPG/JPEG, maksimal 5 MB.</div></div>
                    <div class="col-12"><button class="btn btn-primary w-100 w-md-auto" type="submit">Kirim Laporan</button></div>
                </form>
            </div></div>
        </div>
    </div>
</div>

<div id="ptspReceipt" class="ptsp-receipt d-none" aria-hidden="true">
    <div class="text-center fw-bold mb-2">BUKTI PENGISIAN LAYANAN PTSP</div>
    <div><?= esc($systemSettings['nama_sekolah'] ?? 'MTsN 4 Jombang') ?></div>
    <hr>
    <div id="ptspReceiptBody"></div>
    <hr>
    <div class="text-center">Pengajuan berhasil diterima.</div>
    <div class="text-center small">Bukti ini bukan nomor antrean atau kode tracking.</div>
</div>

<script src="<?= sisfour_asset_url('assets/vendor/libs/jquery/jquery.js') ?>"></script>
<script src="<?= sisfour_asset_url('assets/vendor/libs/popper/popper.js') ?>"></script>
<script src="<?= sisfour_asset_url('assets/vendor/js/bootstrap.js') ?>"></script>
<script src="<?= sisfour_asset_url('assets/js/csrf-fetch.js') ?>"></script>
<script src="<?= sisfour_asset_url('assets/js/ptsp/public.js') ?>"></script>
</body>
</html>
