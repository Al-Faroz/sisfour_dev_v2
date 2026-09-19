<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<div id="kelulusanSiswaApp" data-base-url="<?= esc(base_url()) ?>">
    <div class="sisfour-page-header">
        <div class="sisfour-page-header__copy">
            <h4 class="fw-bold mb-1">Kelulusan Siswa</h4>
            <p class="text-muted mb-0">Proses kelulusan siswa tingkat 9 secara terkontrol per kelas.</p>
        </div>
    </div>

    <ul class="nav nav-tabs flex-nowrap overflow-x-auto mb-4" id="kelulusanSiswaTabs" role="tablist">
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
                <div id="kelulusanKelasMobileList" class="d-md-none list-group list-group-flush">
                    <?php foreach ($sourceClasses as $kelas): ?>
                        <div class="list-group-item py-3">
                            <div class="fw-semibold sisfour-wrap-anywhere"><?= esc($kelas['nama_kelas']) ?></div>
                            <div class="small text-muted mt-1"><?= (int) $kelas['jumlah_siswa'] ?> siswa</div>
                            <div class="sisfour-mobile-actions mt-3">
                                <button type="button" class="btn btn-sm btn-danger sisfour-touch-target--compact btn-proses-lulus" data-id="<?= (int) $kelas['id'] ?>" data-nama="<?= esc($kelas['nama_kelas'], 'attr') ?>">Proses</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($sourceClasses === []): ?>
                        <div class="list-group-item sisfour-mobile-state text-muted">Tidak ada kelas tingkat 9 pada tahun ajaran aktif.</div>
                    <?php endif; ?>
                </div>
                <div class="d-none d-md-block table-responsive">
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
                                        <button type="button" class="btn btn-sm btn-danger sisfour-touch-target--compact btn-proses-lulus" data-id="<?= (int) $kelas['id'] ?>" data-nama="<?= esc($kelas['nama_kelas']) ?>">
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
            <div class="alert alert-info sisfour-compact-note">
                <i class="bx bx-info-circle me-1"></i>
                Restore siswa Lulus hanya tersedia bila tahun ajaran/semester kelulusannya masih menjadi periode aktif. Kelulusan dari periode nonaktif tetap menjadi histori alumni dan tidak dapat dibuka kembali.
            </div>

            <div class="card sisfour-table-card">
                <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                    <div>
                        <h5 class="mb-1">Alumni / Siswa Lulus</h5>
                        <p class="text-muted small mb-0">Daftar siswa yang sudah diproses Lulus tetap tersedia sebagai histori alumni.</p>
                    </div>
                    <span class="badge bg-label-success"><?= count($alumniRows ?? []) ?> alumni</span>
                </div>
                <div id="alumniMobileList" class="d-md-none list-group list-group-flush">
                    <?php foreach (($alumniRows ?? []) as $row): ?>
                        <?php $canRestore = !empty($row['can_restore']); ?>
                        <div class="list-group-item py-3">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                <div class="min-w-0 flex-grow-1">
                                    <div class="fw-semibold sisfour-wrap-anywhere"><?= esc($row['nama'] ?? '-') ?></div>
                                    <div class="small text-muted font-monospace sisfour-wrap-anywhere"><?= esc($row['nisn'] ?? '-') ?></div>
                                </div>
                                <span class="badge <?= (int) ($row['tahun_aktif'] ?? 0) === 1 ? 'bg-label-success' : 'bg-label-secondary' ?> flex-shrink-0">
                                    <?= (int) ($row['tahun_aktif'] ?? 0) === 1 ? 'Periode Aktif' : 'Periode Nonaktif' ?>
                                </span>
                            </div>
                            <div class="small mt-2 sisfour-wrap-anywhere"><?= esc($row['nama_kelas'] ?? '-') ?> · <?= esc($row['nama_tahun'] ?? '-') ?><?= !empty($row['semester']) ? ' - ' . esc($row['semester']) : '' ?></div>
                            <div class="small text-muted mt-1 sisfour-wrap-anywhere">Lulus <?= esc($row['tanggal_selesai'] ?? $row['tanggal_mutasi'] ?? '-') ?> · <?= esc($row['keterangan'] ?? $row['keterangan_mutasi'] ?? '-') ?></div>
                            <div class="sisfour-mobile-actions mt-3">
                                <?php if ($canRestore): ?>
                                    <button type="button" class="btn btn-sm btn-outline-primary sisfour-touch-target--compact btn-restore-lulus" data-history-id="<?= (int) $row['id'] ?>" data-nama="<?= esc($row['nama'] ?? '', 'attr') ?>"><i class="bx bx-undo me-1"></i>Restore</button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-sm btn-outline-secondary sisfour-touch-target--compact" disabled>Restore</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (($alumniRows ?? []) === []): ?>
                        <div class="list-group-item sisfour-mobile-state text-muted">Belum ada data alumni / siswa Lulus.</div>
                    <?php endif; ?>
                </div>
                <div class="d-none d-md-block table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width:56px;">No.</th>
                                <th>Siswa</th>
                                <th>Kelas Terakhir</th>
                                <th>Tahun Ajaran</th>
                                <th>Tanggal Lulus</th>
                                <th>Keterangan</th>
                                <th style="width:130px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyAlumni">
                            <?php foreach (($alumniRows ?? []) as $index => $row): ?>
                                <?php $canRestore = !empty($row['can_restore']); ?>
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
                                        <?php if ((int) ($row['tahun_aktif'] ?? 0) === 1): ?>
                                            <span class="badge bg-label-success mt-1">Aktif</span>
                                        <?php else: ?>
                                            <span class="badge bg-label-secondary mt-1">Nonaktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= esc($row['tanggal_selesai'] ?? $row['tanggal_mutasi'] ?? '-') ?></td>
                                    <td><?= esc($row['keterangan'] ?? $row['keterangan_mutasi'] ?? '-') ?></td>
                                    <td>
                                        <?php if ($canRestore): ?>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-primary sisfour-touch-target--compact btn-restore-lulus"
                                                data-history-id="<?= (int) $row['id'] ?>"
                                                data-nama="<?= esc($row['nama'] ?? '', 'attr') ?>"
                                            >
                                                <i class="bx bx-undo me-1"></i>Restore
                                            </button>
                                        <?php else: ?>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-secondary sisfour-touch-target--compact"
                                                disabled
                                                title="Restore hanya tersedia untuk histori kelulusan terbaru pada periode yang masih aktif."
                                            >
                                                Restore
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (($alumniRows ?? []) === []): ?>
                                <tr class="sisfour-empty-row">
                                    <td colspan="7" class="text-muted text-center py-4">Belum ada data alumni / siswa Lulus.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalKelulusan" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-sm-down">
            <form id="formKelulusan" class="modal-content">
                <?= csrf_field() ?>
                <input type="hidden" id="idKelasLulus">
                <div class="modal-header py-2">
                    <div>
                        <h5 class="modal-title">Proses Kelulusan</h5>
                        <div class="small text-muted" id="labelKelasLulus"></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body overflow-auto py-3">
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-2">
                        <strong id="jumlahLulusDipilih">0 siswa dipilih</strong>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary sisfour-touch-target--compact" id="btnPilihSemuaLulus">Pilih Semua</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary sisfour-touch-target--compact" id="btnKosongkanLulus">Kosongkan</button>
                        </div>
                    </div>
                    <div class="table-responsive border rounded sisfour-admin-matrix-scroll" data-mobile-exception="bulk-student-selection">
                        <table class="table table-hover mb-0">
                            <thead><tr><th style="width:50px;"></th><th>Nama</th><th>NISN</th><th>JK</th></tr></thead>
                            <tbody id="tbodyLulus"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer py-2 sisfour-modal-actions">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Proses Kelulusan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>