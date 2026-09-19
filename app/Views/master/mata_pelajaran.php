<?= $this->extend('main') ?>

<?= $this->section('content') ?>

<div
    id="masterMapelApp"
    data-base-url="<?= esc(base_url()) ?>"
>
    <div class="sisfour-page-header">
        <div class="sisfour-page-header__copy">
            <h4 class="fw-bold mb-1">Master Mata Pelajaran</h4>
            <p class="text-muted mb-0">
                Kelola nama dan kode mata pelajaran yang digunakan pada jadwal guru.
            </p>
        </div>

        <div class="sisfour-page-actions">
            <button
                type="button"
                class="btn btn-outline-success sisfour-touch-target--compact"
                id="btnExportMapel"
            >
                <i class="bx bx-export me-1"></i>
                Export
            </button>

            <button
                type="button"
                class="btn btn-primary sisfour-touch-target"
                id="btnTambahMapel"
            >
                <i class="bx bx-plus me-1"></i>
                Tambah Mata Pelajaran
            </button>
        </div>
    </div>

    <div class="alert alert-info sisfour-compact-note">
        <i class="bx bx-info-circle me-1"></i>
        Kode mata pelajaran harus unik. Mata pelajaran yang sudah digunakan pada
        jadwal guru tidak dapat dihapus.
    </div>

    <div class="card sisfour-filter-card mb-4">
        <div class="card-body">
            <form id="formFilterMapel" class="row g-3 align-items-end">
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

                <div class="col-12 col-md-3 sisfour-filter-actions">
                    <button
                        type="button"
                        class="btn btn-outline-secondary sisfour-touch-target--compact"
                        id="btnResetFilter"
                    >
                        Reset
                    </button>

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        <i class="bx bx-filter-alt me-1"></i>
                        Terapkan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card sisfour-table-card">
        <div class="card-header">
            <h5 class="mb-0">Daftar Mata Pelajaran</h5>
        </div>

        <div id="mapelMobileList" class="d-md-none list-group list-group-flush"><div class="list-group-item sisfour-mobile-state text-muted">Memuat mata pelajaran...</div></div>
        <div class="d-none d-md-block table-responsive">
            <table
                class="table table-hover align-middle mb-0"
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
        <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-sm-down">
            <form id="formMapel" class="modal-content">
                <?= csrf_field() ?>

                <div class="modal-header py-2">
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

                <div class="modal-body overflow-auto py-3">
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

                <div class="modal-footer py-2 sisfour-modal-actions">
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

<?= $this->endSection() ?>