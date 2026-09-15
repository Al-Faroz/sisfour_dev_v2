<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<div id="kelulusanSiswaApp" data-base-url="<?= esc(base_url()) ?>">
    <div class="sisfour-page-header">
        <div class="sisfour-page-header__copy">
            <h4 class="fw-bold mb-1">Kelulusan Siswa</h4>
            <p class="text-muted mb-0">Proses kelulusan siswa tingkat 9 secara terkontrol per kelas.</p>
        </div>
    </div>

    <ul class="nav nav-tabs mb-4" id="kelulusanSiswaTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button
                class="nav-link active"
                id="kelulusan-proses-tab"
                data-bs-toggle="tab"
                data-bs-target="#kelulusan-proses-pane"
                type="button"
                role="tab"
                aria-controls="kelulusan-proses-pane"
                aria-selected="true"
            >
                <i class="bx bx-check-circle me-1"></i>
                Proses Kelulusan
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button
                class="nav-link"
                id="kelulusan-alumni-tab"
                data-bs-toggle="tab"
                data-bs-target="#kelulusan-alumni-pane"
                type="button"
                role="tab"
                aria-controls="kelulusan-alumni-pane"
                aria-selected="false"
            >
                <i class="bx bx-group me-1"></i>
                Alumni / Siswa Lulus
                <span class="badge bg-label-success ms-1"><?= count($alumniRows ?? []) ?></span>
            </button>
        </li>
    </ul>

    <div class="tab-content p-0 bg-transparent shadow-none" id="kelulusanSiswaTabContent">
        <div
            class="tab-pane fade show active"
            id="kelulusan-proses-pane"
            role="tabpanel"
            aria-labelledby="kelulusan-proses-tab"
            tabindex="0"
        >
            <div class="alert alert-warning sisfour-compact-note">
                <i class="bx bx-error me-1"></i>
                Kelulusan mengubah status siswa menjadi <strong>Lulus</strong>, menutup histori aktif, dan menonaktifkan kartu pelajar sesuai business rule.
            </div>

            <div class="card sisfour-table-card">
                <div class="card-header"><h5 class="mb-0">Kelas Tingkat 9</h5></div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr><th>Kelas</th><th>Jumlah Siswa</th><th style="width:160px;">Aksi</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sourceClasses as $kelas): ?>
                                <tr>
                                    <td class="fw-semibold"><?= esc($kelas['nama_kelas']) ?></td>
                                    <td><?= (int) $kelas['jumlah_siswa'] ?> siswa</td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-danger btn-proses-lulus" data-id="<?= (int) $kelas['id'] ?>" data-nama="<?= esc($kelas['nama_kelas']) ?>">
                                            Proses
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ($sourceClasses === []): ?>
                                <tr class="sisfour-empty-row"><td colspan="3" class="text-muted">Tidak ada kelas tingkat 9 pada tahun ajaran aktif.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div
            class="tab-pane fade"
            id="kelulusan-alumni-pane"
            role="tabpanel"
            aria-labelledby="kelulusan-alumni-tab"
            tabindex="0"
        >
            <div class="card sisfour-table-card">
                <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                    <div>
                        <h5 class="mb-1">Alumni / Siswa Lulus</h5>
                        <p class="text-muted small mb-0">Daftar siswa yang sudah diproses Lulus tetap tersedia sebagai histori alumni.</p>
                    </div>
                    <span class="badge bg-label-success"><?= count($alumniRows ?? []) ?> alumni</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width:56px;">No.</th>
                                <th>Siswa</th>
                                <th>Kelas Terakhir</th>
                                <th>Tahun Ajaran</th>
                                <th>Tanggal Lulus</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (($alumniRows ?? []) as $index => $row): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td>
                                        <div class="fw-semibold"><?= esc($row['nama'] ?? '-') ?></div>
                                        <small class="text-muted font-monospace"><?= esc($row['nisn'] ?? '-') ?></small>
                                    </td>
                                    <td><?= esc($row['nama_kelas'] ?? '-') ?></td>
                                    <td>
                                        <?= esc($row['nama_tahun'] ?? '-') ?>
                                        <?php if (!empty($row['semester'])): ?>
                                            <div class="small text-muted"><?= esc($row['semester']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= esc($row['tanggal_selesai'] ?? $row['tanggal_mutasi'] ?? '-') ?></td>
                                    <td><?= esc($row['keterangan'] ?? $row['keterangan_mutasi'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (($alumniRows ?? []) === []): ?>
                                <tr class="sisfour-empty-row">
                                    <td colspan="6" class="text-muted text-center py-4">Belum ada data alumni / siswa Lulus.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalKelulusan" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <form id="formKelulusan">
                    <?= csrf_field() ?>
                    <input type="hidden" id="idKelasLulus">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title">Proses Kelulusan</h5>
                            <div class="small text-muted" id="labelKelasLulus"></div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-2">
                            <strong id="jumlahLulusDipilih">0 siswa dipilih</strong>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" id="btnPilihSemuaLulus">Pilih Semua</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnKosongkanLulus">Kosongkan</button>
                            </div>
                        </div>
                        <div class="table-responsive border rounded">
                            <table class="table table-hover mb-0">
                                <thead><tr><th style="width:50px;"></th><th>Nama</th><th>NISN</th><th>JK</th></tr></thead>
                                <tbody id="tbodyLulus"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer sisfour-modal-actions">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Proses Kelulusan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>