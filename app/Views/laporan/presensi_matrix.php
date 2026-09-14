<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<div id="laporanMatrixApp" data-base-url="<?= esc(base_url()) ?>">
    <div class="sisfour-page-header">
        <div class="sisfour-page-header__copy">
            <h4 class="fw-bold mb-1">Matrix Presensi</h4>
            <p class="text-muted mb-0">Presensi resmi menggunakan Sesi Awal. Tanda “-” bukan Alpha.</p>
        </div>
    </div>

    <?php if (empty($options['success'])): ?>
        <div class="alert alert-danger"><?= esc($options['message'] ?? 'Laporan tidak dapat dibuka.') ?></div>
    <?php else: ?>
        <div class="card sisfour-filter-card mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-3">
                        <label class="form-label" for="matrixTahun">Tahun Ajaran</label>
                        <select class="form-select" id="matrixTahun">
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

                    <div class="col-12 col-md-3">
                        <label class="form-label" for="matrixKelas">Kelas</label>
                        <select class="form-select" id="matrixKelas">
                            <option value="">Pilih Kelas</option>
                            <?php foreach (($options['kelas'] ?? []) as $kelas): ?>
                                <option
                                    value="<?= (int) $kelas['id'] ?>"
                                    <?= (int) $selectedKelas === (int) $kelas['id'] ? 'selected' : '' ?>
                                >
                                    <?= esc($kelas['nama_kelas']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label" for="matrixBulan">Bulan</label>
                        <input type="month" class="form-control" id="matrixBulan" value="<?= esc($bulan) ?>">
                    </div>

                    <div class="col-12 col-md-3 d-grid">
                        <button type="button" class="btn btn-primary" id="btnMatrixMuat">
                            <i class="bx bx-table me-1"></i> Muat Matrix
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="matrixAlert" class="alert alert-info d-none" role="alert"></div>

        <div class="card sisfour-table-card">
            <div class="card-header">
                <h5 class="mb-0" id="matrixTitle">Matrix Presensi</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle mb-0" id="matrixTable">
                    <thead id="matrixHead"></thead>
                    <tbody id="matrixBody">
                        <tr class="sisfour-empty-row">
                            <td class="text-muted">Pilih filter lalu muat Matrix.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>