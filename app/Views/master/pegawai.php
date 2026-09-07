<?= $this->extend('main') ?>

<?= $this->section('content') ?>

<div
    id="masterPegawaiApp"
    data-base-url="<?= esc(base_url()) ?>"
    data-can-manage="<?= !empty($canManage) ? '1' : '0' ?>"
>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Master Pegawai</h4>
            <p class="text-muted mb-0">
                Kelola data tenaga kependidikan dan akun Pegawai.
            </p>
        </div>

        <?php if (!empty($canManage)): ?>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= base_url('master/pegawai/recycle') ?>" class="btn btn-outline-secondary">
                    <i class="bx bx-trash me-1"></i> Recycle Bin
                </a>

                <a href="<?= base_url('master/pegawai/template') ?>" class="btn btn-outline-primary">
                    <i class="bx bx-download me-1"></i> Template
                </a>

                <button type="button" class="btn btn-outline-primary" id="btnImportPegawai">
                    <i class="bx bx-import me-1"></i> Import
                </button>

                <a href="#" class="btn btn-outline-success" id="btnExportPegawai">
                    <i class="bx bx-export me-1"></i> Export
                </a>

                <button type="button" class="btn btn-primary" id="btnTambahPegawai">
                    <i class="bx bx-plus me-1"></i> Tambah Pegawai
                </button>
            </div>
        <?php endif; ?>
    </div>

    <div class="alert alert-info">
        <i class="bx bx-info-circle me-1"></i>
        Akun Pegawai dibuat otomatis dengan username dan password awal = NIP.
        Role tidak diberikan otomatis; Admin menetapkannya melalui Manajemen User.
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form id="formFilterPegawai" class="row g-3">
                <div class="col-12 col-md-3">
                    <label class="form-label" for="filterNama">Nama</label>
                    <input
                        type="text"
                        class="form-control"
                        id="filterNama"
                        name="nama"
                        placeholder="Cari nama"
                    >
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label" for="filterNip">NIP</label>
                    <input
                        type="text"
                        class="form-control"
                        id="filterNip"
                        name="nip"
                        placeholder="Cari NIP"
                    >
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label" for="filterJk">Jenis Kelamin</label>
                    <select
                        class="form-select"
                        id="filterJk"
                        name="jenis_kelamin"
                    >
                        <option value="">Semua</option>
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label" for="filterJabatan">Jabatan</label>
                    <input
                        type="text"
                        class="form-control"
                        id="filterJabatan"
                        name="jabatan"
                        placeholder="Cari jabatan"
                    >
                </div>

                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-filter-alt me-1"></i> Terapkan
                    </button>

                    <button
                        type="button"
                        class="btn btn-outline-secondary"
                        id="btnResetFilter"
                    >
                        Reset
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Daftar Pegawai</h5>

            <?php if (empty($canManage)): ?>
                <span class="badge bg-label-info">Readonly</span>
            <?php endif; ?>
        </div>

        <div class="card-datatable table-responsive">
            <table class="table table-hover" id="tablePegawai">
                <thead>
                    <tr>
                        <th style="width: 56px;">No.</th>
                        <th>Pegawai</th>
                        <th>NIP</th>
                        <th>JK</th>
                        <th>Jabatan</th>
                        <th>Role Akun</th>
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
        <div
            class="modal fade"
            id="modalPegawai"
            tabindex="-1"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <form id="formPegawai">
                        <?= csrf_field() ?>

                        <div class="modal-header">
                            <h5 class="modal-title" id="modalPegawaiTitle">
                                Tambah Pegawai
                            </h5>

                            <button
                                type="button"
                                class="btn-close"
                                data-bs-dismiss="modal"
                                aria-label="Tutup"
                            ></button>
                        </div>

                        <div class="modal-body">
                            <input type="hidden" id="pegawaiId">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="nip">
                                        NIP <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="nip"
                                        name="nip"
                                        maxlength="30"
                                        required
                                    >
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="nama">
                                        Nama Lengkap <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="nama"
                                        name="nama"
                                        maxlength="150"
                                        required
                                    >
                                </div>

                                <div class="col-md-4">
                                    <label
                                        class="form-label"
                                        for="jenis_kelamin"
                                    >
                                        Jenis Kelamin
                                        <span class="text-danger">*</span>
                                    </label>

                                    <select
                                        class="form-select"
                                        id="jenis_kelamin"
                                        name="jenis_kelamin"
                                        required
                                    >
                                        <option value="">Pilih</option>
                                        <option value="L">Laki-laki</option>
                                        <option value="P">Perempuan</option>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label
                                        class="form-label"
                                        for="tempat_lahir"
                                    >
                                        Tempat Lahir
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="tempat_lahir"
                                        name="tempat_lahir"
                                        maxlength="100"
                                    >
                                </div>

                                <div class="col-md-4">
                                    <label
                                        class="form-label"
                                        for="tanggal_lahir"
                                    >
                                        Tanggal Lahir
                                    </label>

                                    <input
                                        type="date"
                                        class="form-control"
                                        id="tanggal_lahir"
                                        name="tanggal_lahir"
                                    >
                                </div>

                                <div class="col-md-6">
                                    <label
                                        class="form-label"
                                        for="jabatan"
                                    >
                                        Jabatan
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="jabatan"
                                        name="jabatan"
                                        maxlength="100"
                                    >
                                </div>

                                <div class="col-md-6">
                                    <label
                                        class="form-label"
                                        for="no_telepon"
                                    >
                                        No. Telepon
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="no_telepon"
                                        name="no_telepon"
                                        maxlength="20"
                                    >
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="email">
                                        Email
                                    </label>

                                    <input
                                        type="email"
                                        class="form-control"
                                        id="email"
                                        name="email"
                                        maxlength="100"
                                    >
                                </div>

                                <div class="col-12">
                                    <label class="form-label" for="alamat">
                                        Alamat
                                    </label>

                                    <textarea
                                        class="form-control"
                                        id="alamat"
                                        name="alamat"
                                        rows="3"
                                    ></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button
                                type="button"
                                class="btn btn-outline-secondary"
                                data-bs-dismiss="modal"
                            >
                                Batal
                            </button>

                            <button
                                type="submit"
                                class="btn btn-primary"
                                id="btnSimpanPegawai"
                            >
                                <span
                                    class="spinner-border spinner-border-sm d-none me-1"
                                    aria-hidden="true"
                                ></span>
                                Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div
            class="modal fade"
            id="modalImportPegawai"
            tabindex="-1"
            aria-hidden="true"
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="formImportPegawai">
                        <?= csrf_field() ?>

                        <div class="modal-header">
                            <h5 class="modal-title">Import Pegawai</h5>

                            <button
                                type="button"
                                class="btn-close"
                                data-bs-dismiss="modal"
                                aria-label="Tutup"
                            ></button>
                        </div>

                        <div class="modal-body">
                            <div class="alert alert-info">
                                Import bersifat <strong>atomic</strong>.
                                Satu baris error membatalkan seluruh import.
                            </div>

                            <label
                                class="form-label"
                                for="fileImportPegawai"
                            >
                                File Excel
                            </label>

                            <input
                                type="file"
                                class="form-control"
                                id="fileImportPegawai"
                                name="file"
                                accept=".xlsx,.xls"
                                required
                            >
                        </div>

                        <div class="modal-footer">
                            <button
                                type="button"
                                class="btn btn-outline-secondary"
                                data-bs-dismiss="modal"
                            >
                                Batal
                            </button>

                            <button type="submit" class="btn btn-primary">
                                <span
                                    class="spinner-border spinner-border-sm d-none me-1"
                                    aria-hidden="true"
                                ></span>
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
