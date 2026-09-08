<?= $this->extend('main') ?>

<?= $this->section('content') ?>

<div
    id="jurnalLaporanApp"
    data-base-url="<?= esc(base_url()) ?>"
    data-tanggal-mulai="<?= esc($tanggalMulai ?? '') ?>"
    data-tanggal-selesai="<?= esc($tanggalSelesai ?? '') ?>"
>
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1"><?= esc($title ?? 'Laporan Jurnal Mengajar') ?></h4>
            <p class="text-muted mb-0">
                Histori Jurnal ditampilkan sesuai permission dan scope user.
            </p>
        </div>

        <?php if (!empty($tahunAktif)): ?>
            <span class="badge bg-label-primary fs-6">
                <?= esc($tahunAktif['nama_tahun'] ?? '') ?>
                <?= esc($tahunAktif['semester'] ?? '') ?>
            </span>
        <?php endif; ?>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label" for="laporanJurnalMulai">Tanggal Mulai</label>
                    <input
                        type="date"
                        class="form-control"
                        id="laporanJurnalMulai"
                        value="<?= esc($tanggalMulai ?? '') ?>"
                    >
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label" for="laporanJurnalSelesai">Tanggal Selesai</label>
                    <input
                        type="date"
                        class="form-control"
                        id="laporanJurnalSelesai"
                        value="<?= esc($tanggalSelesai ?? '') ?>"
                    >
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label" for="laporanJurnalStatus">Status</label>
                    <select class="form-select" id="laporanJurnalStatus">
                        <option value="">Semua Status</option>
                        <option value="Hadir">Hadir</option>
                        <option value="Izin">Izin</option>
                        <option value="Sakit">Sakit</option>
                    </select>
                </div>

                <div class="col-12 col-md-3 d-grid">
                    <button type="button" class="btn btn-primary" id="btnMuatLaporanJurnal">
                        <i class="bx bx-filter-alt me-1"></i> Tampilkan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="laporanJurnalInfo" class="alert alert-info d-none" role="alert"></div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Guru</th>
                        <th>Kelas</th>
                        <th>Mapel</th>
                        <th>Jam / Sesi</th>
                        <th>Status</th>
                        <th>Materi / Keterangan</th>
                    </tr>
                </thead>
                <tbody id="laporanJurnalBody">
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            Memuat data...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="card-footer d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <small class="text-muted" id="laporanJurnalMeta"></small>

            <div class="btn-group">
                <button type="button" class="btn btn-outline-secondary" id="btnJurnalPrev">
                    Sebelumnya
                </button>
                <button type="button" class="btn btn-outline-secondary" id="btnJurnalNext">
                    Berikutnya
                </button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
