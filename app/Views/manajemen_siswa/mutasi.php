<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<div id="mutasiSiswaApp" data-base-url="<?= esc(base_url()) ?>">
    <div class="sisfour-page-header">
        <div class="sisfour-page-header__copy">
            <h4 class="fw-bold mb-1">Mutasi Siswa</h4>
            <p class="text-muted mb-0">Gunakan menu ini untuk siswa yang pindah sekolah atau keluar. Pindah kelas tidak dilakukan dari menu ini.</p>
        </div>
    </div>

    <ul class="nav nav-tabs flex-nowrap overflow-x-auto mb-4" id="mutasiSiswaTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button
                class="nav-link active"
                id="mutasi-aktif-tab"
                data-bs-toggle="tab"
                data-bs-target="#mutasi-aktif-pane"
                type="button"
                role="tab"
                aria-controls="mutasi-aktif-pane"
                aria-selected="true"
            >
                <i class="bx bx-user-check me-1"></i>
                Siswa Aktif
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button
                class="nav-link"
                id="mutasi-riwayat-tab"
                data-bs-toggle="tab"
                data-bs-target="#mutasi-riwayat-pane"
                type="button"
                role="tab"
                aria-controls="mutasi-riwayat-pane"
                aria-selected="false"
            >
                <i class="bx bx-history me-1"></i>
                Riwayat Mutasi
                <span class="badge bg-label-secondary ms-1"><?= count($mutasiHistory ?? []) ?></span>
            </button>
        </li>
    </ul>

    <div class="tab-content p-0 bg-transparent shadow-none" id="mutasiSiswaTabContent">
        <div
            class="tab-pane fade show active"
            id="mutasi-aktif-pane"
            role="tabpanel"
            aria-labelledby="mutasi-aktif-tab"
            tabindex="0"
        >
            <div class="card sisfour-filter-card mb-4">
                <div class="card-body">
                    <form id="formFilterMutasi" class="row g-3 align-items-end">
                        <div class="col-12 col-md-7">
                            <label class="form-label" for="filterMutasiQ">Cari Siswa</label>
                            <input type="search" class="form-control" id="filterMutasiQ" name="q" placeholder="Nama, NISN, atau NIK">
                        </div>
                        <div class="col-12 col-md-5">
                            <label class="form-label" for="filterMutasiKelas">Kelas</label>
                            <select class="form-select" id="filterMutasiKelas" name="id_kelas">
                                <option value="">Semua</option>
                                <?php foreach ($kelasOptions as $kelas): ?>
                                    <option value="<?= (int) $kelas['id'] ?>"><?= esc($kelas['nama_kelas']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 sisfour-filter-actions">
                            <button type="button" class="btn btn-outline-secondary sisfour-touch-target--compact" id="btnResetMutasi">Reset</button>
                            <button type="submit" class="btn btn-primary sisfour-primary-action">
                                <i class="bx bx-filter-alt me-1"></i> Terapkan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card sisfour-table-card">
                <div class="card-header"><h5 class="mb-0">Daftar Siswa Aktif</h5></div>
                <div id="mutasiMobileList" class="d-md-none list-group list-group-flush">
                    <div class="list-group-item sisfour-mobile-state text-muted">Memuat siswa aktif...</div>
                </div>
                <div class="d-none d-md-block table-responsive">
                    <table class="table table-hover align-middle mb-0" id="tableMutasiSiswa">
                        <thead>
                            <tr><th style="width:56px;">No.</th><th>Nama</th><th>NISN</th><th>Kelas</th><th>JK</th><th style="width:140px;">Aksi</th></tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div
            class="tab-pane fade"
            id="mutasi-riwayat-pane"
            role="tabpanel"
            aria-labelledby="mutasi-riwayat-tab"
            tabindex="0"
        >
            <div class="alert alert-info sisfour-compact-note">
                <i class="bx bx-info-circle me-1"></i>
                Restore hanya tersedia bila tahun ajaran/semester asal siswa masih menjadi periode aktif. Histori dari periode nonaktif tetap disimpan dan tidak dapat dibuka kembali.
            </div>

            <div class="card sisfour-table-card">
                <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                    <div>
                        <h5 class="mb-1">Riwayat Mutasi</h5>
                        <p class="text-muted small mb-0">Siswa berstatus Pindah atau Keluar disimpan sebagai histori dan tidak muncul lagi pada daftar siswa aktif.</p>
                    </div>
                    <span class="badge bg-label-secondary"><?= count($mutasiHistory ?? []) ?> data</span>
                </div>
                <div id="mutasiHistoryMobileList" class="d-md-none list-group list-group-flush">
                    <?php foreach (($mutasiHistory ?? []) as $row): ?>
                        <?php $canRestore = !empty($row['can_restore']); ?>
                        <div class="list-group-item py-3">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                <div class="min-w-0 flex-grow-1">
                                    <div class="fw-semibold sisfour-wrap-anywhere"><?= esc($row['nama'] ?? '-') ?></div>
                                    <div class="small text-muted font-monospace sisfour-wrap-anywhere"><?= esc($row['nisn'] ?? '-') ?></div>
                                </div>
                                <span class="badge <?= ($row['status'] ?? '') === 'Pindah' ? 'bg-label-warning' : 'bg-label-secondary' ?> flex-shrink-0"><?= esc($row['status'] ?? '-') ?></span>
                            </div>
                            <div class="small mt-2 sisfour-wrap-anywhere"><?= esc($row['nama_kelas'] ?? '-') ?> · <?= esc($row['nama_tahun'] ?? '-') ?><?= !empty($row['semester']) ? ' - ' . esc($row['semester']) : '' ?></div>
                            <div class="small text-muted mt-1 sisfour-wrap-anywhere"><?= esc($row['tanggal_selesai'] ?? $row['tanggal_mutasi'] ?? '-') ?> · <?= esc($row['keterangan'] ?? $row['keterangan_mutasi'] ?? '-') ?></div>
                            <div class="sisfour-mobile-actions mt-3">
                                <?php if ($canRestore): ?>
                                    <button type="button" class="btn btn-sm btn-outline-primary sisfour-touch-target--compact btn-restore-mutasi" data-history-id="<?= (int) $row['id'] ?>" data-nama="<?= esc($row['nama'] ?? '', 'attr') ?>" data-status="<?= esc($row['status'] ?? '', 'attr') ?>"><i class="bx bx-undo me-1"></i>Restore</button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-sm btn-outline-secondary sisfour-touch-target--compact" disabled>Restore</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (($mutasiHistory ?? []) === []): ?>
                        <div class="list-group-item sisfour-mobile-state text-muted">Belum ada riwayat siswa Pindah/Keluar.</div>
                    <?php endif; ?>
                </div>
                <div class="d-none d-md-block table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width:56px;">No.</th>
                                <th>Siswa</th>
                                <th>Status</th>
                                <th>Kelas Terakhir</th>
                                <th>Periode</th>
                                <th>Tanggal</th>
                                <th>Keterangan</th>
                                <th style="width:130px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyRiwayatMutasi">
                            <?php foreach (($mutasiHistory ?? []) as $index => $row): ?>
                                <?php $canRestore = !empty($row['can_restore']); ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td>
                                        <div class="fw-semibold"><?= esc($row['nama'] ?? '-') ?></div>
                                        <small class="text-muted font-monospace"><?= esc($row['nisn'] ?? '-') ?></small>
                                    </td>
                                    <td>
                                        <span class="badge <?= ($row['status'] ?? '') === 'Pindah' ? 'bg-label-warning' : 'bg-label-secondary' ?>">
                                            <?= esc($row['status'] ?? '-') ?>
                                        </span>
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
                                                class="btn btn-sm btn-outline-primary sisfour-touch-target--compact btn-restore-mutasi"
                                                data-history-id="<?= (int) $row['id'] ?>"
                                                data-nama="<?= esc($row['nama'] ?? '', 'attr') ?>"
                                                data-status="<?= esc($row['status'] ?? '', 'attr') ?>"
                                            >
                                                <i class="bx bx-undo me-1"></i>Restore
                                            </button>
                                        <?php else: ?>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-secondary"
                                                disabled
                                                title="Restore hanya tersedia untuk histori terminal terbaru pada periode yang masih aktif."
                                            >
                                                Restore
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (($mutasiHistory ?? []) === []): ?>
                                <tr class="sisfour-empty-row">
                                    <td colspan="8" class="text-muted text-center py-4">Belum ada riwayat siswa Pindah/Keluar.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalMutasi" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-sm-down">
            <form id="formMutasi" class="modal-content">
                <?= csrf_field() ?>
                <input type="hidden" id="idSiswaMutasi">
                <div class="modal-header py-2">
                    <h5 class="modal-title">Proses Mutasi Siswa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body overflow-auto py-3">
                    <div class="mb-3">
                        <label class="form-label" for="namaSiswaMutasi">Siswa</label>
                        <input type="text" class="form-control" id="namaSiswaMutasi" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="statusMutasi">Status</label>
                        <select class="form-select" id="statusMutasi" name="status" required data-searchable-off="1">
                            <option value="">Pilih</option>
                            <option value="Pindah">Pindah Sekolah</option>
                            <option value="Keluar">Keluar</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="keteranganMutasi">Keterangan</label>
                        <textarea class="form-control" id="keteranganMutasi" name="keterangan" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer py-2 sisfour-modal-actions">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Proses Mutasi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>