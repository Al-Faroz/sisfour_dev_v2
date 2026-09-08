<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<div id="laporanExportApp" data-base-url="<?= esc(base_url()) ?>">
    <div class="mb-4">
        <h4 class="fw-bold mb-1">Export Presensi</h4>
        <p class="text-muted mb-0">XLSX bulanan memuat Sesi Awal dan Sesi Akhir; total H/S/I/A tetap hanya Sesi Awal.</p>
    </div>

    <?php if (empty($options['success'])): ?>
        <div class="alert alert-danger"><?= esc($options['message'] ?? 'Export tidak dapat dibuka.') ?></div>
    <?php else: ?>
        <div class="card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label">Tahun Ajaran</label>
                        <select class="form-select" id="exportTahun">
                            <?php foreach (($options['tahun'] ?? []) as $tahun): ?>
                                <option
                                    value="<?= (int) $tahun['id'] ?>"
                                    <?= (int) $selectedTahun === (int) $tahun['id'] ? 'selected' : '' ?>
                                >
                                    <?= esc($tahun['nama_tahun'] . ' - ' . $tahun['semester']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label">Kelas</label>
                        <select class="form-select" id="exportKelas">
                            <option value="">Pilih Kelas</option>
                            <?php foreach (($options['kelas'] ?? []) as $kelas): ?>
                                <option value="<?= (int) $kelas['id'] ?>">
                                    <?= esc($kelas['nama_kelas']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label">Bulan</label>
                        <input type="month" class="form-control" id="exportBulan" value="<?= esc($bulan) ?>">
                    </div>
                </div>

                <hr>

                <div class="d-flex flex-column flex-md-row gap-2">
                    <button type="button" class="btn btn-primary" id="btnExportBulanan">
                        Export Bulanan XLSX
                    </button>
                    <button type="button" class="btn btn-outline-primary" id="btnExportSemester">
                        Export Semester XLSX
                    </button>
                </div>

                <div class="form-text mt-3">
                    Export Semester mengikuti semester pada Tahun Ajaran yang dipilih:
                    Ganjil = Juli–Desember, Genap = Januari–Juni.
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
