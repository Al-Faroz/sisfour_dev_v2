<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<div
    id="laporanMatrixApp"
    data-base-url="<?= esc(base_url()) ?>"
>
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Matrix Presensi</h4>
            <p class="text-muted mb-0">Presensi resmi menggunakan Sesi Awal. Tanda “-” bukan Alpha.</p>
        </div>
    </div>

    <?php if (empty($options['success'])): ?>
        <div class="alert alert-danger"><?= esc($options['message'] ?? 'Laporan tidak dapat dibuka.') ?></div>
    <?php else: ?>
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-3">
                        <label class="form-label">Tahun Ajaran</label>
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
                        <label class="form-label">Kelas</label>
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
                        <label class="form-label">Bulan</label>
                        <input type="month" class="form-control" id="matrixBulan" value="<?= esc($bulan) ?>">
                    </div>

                    <div class="col-12 col-md-3 d-grid">
                        <button type="button" class="btn btn-primary" id="btnMatrixMuat">
                            Muat Matrix
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="matrixAlert" class="alert alert-info d-none"></div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0" id="matrixTitle">Matrix Presensi</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle" id="matrixTable">
                    <thead id="matrixHead"></thead>
                    <tbody id="matrixBody">
                        <tr>
                            <td class="text-center text-muted py-4">Pilih filter lalu muat Matrix.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
