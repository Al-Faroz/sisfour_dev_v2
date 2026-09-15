<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<div
    id="kartuApp"
    data-base-url="<?= esc(base_url()) ?>"
    data-max-print="<?= (int) ($initial['max_print'] ?? 200) ?>"
    data-can-manage="<?= !empty($initial['can_manage']) ? '1' : '0' ?>"
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
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Generate Kartu</h5>
                </div>

                <div class="card-body">
                    <div class="row g-3 align-items-stretch">
                        <div class="col-lg-7">
                            <form id="formGenerateKartu" class="row g-3 align-items-end h-100">
                                <div class="col-md-8">
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

                                <div class="col-md-4 d-grid">
                                    <button class="btn btn-primary" type="submit">
                                        Generate Satu
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="col-lg-5">
                            <div class="border rounded p-3 h-100">
                                <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                                    <span>Belum memiliki kartu</span>
                                    <strong id="eligibleCount"><?= (int) ($initial['eligible_total'] ?? 0) ?></strong>
                                </div>

                                <button
                                    id="btnGenerateSemua"
                                    class="btn btn-outline-primary w-100"
                                    type="button"
                                    <?= empty($initial['eligible_total']) ? 'disabled' : '' ?>
                                >
                                    Generate Semua Belum Terbit
                                </button>

                                <div class="form-text mt-2">
                                    Diproses otomatis per batch maksimal
                                    <?= (int) ($initial['max_generate_batch'] ?? 200) ?>
                                    siswa agar transaksi tetap aman.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($initial['can_manage'])): ?>
            <div class="card sisfour-filter-card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Filter &amp; Cetak Massal</h5>
                </div>

                <div class="card-body">
                    <div class="row g-3 align-items-end">
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

                    <hr>

                    <div class="d-flex flex-wrap gap-2">
                        <button id="btnCetakDepanSelected" class="btn btn-primary" type="button">
                            Cetak Depan Terpilih A4
                        </button>
                        <button id="btnCetakBelakangSelected" class="btn btn-outline-primary" type="button">
                            Cetak Belakang Terpilih A4
                        </button>
                        <button id="btnCetakDepanKelas" class="btn btn-success" type="button">
                            Cetak Depan Per Kelas
                        </button>
                        <button id="btnCetakBelakangKelas" class="btn btn-outline-success" type="button">
                            Cetak Belakang Per Kelas
                        </button>
                    </div>

                    <div class="form-text mt-2">
                        Layout A4: 2 kolom × 5 baris = 10 kartu per lembar.
                        Maksimum <?= (int) ($initial['max_print'] ?? 200) ?> kartu per file PDF.
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