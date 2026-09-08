<?= $this->extend('main') ?>

<?= $this->section('content') ?>

<div
    id="rekapPresensiSiswaApp"
    data-base-url="<?= esc(base_url()) ?>"
    data-self-view="<?= !empty($isSiswaSelfView) ? '1' : '0' ?>"
>
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Rekap Presensi Siswa</h4>
            <p class="text-muted mb-0">
                Histori Presensi ditampilkan server-side sesuai permission dan scope.
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
                        <select class="form-select" id="rekapSesi">
                            <option value="">Semua</option>
                            <option value="Sesi Awal">Sesi Awal</option>
                            <option value="Sesi Akhir">Sesi Akhir</option>
                        </select>
                    </div>

                    <div class="col-6 col-md-2">
                        <label class="form-label" for="rekapStatus">Status</label>
                        <select class="form-select" id="rekapStatus">
                            <option value="">Semua</option>
                            <option value="Hadir">Hadir</option>
                            <option value="Sakit">Sakit</option>
                            <option value="Izin">Izin</option>
                            <option value="Alpha">Alpha</option>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-filter-alt me-1"></i> Tampilkan
                    </button>
                    <button type="button" class="btn btn-outline-secondary" id="btnResetRekap">Reset</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Histori Presensi</h5>
            <span class="badge bg-label-secondary" id="rekapTotal">0 data</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Siswa</th>
                        <th>Sesi</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="rekapTableBody">
                    <tr><td colspan="4" class="text-center text-muted py-4">Gunakan filter untuk menampilkan data.</td></tr>
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center">
            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnRekapPrev" disabled>Sebelumnya</button>
            <span class="small text-muted" id="rekapPageInfo">Halaman 1</span>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnRekapNext" disabled>Berikutnya</button>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
