<?= $this->extend('main') ?>

<?= $this->section('content') ?>

<div
    id="masterMapelApp"
    data-base-url="<?= esc(base_url()) ?>"
>
    <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Master Mata Pelajaran</h4>
            <p class="text-muted mb-0">
                Kelola nama dan kode mata pelajaran yang digunakan pada jadwal guru.
            </p>
        </div>

        <button
            type="button"
            class="btn btn-primary"
            id="btnTambahMapel"
        >
            <i class="bx bx-plus me-1"></i>
            Tambah Mata Pelajaran
        </button>
    </div>

    <div class="alert alert-info">
        <i class="bx bx-info-circle me-1"></i>
        Kode mata pelajaran harus unik. Mata pelajaran yang sudah digunakan pada
        jadwal guru tidak dapat dihapus.
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form id="formFilterMapel" class="row g-3">
                <div class="col-12 col-md-5">
                    <label
                        class="form-label"
                        for="filterNamaMapel"
                    >
                        Nama Mata Pelajaran
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="filterNamaMapel"
                        name="nama_mapel"
                        placeholder="Cari nama mata pelajaran"
                    >
                </div>

                <div class="col-12 col-md-4">
                    <label
                        class="form-label"
                        for="filterKodeMapel"
                    >
                        Kode Mata Pelajaran
                    </label>

                    <input
                        type="text"
                        class="form-control text-uppercase"
                        id="filterKodeMapel"
                        name="kode_mapel"
                        maxlength="10"
                        placeholder="Contoh: MTK"
                    >
                </div>

                <div class="col-12 col-md-3 d-flex align-items-end gap-2">
                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        <i class="bx bx-filter-alt me-1"></i>
                        Terapkan
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
        <div class="card-header">
            <h5 class="mb-0">Daftar Mata Pelajaran</h5>
        </div>

        <div class="card-datatable table-responsive">
            <table
                class="table table-hover align-middle"
                id="tableMapel"
            >
                <thead>
                    <tr>
                        <th style="width: 56px;">No.</th>
                        <th>Nama Mata Pelajaran</th>
                        <th>Kode</th>
                        <th>Digunakan Jadwal</th>
                        <th style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div
        class="modal fade"
        id="modalMapel"
        tabindex="-1"
        aria-hidden="true"
    >
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="formMapel">
                    <?= csrf_field() ?>

                    <div class="modal-header">
                        <h5
                            class="modal-title"
                            id="modalMapelTitle"
                        >
                            Tambah Mata Pelajaran
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
                            id="mapelId"
                        >

                        <div class="mb-3">
                            <label
                                class="form-label"
                                for="nama_mapel"
                            >
                                Nama Mata Pelajaran
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                id="nama_mapel"
                                name="nama_mapel"
                                maxlength="100"
                                required
                            >
                        </div>

                        <div>
                            <label
                                class="form-label"
                                for="kode_mapel"
                            >
                                Kode Mata Pelajaran
                            </label>

                            <input
                                type="text"
                                class="form-control text-uppercase"
                                id="kode_mapel"
                                name="kode_mapel"
                                maxlength="10"
                                placeholder="Contoh: MTK"
                                required
                            >

                            <div class="form-text">
                                Maksimal 10 karakter. Huruf, angka, underscore, atau tanda minus.
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
                            id="btnSimpanMapel"
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
