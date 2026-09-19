<?= $this->extend('main') ?>

<?= $this->section('content') ?>

<div
    id="rekapPresensiSiswaApp"
    data-base-url="<?= esc(base_url()) ?>"
    data-self-view="<?= !empty($isSiswaSelfView) ? '1' : '0' ?>"
>
    <div class="sisfour-page-header">
        <div class="sisfour-page-header__copy">
            <h4 class="fw-bold mb-1">Rekap Presensi Siswa</h4>
            <p class="text-muted mb-0">
                Histori Presensi ditampilkan server-side sesuai permission dan scope.
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
            <form id="formFilterRekap" class="row g-3 align-items-end">
                <?php if (empty($isSiswaSelfView)): ?>
                    <div class="col-12 col-md-4">
                        <label class="form-label" for="rekapKelas">Kelas</label>
                        <select class="form-select" id="rekapKelas">
                            <option value="">Pilih kelas</option>
                            <?php foreach (($kelasOptions ?? []) as $kelas): ?>
                                <option value="<?= (int) $kelas['id'] ?>">
                                    <?= esc($kelas['nama_kelas']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="col-6 col-md-2">
                    <label class="form-label" for="rekapTanggalMulai">Dari</label>
                    <input type="date" class="form-control" id="rekapTanggalMulai" value="<?= esc($tanggalMulai ?? '') ?>">
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label" for="rekapTanggalSelesai">Sampai</label>
                    <input type="date" class="form-control" id="rekapTanggalSelesai" value="<?= esc($tanggalSelesai ?? '') ?>">
                </div>

                <?php if (empty($isSiswaSelfView)): ?>
                    <div class="col-6 col-md-2">
                        <label class="form-label" for="rekapSesi">Sesi</label>
                        <select class="form-select" id="rekapSesi" data-searchable-off="1">
                            <option value="">Semua</option>
                            <option value="Sesi Awal">Sesi Awal</option>
                            <option value="Sesi Akhir">Sesi Akhir</option>
                        </select>
                    </div>

                    <div class="col-6 col-md-2">
                        <label class="form-label" for="rekapStatus">Status</label>
                        <select class="form-select" id="rekapStatus" data-searchable-off="1">
                            <option value="">Semua</option>
                            <option value="Hadir">Hadir</option>
                            <option value="Sakit">Sakit</option>
                            <option value="Izin">Izin</option>
                            <option value="Alpha">Alpha</option>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="col-12 sisfour-filter-actions">
                    <button type="button" class="btn btn-outline-secondary sisfour-touch-target--compact" id="btnResetRekap">Reset</button>
                    <button type="submit" class="btn btn-primary sisfour-primary-action">
                        <i class="bx bx-filter-alt me-1"></i> Tampilkan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card sisfour-table-card">
        <div class="card-header sisfour-section-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">Histori Presensi</h5>
            <span class="badge bg-label-secondary" id="rekapTotal">0 data</span>
        </div>
        <div id="rekapMobileList" class="d-md-none list-group list-group-flush">
            <div class="list-group-item sisfour-mobile-state text-muted">Gunakan filter untuk menampilkan data.</div>
        </div>
        <div class="d-none d-md-block table-responsive">
            <table class="table table-hover align-middle mb-0" id="tableRekapPresensi">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Siswa</th>
                        <th>Sesi</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="rekapTableBody">
                    <tr class="sisfour-empty-row"><td colspan="4" class="text-muted">Gunakan filter untuk menampilkan data.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>