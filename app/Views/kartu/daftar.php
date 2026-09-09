<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<div
    id="kartuApp"
    data-base-url="<?= esc(base_url()) ?>"
    data-max-print="<?= (int) ($initial['max_print'] ?? 200) ?>"
    data-can-manage="<?= !empty($initial['can_manage']) ? '1' : '0' ?>"
>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
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
                    <div class="row g-3">
                        <div class="col-lg-7">
                            <form
                                id="formGenerateKartu"
                                class="row g-2 align-items-end"
                            >
                                <div class="col-md-8">
                                    <label class="form-label">
                                        Siswa aktif yang belum memiliki kartu
                                    </label>

                                    <select
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
                                    <button
                                        class="btn btn-primary"
                                        type="submit"
                                    >
                                        Generate Satu
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="col-lg-5">
                            <div class="border rounded p-3 h-100">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Belum memiliki kartu</span>
                                    <strong id="eligibleCount">
                                        <?= (int) ($initial['eligible_total'] ?? 0) ?>
                                    </strong>
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
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Filter & Cetak Massal</h5>
            </div>

            <div class="card-body">
                <div class="row g-3">
                    <div class="col-lg-4">
                        <label class="form-label">Cari</label>
                        <input
                            id="kartuSearch"
                            class="form-control"
                            placeholder="Nama / NISN / nomor kartu"
                        >
                    </div>

                    <div class="col-lg-3">
                        <label class="form-label">Kelas</label>
                        <select
                            id="kartuKelas"
                            class="form-select"
                        >
                            <option value="">Semua kelas</option>

                            <?php foreach (($initial['class_options'] ?? []) as $kelas): ?>
                                <option value="<?= (int) $kelas['id'] ?>">
                                    <?= esc($kelas['nama_kelas']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-lg-2">
                        <label class="form-label">Status</label>
                        <select
                            id="kartuStatus"
                            class="form-select"
                        >
                            <option value="">Semua</option>
                            <option value="Aktif">Aktif</option>
                            <option value="Nonaktif">Nonaktif</option>
                        </select>
                    </div>

                    <div class="col-lg-3 d-grid align-self-end">
                        <button
                            id="btnKartuCari"
                            class="btn btn-outline-primary"
                            type="button"
                        >
                            Tampilkan
                        </button>
                    </div>
                </div>

                <hr>

                <div class="d-flex flex-wrap gap-2">
                    <button
                        id="btnCetakDepanSelected"
                        class="btn btn-primary"
                        type="button"
                    >
                        Cetak Depan Terpilih A4
                    </button>

                    <button
                        id="btnCetakBelakangSelected"
                        class="btn btn-outline-primary"
                        type="button"
                    >
                        Cetak Belakang Terpilih A4
                    </button>

                    <button
                        id="btnCetakDepanKelas"
                        class="btn btn-success"
                        type="button"
                    >
                        Cetak Depan Per Kelas
                    </button>

                    <button
                        id="btnCetakBelakangKelas"
                        class="btn btn-outline-success"
                        type="button"
                    >
                        Cetak Belakang Per Kelas
                    </button>
                </div>

                <div class="form-text mt-2">
                    Layout A4: 2 kolom × 5 baris = 10 kartu per lembar.
                    Maksimum <?= (int) ($initial['max_print'] ?? 200) ?>
                    kartu per file PDF.
                </div>
            </div>
        </div>

        <?php endif; ?>

        <div
            id="kartuAlert"
            class="alert d-none"
        ></div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <?php if (!empty($initial['can_manage'])): ?>
                                <th style="width:40px">
                                    <input
                                        id="checkAllKartu"
                                        class="form-check-input"
                                        type="checkbox"
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

            <div class="card-footer d-flex justify-content-between">
                <small
                    id="kartuInfo"
                    class="text-muted"
                ></small>

                <div class="btn-group">
                    <button
                        id="kartuPrev"
                        class="btn btn-sm btn-outline-secondary"
                        type="button"
                    >
                        Sebelumnya
                    </button>

                    <button
                        id="kartuNext"
                        class="btn btn-sm btn-outline-secondary"
                        type="button"
                    >
                        Berikutnya
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
