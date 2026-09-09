<?= $this->extend('main') ?>

<?= $this->section('content') ?>

<div
    id="masterSiswaApp"
    data-base-url="<?= esc(base_url()) ?>"
    data-can-edit="<?= !empty($canEdit) ? '1' : '0' ?>"
    data-can-edit-nisn="<?= !empty($canEditNisn) ? '1' : '0' ?>"
    data-can-manage="<?= !empty($canManage) ? '1' : '0' ?>"
    data-can-import-export="<?= !empty($canImportExport) ? '1' : '0' ?>"
>
    <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Master Siswa</h4>
            <p class="text-muted mb-0">
                Kelola biodata, kelas aktif, akun, foto, status, dan mutasi siswa.
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <?php if (!empty($canManage)): ?>
                <a href="<?= base_url('master/siswa/recycle') ?>" class="btn btn-outline-secondary">
                    <i class="bx bx-trash me-1"></i> Recycle Bin
                </a>

                <button type="button" class="btn btn-primary" id="btnTambahSiswa">
                    <i class="bx bx-plus me-1"></i> Tambah Siswa
                </button>
            <?php endif; ?>

            <?php if (!empty($canImportExport)): ?>
                <a href="<?= base_url('master/siswa/template') ?>" class="btn btn-outline-primary">
                    <i class="bx bx-download me-1"></i> Template
                </a>

                <button type="button" class="btn btn-outline-primary" id="btnImportSiswa">
                    <i class="bx bx-import me-1"></i> Import
                </button>

                <a href="#" class="btn btn-outline-success" id="btnExportSiswa">
                    <i class="bx bx-export me-1"></i> Export
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($canEdit) && empty($canEditNisn)): ?>
        <div class="alert alert-info">
            <i class="bx bx-info-circle me-1"></i>
            Akses Wali Kelas aktif: biodata dan foto siswa kelas yang diampu dapat diedit,
            tetapi NISN tidak dapat diubah.
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-body">
            <form id="formFilterSiswa" class="row g-3">
                <div class="col-12 col-md-4 col-xl">
                    <label class="form-label" for="filterNama">Nama</label>
                    <input type="text" class="form-control" id="filterNama" name="nama" placeholder="Cari nama">
                </div>

                <div class="col-12 col-md-4 col-xl">
                    <label class="form-label" for="filterNik">NIK</label>
                    <input type="text" class="form-control" id="filterNik" name="nik" maxlength="16" placeholder="Cari NIK">
                </div>

                <div class="col-12 col-md-4 col-xl">
                    <label class="form-label" for="filterNisn">NISN</label>
                    <input type="text" class="form-control" id="filterNisn" name="nisn" placeholder="Cari NISN">
                </div>

                <div class="col-12 col-md-6 col-xl">
                    <label class="form-label" for="filterKelas">Kelas</label>
                    <select class="form-select" id="filterKelas" name="id_kelas">
                        <option value="">Semua</option>
                        <?php foreach ($kelasOptions as $kelas): ?>
                            <option value="<?= (int) $kelas['id'] ?>">
                                <?= esc($kelas['nama_kelas']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-md-6 col-xl">
                    <label class="form-label" for="filterStatus">Status</label>
                    <select class="form-select" id="filterStatus" name="status_aktif">
                        <option value="">Semua</option>
                        <option value="Aktif">Aktif</option>
                        <option value="Lulus">Lulus</option>
                        <option value="Pindah">Pindah</option>
                        <option value="Keluar">Keluar</option>
                    </select>
                </div>

                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-filter-alt me-1"></i> Terapkan
                    </button>

                    <button type="button" class="btn btn-outline-secondary" id="btnResetFilter">
                        Reset
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Daftar Siswa</h5>

            <?php if (empty($canEdit) && empty($canManage)): ?>
                <span class="badge bg-label-info">Readonly</span>
            <?php endif; ?>
        </div>

        <div class="card-datatable table-responsive">
            <table class="table table-hover align-middle" id="tableSiswa">
                <thead>
                    <tr>
                        <th style="width: 56px;">No.</th>
                        <th>Siswa</th>
                        <th>NIK / NISN</th>
                        <th>Kelas</th>
                        <th>JK</th>
                        <th>Status</th>
                        <th>Kontak</th>
                        <?php if (!empty($canEdit) || !empty($canManage)): ?>
                            <th style="min-width: 190px;">Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <?php if (!empty($canEdit) || !empty($canManage)): ?>
        <div class="modal fade" id="modalSiswa" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <form id="formSiswa">
                        <?= csrf_field() ?>

                        <div class="modal-header">
                            <h5 class="modal-title" id="modalSiswaTitle">Tambah Siswa</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>

                        <div class="modal-body">
                            <input type="hidden" id="siswaId">

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label" for="nik">NIK <span class="text-danger">*</span></label>
                                    <input
                                        type="text"
                                        inputmode="numeric"
                                        pattern="[0-9]{16}"
                                        maxlength="16"
                                        class="form-control"
                                        id="nik"
                                        name="nik"
                                        required
                                    >
                                    <div class="form-text">Tepat 16 digit.</div>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" for="nisn">NISN <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nisn" name="nisn" maxlength="20" required>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" for="nama">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nama" name="nama" maxlength="150" required>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" for="jenis_kelamin">Jenis Kelamin <span class="text-danger">*</span></label>
                                    <select class="form-select" id="jenis_kelamin" name="jenis_kelamin" required>
                                        <option value="">Pilih</option>
                                        <option value="L">Laki-laki</option>
                                        <option value="P">Perempuan</option>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" for="tempat_lahir">Tempat Lahir</label>
                                    <input type="text" class="form-control" id="tempat_lahir" name="tempat_lahir" maxlength="100">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" for="tanggal_lahir">Tanggal Lahir</label>
                                    <input type="date" class="form-control" id="tanggal_lahir" name="tanggal_lahir">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" for="no_telepon">No. Telepon</label>
                                    <input type="text" class="form-control" id="no_telepon" name="no_telepon" maxlength="20">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" for="nomor_kip_pip">Nomor KIP/PIP</label>
                                    <input type="text" class="form-control" id="nomor_kip_pip" name="nomor_kip_pip" maxlength="50">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" for="foto">Foto PNG</label>
                                    <input type="file" class="form-control" id="foto" name="foto" accept="image/png">
                                    <div class="form-text">PNG max 2 MB, crop otomatis 3:4.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="kebutuhan_khusus">Kebutuhan Khusus</label>
                                    <input type="text" class="form-control" id="kebutuhan_khusus" name="kebutuhan_khusus" maxlength="100">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="disabilitas">Disabilitas</label>
                                    <input type="text" class="form-control" id="disabilitas" name="disabilitas" maxlength="100">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" for="nama_ayah_kandung">Nama Ayah Kandung</label>
                                    <input type="text" class="form-control" id="nama_ayah_kandung" name="nama_ayah_kandung" maxlength="150">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" for="nama_ibu_kandung">Nama Ibu Kandung</label>
                                    <input type="text" class="form-control" id="nama_ibu_kandung" name="nama_ibu_kandung" maxlength="150">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" for="nama_wali">Nama Wali</label>
                                    <input type="text" class="form-control" id="nama_wali" name="nama_wali" maxlength="150">
                                </div>

                                <div class="col-12">
                                    <label class="form-label" for="alamat">Alamat</label>
                                    <textarea class="form-control" id="alamat" name="alamat" rows="3"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary" id="btnSimpanSiswa">
                                <span class="spinner-border spinner-border-sm d-none me-1" aria-hidden="true"></span>
                                Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($canManage)): ?>
        <div class="modal fade" id="modalKelasSiswa" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="formKelasSiswa">
                        <?= csrf_field() ?>
                        <input type="hidden" name="aksi" value="kelas">
                        <input type="hidden" id="kelasSiswaId">

                        <div class="modal-header">
                            <h5 class="modal-title">Atur Kelas Siswa</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>

                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Siswa</label>
                                <input type="text" class="form-control" id="kelasNamaSiswa" readonly>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Kelas Saat Ini</label>
                                <input type="text" class="form-control" id="kelasSaatIni" readonly>
                            </div>

                            <div>
                                <label class="form-label" for="idKelasTujuan">
                                    Kelas Tujuan <span class="text-danger">*</span>
                                </label>
                                <select
                                    class="form-select"
                                    id="idKelasTujuan"
                                    name="id_kelas_tujuan"
                                    required
                                >
                                    <option value="">Pilih kelas</option>
                                    <?php foreach ($kelasOptions as $kelas): ?>
                                        <option value="<?= (int) $kelas['id'] ?>">
                                            <?= esc($kelas['nama_kelas']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">
                                    Jika siswa belum memiliki kelas, sistem akan menempatkan siswa.
                                    Jika sudah memiliki kelas, perpindahan dilakukan secara transactional dan histori diperbarui.
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                Batal
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <span class="spinner-border spinner-border-sm d-none me-1" aria-hidden="true"></span>
                                Simpan Kelas
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modalMutasiSiswa" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="formMutasiSiswa">
                        <?= csrf_field() ?>

                        <div class="modal-header">
                            <h5 class="modal-title">Mutasi Keluar Siswa</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>

                        <div class="modal-body">
                            <input type="hidden" id="mutasiSiswaId">

                            <div class="mb-3">
                                <label class="form-label">Siswa</label>
                                <input type="text" class="form-control" id="mutasiNamaSiswa" readonly>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="mutasiStatus">Status</label>
                                <select class="form-select" id="mutasiStatus" name="status" required>
                                    <option value="">Pilih</option>
                                    <option value="Pindah">Pindah Sekolah</option>
                                    <option value="Keluar">Keluar</option>
                                </select>
                            </div>

                            <div>
                                <label class="form-label" for="mutasiKeterangan">Keterangan</label>
                                <textarea class="form-control" id="mutasiKeterangan" name="keterangan" rows="3" required></textarea>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-warning">Proses Mutasi</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($canImportExport)): ?>
        <div class="modal fade" id="modalImportSiswa" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="formImportSiswa">
                        <?= csrf_field() ?>

                        <div class="modal-header">
                            <h5 class="modal-title">Import Siswa</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>

                        <div class="modal-body">
                            <div class="alert alert-info">
                                Import bersifat <strong>atomic</strong>. Satu baris error membatalkan seluruh import.
                            </div>

                            <label class="form-label" for="fileImportSiswa">File Excel</label>
                            <input
                                type="file"
                                class="form-control"
                                id="fileImportSiswa"
                                name="file"
                                accept=".xlsx,.xls"
                                required
                            >
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">
                                <span class="spinner-border spinner-border-sm d-none me-1" aria-hidden="true"></span>
                                Import
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
