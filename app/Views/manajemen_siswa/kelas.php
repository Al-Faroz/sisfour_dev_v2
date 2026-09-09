<?= $this->extend('main') ?>

<?= $this->section('content') ?>

<div id="manajemenKelasSiswaApp" data-base-url="<?= esc(base_url()) ?>">
    <div class="mb-4">
        <h4 class="fw-bold mb-1">Penempatan / Pindah Kelas</h4>
        <p class="text-muted mb-0">Tempatkan siswa yang belum memiliki kelas atau pindahkan siswa antar kelas pada tahun ajaran aktif.</p>
    </div>

    <?php if ($tahunAktif): ?>
        <div class="alert alert-info">
            <i class="bx bx-calendar me-1"></i>
            Tahun ajaran aktif:
            <strong><?= esc($tahunAktif['nama_tahun'] . ' - ' . $tahunAktif['semester']) ?></strong>
        </div>
    <?php else: ?>
        <div class="alert alert-danger">Tahun ajaran aktif belum tersedia.</div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-body">
            <form id="formFilterKelasSiswa" class="row g-3">
                <div class="col-md-7">
                    <label class="form-label" for="filterQ">Cari Siswa</label>
                    <input type="text" class="form-control" id="filterQ" name="q" placeholder="Nama, NISN, atau NIK">
                </div>
                <div class="col-md-5">
                    <label class="form-label" for="filterKelas">Kelas Saat Ini</label>
                    <select class="form-select" id="filterKelas" name="kelas">
                        <option value="">Semua</option>
                        <option value="tanpa">Belum Ada Kelas</option>
                        <?php foreach ($kelasOptions as $kelas): ?>
                            <option value="<?= (int) $kelas['id'] ?>"><?= esc($kelas['nama_kelas']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bx bx-filter-alt me-1"></i> Terapkan</button>
                    <button type="button" class="btn btn-outline-secondary" id="btnResetFilter">Reset</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="mb-0">Daftar Siswa Aktif</h5></div>
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="tableKelasSiswa">
                <thead>
                    <tr>
                        <th style="width:56px;">No.</th>
                        <th>Nama</th>
                        <th>NISN</th>
                        <th>NIK</th>
                        <th>JK</th>
                        <th>Kelas Saat Ini</th>
                        <th style="width:150px;">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="modalAturKelas" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="formAturKelas">
                    <?= csrf_field() ?>
                    <input type="hidden" id="idSiswaKelas">
                    <div class="modal-header">
                        <h5 class="modal-title">Atur Kelas Siswa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Siswa</label>
                            <input type="text" class="form-control" id="namaSiswaKelas" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kelas Saat Ini</label>
                            <input type="text" class="form-control" id="kelasSaatIni" readonly>
                        </div>
                        <div>
                            <label class="form-label" for="idKelasTujuan">Kelas Tujuan</label>
                            <select class="form-select" id="idKelasTujuan" name="id_kelas_tujuan" required>
                                <option value="">Pilih kelas</option>
                                <?php foreach ($kelasOptions as $kelas): ?>
                                    <option value="<?= (int) $kelas['id'] ?>"><?= esc($kelas['nama_kelas']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
