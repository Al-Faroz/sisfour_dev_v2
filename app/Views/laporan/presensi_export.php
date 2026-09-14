<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<div id="laporanExportApp" data-base-url="<?= esc(base_url()) ?>">
    <div class="sisfour-page-header">
        <div class="sisfour-page-header__copy">
            <h4 class="fw-bold mb-1">Export Presensi</h4>
            <p class="text-muted mb-0">
                XLSX bulanan memuat Sesi Awal dan Sesi Akhir; total H/S/I/A tetap hanya Sesi Awal.
            </p>
        </div>
    </div>

    <?php if (empty($options['success'])): ?>
        <div class="alert alert-danger">
            <?= esc($options['message'] ?? 'Export tidak dapat dibuka.') ?>
        </div>
    <?php else: ?>
        <div class="card sisfour-filter-card">
            <div class="card-header">
                <h5 class="mb-1">Parameter Export</h5>
                <div class="small text-muted">Pilih tahun ajaran, kelas, dan periode export.</div>
            </div>
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-4">
                        <label class="form-label" for="exportTahun">Tahun Ajaran</label>
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
                        <label class="form-label" for="exportKelas">Kelas</label>
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
                        <label class="form-label" for="exportBulan">Bulan</label>
                        <input type="month" class="form-control" id="exportBulan" value="<?= esc($bulan) ?>">
                    </div>
                </div>

                <div class="sisfour-filter-actions mt-4">
                    <button type="button" class="btn btn-primary" id="btnExportBulanan">
                        <i class="bx bx-export me-1"></i> Export Bulanan XLSX
                    </button>
                    <button type="button" class="btn btn-outline-primary" id="btnExportSemester">
                        <i class="bx bx-export me-1"></i> Export Semester XLSX
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