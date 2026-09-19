<?= $this->extend('main') ?>

<?= $this->section('content') ?>

<div
    id="mappingWaliApp"
    data-base-url="<?= esc(base_url()) ?>"
    data-can-manage="<?= !empty($canManage) ? '1' : '0' ?>"
>
    <div class="sisfour-page-header">
        <div class="sisfour-page-header__copy">
            <h4 class="fw-bold mb-1">Mapping Wali Kelas</h4>
            <p class="text-muted mb-0">
                Kelola penetapan wali kelas per tahun ajaran.
            </p>
        </div>

        <?php if (!empty($canManage)): ?>
            <div class="sisfour-page-actions">
                <a
                    href="<?= base_url('master/wali-kelas/recycle') ?>"
                    class="btn btn-outline-secondary sisfour-touch-target--compact"
                >
                    <i class="bx bx-history me-1"></i>
                    Histori / Recycle Bin
                </a>

                <button
                    type="button"
                    class="btn btn-outline-success sisfour-touch-target--compact"
                    id="btnExportWali"
                >
                    <i class="bx bx-export me-1"></i>
                    Export
                </button>

                <button
                    type="button"
                    class="btn btn-primary sisfour-touch-target"
                    id="btnAssignWali"
                >
                    <i class="bx bx-plus me-1"></i>
                    Assign Wali
                </button>
            </div>
        <?php endif; ?>
    </div>

    <div class="alert alert-info sisfour-compact-note">
        <i class="bx bx-info-circle me-1"></i>
        Wali Kelas <strong>bukan role</strong>. Status Wali dibaca dinamis dari
        mapping aktif. Satu guru maksimal satu kelas aktif per tahun ajaran,
        dan satu kelas maksimal satu wali aktif per tahun ajaran.
    </div>

    <div class="card sisfour-filter-card mb-4">
        <div class="card-body">
            <form id="formFilterWali" class="row g-3 align-items-end">
                <div class="col-12 col-md-4">
                    <label
                        class="form-label"
                        for="filterTahun"
                    >
                        Tahun Ajaran
                    </label>

                    <select
                        class="form-select"
                        id="filterTahun"
                        name="id_tahun"
                    >
                        <option value="">Semua</option>

                        <?php foreach ($tahunOptions as $tahun): ?>
                            <option value="<?= (int) $tahun['id'] ?>">
                                <?= esc(
                                    $tahun['nama_tahun']
                                    . ' - '
                                    . $tahun['semester']
                                    . ((int) $tahun['status_aktif'] === 1
                                        ? ' (Aktif)'
                                        : '')
                                ) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-md-4">
                    <label
                        class="form-label"
                        for="filterGuru"
                    >
                        Guru / NIP
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="filterGuru"
                        name="guru"
                        placeholder="Cari nama atau NIP"
                    >
                </div>

                <div class="col-12 col-md-4">
                    <label
                        class="form-label"
                        for="filterKelas"
                    >
                        Kelas
                    </label>

                    <select
                        class="form-select"
                        id="filterKelas"
                        name="id_kelas"
                    >
                        <option value="">Semua</option>

                        <?php foreach ($kelasFilterOptions as $kelas): ?>
                            <option value="<?= (int) $kelas['id'] ?>">
                                <?= esc(
                                    $kelas['nama_tahun']
                                    . ' - '
                                    . $kelas['semester']
                                    . ' · '
                                    . $kelas['nama_kelas']
                                ) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 sisfour-filter-actions">
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
        <div class="card-header d-flex justify-content-between align-items-start flex-wrap gap-2">
            <h5 class="mb-0">Daftar Wali Kelas Aktif</h5>

            <?php if (empty($canManage)): ?>
                <span class="badge bg-label-info">
                    Readonly
                </span>
            <?php endif; ?>
        </div>

        <div id="mappingWaliMobileList" class="d-md-none list-group list-group-flush"><div class="list-group-item sisfour-mobile-state text-muted">Memuat mapping wali...</div></div>
        <div class="d-none d-md-block table-responsive">
            <table
                class="table table-hover align-middle mb-0"
                id="tableMappingWali"
            >
                <thead>
                    <tr>
                        <th style="width: 56px;">No.</th>
                        <th>Guru</th>
                        <th>Kelas</th>
                        <th>Tahun Ajaran</th>
                        <th>Status</th>
                        <?php if (!empty($canManage)): ?>
                            <th style="width: 140px;">Aksi</th>
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
            id="modalAssignWali"
            tabindex="-1"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-sm-down">
                <form id="formAssignWali" class="modal-content">
                    <?= csrf_field() ?>

                    <div class="modal-header py-2">
                        <h5 class="modal-title">
                            Assign Wali Kelas
                        </h5>

                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Tutup"
                        ></button>
                    </div>

                    <div class="modal-body overflow-auto py-3">
                        <div class="alert alert-warning sisfour-compact-note">
                            Jika guru pernah menjadi wali pada tahun yang sama
                            dan mapping lama sudah nonaktif, sistem akan
                            <strong>restore</strong> row lama, bukan membuat
                            row baru.
                        </div>

                        <div class="mb-3">
                            <label
                                class="form-label"
                                for="assignTahun"
                            >
                                Tahun Ajaran
                            </label>

                            <select
                                class="form-select"
                                id="assignTahun"
                                name="id_tahun"
                                required
                            >
                                <option value="">
                                    Pilih tahun ajaran
                                </option>

                                <?php foreach ($tahunOptions as $tahun): ?>
                                    <option
                                        value="<?= (int) $tahun['id'] ?>"
                                    >
                                        <?= esc(
                                            $tahun['nama_tahun']
                                            . ' - '
                                            . $tahun['semester']
                                            . ((int) $tahun['status_aktif'] === 1
                                                ? ' (Aktif)'
                                                : '')
                                        ) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label
                                class="form-label"
                                for="assignGuru"
                            >
                                Guru
                            </label>

                            <select
                                class="form-select"
                                id="assignGuru"
                                name="id_guru"
                                required
                                disabled
                            >
                                <option value="">
                                    Pilih tahun ajaran terlebih dahulu
                                </option>
                            </select>
                        </div>

                        <div>
                            <label
                                class="form-label"
                                for="assignKelas"
                            >
                                Kelas
                            </label>

                            <select
                                class="form-select"
                                id="assignKelas"
                                name="id_kelas"
                                required
                                disabled
                            >
                                <option value="">
                                    Pilih tahun ajaran terlebih dahulu
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="modal-footer py-2 sisfour-modal-actions">
                        <button
                            type="button"
                            class="btn btn-outline-secondary sisfour-touch-target--compact"
                            data-bs-dismiss="modal"
                        >
                            Batal
                        </button>

                        <button
                            type="submit"
                            class="btn btn-primary"
                            id="btnSimpanMapping"
                        >
                            <span
                                class="spinner-border spinner-border-sm d-none me-1"
                                aria-hidden="true"
                            ></span>
                            Simpan Mapping
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
