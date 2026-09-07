<?= $this->extend('main') ?>

<?= $this->section('content') ?>

<div
    id="masterTahunAjaranApp"
    data-base-url="<?= esc(base_url()) ?>"
>
    <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Master Tahun Ajaran</h4>
            <p class="text-muted mb-0">
                Kelola tahun ajaran dan semester aktif yang digunakan sistem.
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a
                href="<?= base_url('master/tahun/recycle') ?>"
                class="btn btn-outline-secondary"
            >
                <i class="bx bx-trash me-1"></i>
                Recycle Bin
            </a>

            <button
                type="button"
                class="btn btn-primary"
                id="btnTambahTahun"
            >
                <i class="bx bx-plus me-1"></i>
                Tambah Tahun Ajaran
            </button>
        </div>
    </div>

    <div class="alert alert-info">
        <i class="bx bx-info-circle me-1"></i>
        Hanya <strong>satu</strong> tahun ajaran/semester yang boleh aktif.
        Mengaktifkan satu data akan otomatis menonaktifkan data yang aktif sebelumnya.
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Daftar Tahun Ajaran</h5>
        </div>

        <div class="card-datatable table-responsive">
            <table
                class="table table-hover align-middle"
                id="tableTahunAjaran"
            >
                <thead>
                    <tr>
                        <th style="width: 56px;">No.</th>
                        <th>Tahun Ajaran</th>
                        <th>Semester</th>
                        <th>Status</th>
                        <th>Kelas</th>
                        <th>Anggota</th>
                        <th>Jadwal</th>
                        <th style="min-width: 180px;">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div
        class="modal fade"
        id="modalTahunAjaran"
        tabindex="-1"
        aria-hidden="true"
    >
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="formTahunAjaran">
                    <?= csrf_field() ?>

                    <div class="modal-header">
                        <h5
                            class="modal-title"
                            id="modalTahunAjaranTitle"
                        >
                            Tambah Tahun Ajaran
                        </h5>

                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Tutup"
                        ></button>
                    </div>

                    <div class="modal-body">
                        <input
                            type="hidden"
                            id="tahunAjaranId"
                        >

                        <div class="mb-3">
                            <label
                                class="form-label"
                                for="nama_tahun"
                            >
                                Tahun Ajaran
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                id="nama_tahun"
                                name="nama_tahun"
                                maxlength="20"
                                placeholder="2026/2027"
                                required
                            >

                            <div class="form-text">
                                Format YYYY/YYYY, contoh 2026/2027.
                            </div>
                        </div>

                        <div>
                            <label
                                class="form-label"
                                for="semester"
                            >
                                Semester
                            </label>

                            <select
                                class="form-select"
                                id="semester"
                                name="semester"
                                required
                            >
                                <option value="">Pilih</option>
                                <option value="Ganjil">Ganjil</option>
                                <option value="Genap">Genap</option>
                            </select>
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
                            id="btnSimpanTahun"
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
</div>

<?= $this->endSection() ?>
