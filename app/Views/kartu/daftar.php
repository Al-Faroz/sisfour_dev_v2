<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<div
    id="kartuApp"
    data-base-url="<?= esc(base_url()) ?>"
    data-max-print="<?= (int) ($initial['max_print'] ?? 200) ?>"
    data-can-manage="<?= !empty($initial['can_manage']) ? '1' : '0' ?>"
    class="kartu-page"
>
    <div class="sisfour-page-header">
        <div class="sisfour-page-header__copy">
            <h4 class="fw-bold mb-1">Kartu Pelajar</h4>
            <p class="text-muted mb-0">
                Generate identitas kartu dan cetak fisik adalah dua proses terpisah.
            </p>
        </div>
    </div>

    <?php if (empty($initial['success'])): ?>
        <div class="alert alert-danger">
            <?= esc($initial['message'] ?? 'Data tidak dapat dibuka.') ?>
        </div>
    <?php else: ?>

        <?php if (!empty($initial['can_manage'])): ?>
            <div class="card mb-4 kartu-panel">
                <div class="card-header kartu-panel-header">
                    <div class="kartu-panel-title">
                        <span class="kartu-panel-icon" aria-hidden="true">
                            <i class="bx bx-id-card"></i>
                        </span>
                        <div>
                            <h5 class="mb-1">Generate Kartu</h5>
                            <p class="mb-0 text-muted">
                                Terbitkan kartu siswa satu per satu atau proses seluruh kartu yang belum terbit.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="card-body pt-2">
                    <div class="kartu-generate-grid">
                        <div class="kartu-generate-box">
                            <div class="kartu-box-kicker">Generate individu</div>

                            <form id="formGenerateKartu" class="row g-3 align-items-end">
                                <div class="col-12 col-md-8">
                                    <label class="form-label" for="generateKartuSiswa">
                                        Siswa aktif yang belum memiliki kartu
                                    </label>

                                    <select
                                        id="generateKartuSiswa"
                                        name="id_siswa"
                                        class="form-select"
                                        required
                                    >
                                        <option value="">Pilih siswa</option>

                                        <?php foreach (($initial['eligible_students'] ?? []) as $siswa): ?>
                                            <option value="<?= (int) $siswa['id'] ?>">
                                                <?= esc(
                                                    ($siswa['nama_kelas'] ?? '-')
                                                    . ' — '
                                                    . $siswa['nisn']
                                                    . ' — '
                                                    . $siswa['nama']
                                                ) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12 col-md-4 d-grid">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="bx bx-plus-circle me-1"></i>
                                        Generate Satu
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="kartu-generate-box kartu-generate-box--summary">
                            <div class="kartu-count-row">
                                <div>
                                    <div class="kartu-box-kicker">Antrian penerbitan</div>
                                    <div class="text-muted small">Siswa aktif yang belum memiliki kartu</div>
                                </div>
                                <strong class="kartu-count-badge" id="eligibleCount">
                                    <?= (int) ($initial['eligible_total'] ?? 0) ?>
                                </strong>
                            </div>

                            <button
                                id="btnGenerateSemua"
                                class="btn btn-outline-primary w-100 mt-3"
                                type="button"
                                <?= empty($initial['eligible_total']) ? 'disabled' : '' ?>
                            >
                                <i class="bx bx-layer-plus me-1"></i>
                                Generate Semua Belum Terbit
                            </button>

                            <div class="form-text mt-2">
                                Diproses bertahap maksimal
                                <?= (int) ($initial['max_generate_batch'] ?? 200) ?>
                                siswa per request agar transaksi tetap aman.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($initial['can_manage'])): ?>
            <div class="card sisfour-filter-card mb-4 kartu-panel">
                <div class="card-header kartu-panel-header">
                    <div class="kartu-panel-title">
                        <span class="kartu-panel-icon kartu-panel-icon--print" aria-hidden="true">
                            <i class="bx bx-printer"></i>
                        </span>
                        <div>
                            <h5 class="mb-1">Filter &amp; Cetak Massal</h5>
                            <p class="mb-0 text-muted">
                                Pilih data yang ingin ditampilkan, lalu gunakan jenis output sesuai kebutuhan.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="card-body pt-2">
                    <div class="row g-3 align-items-end kartu-filter-row">
                        <div class="col-12 col-lg-4">
                            <label class="form-label" for="kartuSearch">Cari</label>
                            <input
                                id="kartuSearch"
                                type="search"
                                class="form-control"
                                placeholder="Nama / NISN / nomor kartu"
                            >
                        </div>

                        <div class="col-12 col-md-5 col-lg-3">
                            <label class="form-label" for="kartuKelas">Kelas</label>
                            <select id="kartuKelas" class="form-select">
                                <option value="">Semua kelas</option>
                                <?php foreach (($initial['class_options'] ?? []) as $kelas): ?>
                                    <option value="<?= (int) $kelas['id'] ?>"><?= esc($kelas['nama_kelas']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-6 col-md-3 col-lg-2">
                            <label class="form-label" for="kartuStatus">Status</label>
                            <select id="kartuStatus" class="form-select" data-searchable-off="1">
                                <option value="">Semua</option>
                                <option value="Aktif">Aktif</option>
                                <option value="Nonaktif">Nonaktif</option>
                            </select>
                        </div>

                        <div class="col-6 col-md-4 col-lg-3 d-grid">
                            <button id="btnKartuCari" class="btn btn-primary" type="button">
                                <i class="bx bx-filter-alt me-1"></i> Tampilkan
                            </button>
                        </div>
                    </div>

                    <div class="kartu-action-divider"></div>

                    <div class="kartu-action-grid">
                        <section class="kartu-action-card">
                            <div class="kartu-action-card__head">
                                <span class="kartu-action-icon">
                                    <i class="bx bx-check-square"></i>
                                </span>
                                <div>
                                    <h6 class="mb-1">Kartu Terpilih</h6>
                                    <p class="mb-0">Gunakan centang pada tabel untuk memilih kartu tertentu.</p>
                                </div>
                            </div>

                            <div class="kartu-action-buttons kartu-action-buttons--pair">
                                <button id="btnCetakDepanSelected" class="btn btn-primary" type="button">
                                    <i class="bx bx-id-card me-1"></i>
                                    Depan A4
                                </button>
                                <button id="btnCetakBelakangSelected" class="btn btn-outline-primary" type="button">
                                    <i class="bx bx-id-card me-1"></i>
                                    Belakang A4
                                </button>
                            </div>
                        </section>

                        <section class="kartu-action-card kartu-action-card--class">
                            <div class="kartu-action-card__head">
                                <span class="kartu-action-icon">
                                    <i class="bx bx-group"></i>
                                </span>
                                <div>
                                    <h6 class="mb-1">PDF Per Kelas</h6>
                                    <p class="mb-0">Cetak seluruh kartu aktif pada kelas yang dipilih.</p>
                                </div>
                            </div>

                            <div class="kartu-action-buttons kartu-action-buttons--pair">
                                <button id="btnCetakDepanKelas" class="btn btn-success" type="button">
                                    <i class="bx bx-file me-1"></i>
                                    Depan PDF
                                </button>
                                <button id="btnCetakBelakangKelas" class="btn btn-outline-success" type="button">
                                    <i class="bx bx-file me-1"></i>
                                    Belakang PDF
                                </button>
                            </div>
                        </section>

                        <?php if (!empty($initial['can_export_jpg_zip'])): ?>
                            <section class="kartu-action-card kartu-action-card--digital">
                                <div class="kartu-action-card__head">
                                    <span class="kartu-action-icon">
                                        <i class="bx bx-image"></i>
                                    </span>
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center justify-content-between gap-2">
                                            <h6 class="mb-1">JPG Per Kelas</h6>
                                            <span class="badge bg-label-dark">Admin</span>
                                        </div>
                                        <p class="mb-0">Satu JPG sisi depan per siswa, otomatis dikemas dalam ZIP.</p>
                                    </div>
                                </div>

                                <div class="kartu-action-buttons">
                                    <button id="btnExportJpgKelas" class="btn btn-dark" type="button">
                                        <i class="bx bx-download me-1"></i>
                                        Unduh JPG Depan (.ZIP)
                                    </button>
                                </div>
                            </section>
                        <?php endif; ?>
                    </div>

                    <div class="kartu-print-note">
                        <i class="bx bx-info-circle" aria-hidden="true"></i>
                        <span>
                            PDF A4 memakai layout 2 kolom × 5 baris.
                            Maksimum <?= (int) ($initial['max_print'] ?? 200) ?> kartu per proses.
                            JPG ZIP mengikuti data <strong>Cetak Depan Per Kelas</strong>.
                        </span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div id="kartuAlert" class="alert d-none" role="alert"></div>

        <div class="card sisfour-table-card">
            <div class="card-header">
                <h5 class="mb-0">Daftar Kartu Pelajar</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tableKartuPelajar">
                    <thead>
                        <tr>
                            <?php if (!empty($initial['can_manage'])): ?>
                                <th style="width:40px">
                                    <input
                                        id="checkAllKartu"
                                        class="form-check-input"
                                        type="checkbox"
                                        aria-label="Pilih semua kartu pada halaman ini"
                                    >
                                </th>
                            <?php endif; ?>
                            <th>Siswa</th>
                            <th>Kelas</th>
                            <th>Nomor Kartu</th>
                            <th>Terbit</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="kartuBody"></tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>