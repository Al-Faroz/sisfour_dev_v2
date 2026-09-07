<?= $this->extend('main') ?>

<?= $this->section('content') ?>

<div
    id="masterGuruApp"
    data-base-url="<?= esc(base_url()) ?>"
    data-can-manage="<?= !empty($canManage) ? '1' : '0' ?>"
>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Master Guru</h4>
            <p class="text-muted mb-0">
                Kelola data Guru, akun otomatis, import/export, dan foto profil Guru.
            </p>
        </div>

        <?php if (!empty($canManage)): ?>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= base_url('master/guru/recycle') ?>" class="btn btn-outline-secondary">
                    <i class="bx bx-trash me-1"></i> Recycle Bin
                </a>
                <a href="<?= base_url('master/guru/template') ?>" class="btn btn-outline-primary">
                    <i class="bx bx-download me-1"></i> Template
                </a>
                <button type="button" class="btn btn-outline-primary" id="btnImportGuru">
                    <i class="bx bx-import me-1"></i> Import
                </button>
                <a href="#" class="btn btn-outline-success" id="btnExportGuru">
                    <i class="bx bx-export me-1"></i> Export
                </a>
                <button type="button" class="btn btn-primary" id="btnTambahGuru">
                    <i class="bx bx-plus me-1"></i> Tambah Guru
                </button>
            </div>
        <?php endif; ?>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form id="formFilterGuru" class="row g-3">
                <div class="col-12 col-md-3">
                    <label class="form-label" for="filterNama">Nama</label>
                    <input type="text" class="form-control" id="filterNama" name="nama" placeholder="Cari nama">
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label" for="filterNip">NIP</label>
                    <input type="text" class="form-control" id="filterNip" name="nip" placeholder="Cari NIP">
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label" for="filterJk">Jenis Kelamin</label>
                    <select class="form-select" id="filterJk" name="jenis_kelamin">
                        <option value="">Semua</option>
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label" for="filterStatus">Status Kepegawaian</label>
                    <select class="form-select" id="filterStatus" name="status_kepegawaian">
                        <option value="">Semua</option>
                        <option value="PNS">PNS</option>
                        <option value="PPPK">PPPK</option>
                        <option value="NON ASN">NON ASN</option>
                        <option value="Yayasan">Yayasan</option>
                        <option value="Outsourcing">Outsourcing</option>
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
            <h5 class="mb-0">Daftar Guru</h5>
            <?php if (empty($canManage)): ?>
                <span class="badge bg-label-info">Readonly</span>
            <?php endif; ?>
        </div>

        <div class="card-datatable table-responsive">
            <table class="table table-hover" id="tableGuru">
                <thead>
                    <tr>
                        <th style="width: 56px;">No.</th>
                        <th>Guru</th>
                        <th>NIP</th>
                        <th>JK</th>
                        <th>Status</th>
                        <th>Kontak</th>
                        <?php if (!empty($canManage)): ?>
                            <th style="width: 130px;">Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <?php if (!empty($canManage)): ?>
        <div class="modal fade" id="modalGuru" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <form id="formGuru">
                        <?= csrf_field() ?>

                        <div class="modal-header">
                            <h5 class="modal-title" id="modalGuruTitle">Tambah Guru</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>

                        <div class="modal-body">
                            <input type="hidden" id="guruId">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="nip">NIP <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nip" name="nip" maxlength="30" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="nama">Nama Lengkap & Gelar <span class="text-danger">*</span></label>
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

                                <div class="col-md-6">
                                    <label class="form-label" for="no_telepon">No. Telepon</label>
                                    <input type="text" class="form-control" id="no_telepon" name="no_telepon" maxlength="20">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="email">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" maxlength="100">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="status_kepegawaian">Status Kepegawaian</label>
                                    <select class="form-select" id="status_kepegawaian" name="status_kepegawaian">
                                        <option value="">Pilih</option>
                                        <option value="PNS">PNS</option>
                                        <option value="PPPK">PPPK</option>
                                        <option value="NON ASN">NON ASN</option>
                                        <option value="Yayasan">Yayasan</option>
                                        <option value="Outsourcing">Outsourcing</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="foto">Foto PNG</label>
                                    <input type="file" class="form-control" id="foto" name="foto" accept="image/png">
                                    <div class="form-text">
                                        PNG maksimal 2 MB. Sistem otomatis crop rasio 3:4 dan re-encode.
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label" for="alamat">Alamat</label>
                                    <textarea class="form-control" id="alamat" name="alamat" rows="3"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary" id="btnSimpanGuru">
                                <span class="spinner-border spinner-border-sm d-none me-1" aria-hidden="true"></span>
                                Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modalImportGuru" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="formImportGuru">
                        <?= csrf_field() ?>

                        <div class="modal-header">
                            <h5 class="modal-title">Import Guru</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>

                        <div class="modal-body">
                            <div class="alert alert-info">
                                Import bersifat <strong>atomic</strong>. Jika satu baris bermasalah,
                                seluruh import dibatalkan.
                            </div>

                            <label class="form-label" for="fileImportGuru">File Excel</label>
                            <input
                                type="file"
                                class="form-control"
                                id="fileImportGuru"
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
