<?= $this->extend('main') ?>

<?= $this->section('content') ?>

<div id="masterKelasApp" data-base-url="<?= esc(base_url()) ?>">
    <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Master Kelas</h4>
            <p class="text-muted mb-0">
                Kelola definisi kelas. Penempatan siswa, kenaikan kelas, mutasi,
                dan kelulusan dikelola pada menu Manajemen Siswa.
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="<?= base_url('master/kelas/recycle') ?>" class="btn btn-outline-secondary">
                <i class="bx bx-trash me-1"></i> Recycle Bin
            </a>
            <button type="button" class="btn btn-primary" id="btnTambahKelas">
                <i class="bx bx-plus me-1"></i> Tambah Kelas
            </button>
        </div>
    </div>

    <div class="alert alert-info">
        <i class="bx bx-info-circle me-1"></i>
        Nama kelas dibuat otomatis dari <strong>Tingkat + Rombel</strong>,
        misalnya tingkat 7 dan rombel A menjadi <strong>7-A</strong>.
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form id="formFilterKelas" class="row g-3">
                <div class="col-12 col-md-4">
                    <label class="form-label" for="filterTingkat">Tingkat</label>
                    <select class="form-select" id="filterTingkat" name="tingkat">
                        <option value="">Semua</option>
                        <option value="7">7</option>
                        <option value="8">8</option>
                        <option value="9">9</option>
                    </select>
                </div>
                <div class="col-12 col-md-5">
                    <label class="form-label" for="filterTahun">Tahun Ajaran</label>
                    <select class="form-select" id="filterTahun" name="id_tahun">
                        <option value="">Semua</option>
                        <?php foreach ($tahunOptions as $tahun): ?>
                            <option value="<?= (int) $tahun['id'] ?>">
                                <?= esc($tahun['nama_tahun'] . ' - ' . $tahun['semester'] . ((int) $tahun['status_aktif'] === 1 ? ' (Aktif)' : '')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-filter-alt me-1"></i> Terapkan
                    </button>
                    <button type="button" class="btn btn-outline-secondary" id="btnResetFilter">Reset</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="mb-0">Daftar Kelas</h5></div>
        <div class="card-datatable table-responsive">
            <table class="table table-hover align-middle" id="tableKelas">
                <thead>
                    <tr>
                        <th style="width:56px;">No.</th>
                        <th>Nama Kelas</th>
                        <th>Tingkat</th>
                        <th>Rombel</th>
                        <th>Tahun Ajaran</th>
                        <th>Jumlah Siswa</th>
                        <th style="min-width:140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="modalKelas" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="formKelas">
                    <?= csrf_field() ?>
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalKelasTitle">Tambah Kelas</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="kelasId">
                        <div class="mb-3">
                            <label class="form-label" for="tingkat">Tingkat</label>
                            <select class="form-select" id="tingkat" name="tingkat" required>
                                <option value="">Pilih</option>
                                <option value="7">7</option>
                                <option value="8">8</option>
                                <option value="9">9</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="rombel">Rombel</label>
                            <input type="text" class="form-control text-uppercase" id="rombel" name="rombel" maxlength="10" placeholder="Contoh: A" required>
                        </div>
                        <div>
                            <label class="form-label" for="id_tahun">Tahun Ajaran</label>
                            <select class="form-select" id="id_tahun" name="id_tahun" required>
                                <option value="">Pilih</option>
                                <?php foreach ($tahunOptions as $tahun): ?>
                                    <option value="<?= (int) $tahun['id'] ?>">
                                        <?= esc($tahun['nama_tahun'] . ' - ' . $tahun['semester'] . ((int) $tahun['status_aktif'] === 1 ? ' (Aktif)' : '')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="btnSimpanKelas">
                            <span class="spinner-border spinner-border-sm d-none me-1"></span>
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
