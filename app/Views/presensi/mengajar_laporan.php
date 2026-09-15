<?= $this->extend('main') ?>

<?= $this->section('content') ?>

<div
    id="jurnalLaporanApp"
    data-base-url="<?= esc(base_url()) ?>"
    data-tanggal-mulai="<?= esc($tanggalMulai ?? '') ?>"
    data-tanggal-selesai="<?= esc($tanggalSelesai ?? '') ?>"
>
    <div class="sisfour-page-header">
        <div class="sisfour-page-header__copy">
            <h4 class="fw-bold mb-1"><?= esc($title ?? 'Laporan Jurnal Mengajar') ?></h4>
            <p class="text-muted mb-0">
                Satu baris mewakili satu Jurnal. Detail siswa S/I/A dimuat saat dibutuhkan.
            </p>
        </div>

        <?php if (!empty($tahunAktif)): ?>
            <div class="sisfour-page-actions">
                <span class="badge bg-label-primary fs-6">
                    <?= esc($tahunAktif['nama_tahun'] ?? '') ?>
                    <?= esc($tahunAktif['semester'] ?? '') ?>
                </span>
            </div>
        <?php endif; ?>
    </div>

    <div class="card sisfour-filter-card mb-4">
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
                    <label class="form-label" for="laporanJurnalStatus">Status Guru</label>
                    <select class="form-select" id="laporanJurnalStatus" data-searchable-off="1">
                        <option value="">Semua Status</option>
                        <option value="Hadir">Hadir</option>
                        <option value="Izin">Izin</option>
                        <option value="Sakit">Sakit</option>
                    </select>
                </div>

                <div class="col-12 col-md-3 d-grid">
                    <button type="button" class="btn btn-primary sisfour-primary-action" id="btnMuatLaporanJurnal">
                        <i class="bx bx-filter-alt me-1"></i> Tampilkan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="laporanJurnalInfo" class="alert alert-info d-none" role="alert" aria-live="polite"></div>

    <div class="card" id="laporanJurnalCard">
        <div class="table-responsive d-none d-md-block">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Guru / Kelas</th>
                        <th>Pembelajaran</th>
                        <th>Status</th>
                        <th>Materi / Catatan</th>
                        <th>Siswa S/I/A</th>
                        <th class="text-end">Aksi</th>
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

        <div class="d-md-none p-2" id="laporanJurnalMobileList">
            <div class="sisfour-mobile-state text-muted">Memuat data...</div>
        </div>

        <div class="card-footer"></div>
    </div>
</div>

<div class="modal fade" id="jurnalDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header py-2">
                <div>
                    <h5 class="modal-title" id="jurnalDetailTitle">Detail Jurnal</h5>
                    <small class="text-muted" id="jurnalDetailMeta"></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body overflow-auto">
                <div id="jurnalDetailInfo" class="alert alert-info d-none" role="alert"></div>

                <div id="jurnalDetailContent" class="d-none">
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="badge bg-label-secondary" id="jurnalDetailStatus"></span>
                        <span class="badge bg-label-warning">S <span id="jurnalDetailSakit">0</span></span>
                        <span class="badge bg-label-info">I <span id="jurnalDetailIzin">0</span></span>
                        <span class="badge bg-label-danger">A <span id="jurnalDetailAlpha">0</span></span>
                    </div>

                    <div class="mb-3">
                        <div class="text-muted small mb-1">Materi / Keterangan</div>
                        <div class="border rounded p-3 text-break" id="jurnalDetailMateri">-</div>
                    </div>

                    <div class="mb-4">
                        <div class="text-muted small mb-1">Catatan</div>
                        <div class="border rounded p-3 text-break" id="jurnalDetailCatatan">-</div>
                    </div>

                    <div>
                        <h6 class="mb-2">Siswa Tidak Mengikuti Pembelajaran</h6>
                        <div class="d-flex flex-column gap-2" id="jurnalDetailStudents"></div>
                        <div class="text-muted small py-2 d-none" id="jurnalDetailEmpty">
                            Tidak ada siswa Sakit/Izin/Alpha pada Jurnal ini.
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>