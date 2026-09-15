<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<div id="laporanJurnalApp" data-base-url="<?= esc(base_url()) ?>">
    <div class="sisfour-page-header">
        <div class="sisfour-page-header__copy">
            <h4 class="fw-bold mb-1">Laporan Jurnal Mengajar</h4>
            <p class="text-muted mb-0">Satu data mewakili satu Jurnal. Siswa S/I/A ditampilkan sebagai ringkasan dan Detail.</p>
        </div>
    </div>

    <?php if (empty($options['success'])): ?>
        <div class="alert alert-danger"><?= esc($options['message'] ?? 'Laporan Jurnal tidak dapat dibuka.') ?></div>
    <?php else: ?>
        <div
            id="jurnalOptions"
            data-scope="<?= esc($options['scope'] ?? '') ?>"
            data-fixed-guru="<?= (int) ($options['id_guru_fixed'] ?? 0) ?>"
        ></div>

        <div class="card sisfour-filter-card mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-3">
                        <label class="form-label" for="jurnalTahun">Tahun Ajaran</label>
                        <select class="form-select" id="jurnalTahun">
                            <?php foreach (($options['tahun'] ?? []) as $tahun): ?>
                                <option
                                    value="<?= (int) $tahun['id'] ?>"
                                    <?= (int) $selectedTahun === (int) $tahun['id'] ? 'selected' : '' ?>
                                >
                                    <?= esc($tahun['nama_tahun'] . ' - ' . $tahun['semester']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if (($options['scope'] ?? '') === 'SEMUA'): ?>
                        <div class="col-12 col-md-3">
                            <label class="form-label" for="jurnalGuru">Guru</label>
                            <select class="form-select" id="jurnalGuru">
                                <option value="">Semua Guru</option>
                                <?php foreach (($options['guru'] ?? []) as $guru): ?>
                                    <option value="<?= (int) $guru['id_guru'] ?>">
                                        <?= esc(($guru['nama_guru'] ?? '-') . (!empty($guru['nip']) ? ' — ' . $guru['nip'] : '')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="col-12 col-md-3">
                        <label class="form-label" for="jurnalKelas">Kelas</label>
                        <select class="form-select" id="jurnalKelas">
                            <option value="">Semua Kelas</option>
                            <?php foreach (($options['kelas'] ?? []) as $kelas): ?>
                                <option value="<?= (int) $kelas['id_kelas'] ?>">
                                    <?= esc($kelas['nama_kelas'] ?? '-') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label" for="jurnalHari">Hari</label>
                        <select class="form-select" id="jurnalHari" data-searchable-off="1">
                            <option value="">Semua Hari</option>
                            <?php foreach (($options['hari'] ?? []) as $hari): ?>
                                <option value="<?= esc($hari) ?>"><?= esc($hari) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label" for="jurnalStatus">Status Guru</label>
                        <select class="form-select" id="jurnalStatus" data-searchable-off="1">
                            <option value="">Semua Status</option>
                            <?php foreach (($options['status'] ?? []) as $status): ?>
                                <option value="<?= esc($status) ?>"><?= esc($status) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-6 col-md-3">
                        <label class="form-label" for="jurnalMulai">Tanggal Awal</label>
                        <input type="date" class="form-control" id="jurnalMulai" value="<?= esc($tanggalMulai) ?>">
                    </div>

                    <div class="col-6 col-md-3">
                        <label class="form-label" for="jurnalSelesai">Tanggal Akhir</label>
                        <input type="date" class="form-control" id="jurnalSelesai" value="<?= esc($tanggalSelesai) ?>">
                    </div>

                    <div class="col-12 col-md-3 d-grid">
                        <button type="button" class="btn btn-primary sisfour-primary-action" id="btnJurnalCari">
                            <i class="bx bx-filter-alt me-1"></i> Tampilkan
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="jurnalAlert" class="alert alert-info d-none" role="alert" aria-live="polite"></div>

        <div class="card sisfour-table-card" id="laporanJurnalCard">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="mb-0">Histori Jurnal</h5>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btnJurnalExport">
                    <i class="bx bx-export me-1"></i> Export XLSX
                </button>
            </div>

            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Guru / Kelas</th>
                            <th>Pembelajaran</th>
                            <th>Status</th>
                            <th>Materi / Catatan</th>
                            <th>Siswa S/I/A</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="jurnalBody">
                        <tr class="sisfour-loading-row">
                            <td colspan="7" class="text-muted">Memuat data...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="d-md-none p-2" id="jurnalMobileList">
                <div class="sisfour-mobile-state text-muted">Memuat data...</div>
            </div>

            <div class="card-footer">
                <div id="laporanJurnalPager"></div>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="laporanJurnalDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header py-2">
                <div>
                    <h5 class="modal-title" id="laporanJurnalDetailTitle">Detail Jurnal</h5>
                    <small class="text-muted" id="laporanJurnalDetailMeta"></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body overflow-auto">
                <div id="laporanJurnalDetailInfo" class="alert alert-info d-none" role="alert"></div>
                <div id="laporanJurnalDetailContent" class="d-none">
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="badge bg-label-secondary" id="laporanJurnalDetailStatus"></span>
                        <span class="badge bg-label-warning">S <span id="laporanJurnalDetailSakit">0</span></span>
                        <span class="badge bg-label-info">I <span id="laporanJurnalDetailIzin">0</span></span>
                        <span class="badge bg-label-danger">A <span id="laporanJurnalDetailAlpha">0</span></span>
                    </div>

                    <div class="mb-3">
                        <div class="text-muted small mb-1">Materi / Keterangan</div>
                        <div class="border rounded p-3 text-break" id="laporanJurnalDetailMateri">-</div>
                    </div>

                    <div class="mb-4">
                        <div class="text-muted small mb-1">Catatan</div>
                        <div class="border rounded p-3 text-break" id="laporanJurnalDetailCatatan">-</div>
                    </div>

                    <div>
                        <h6 class="mb-2">Siswa Tidak Mengikuti Pembelajaran</h6>
                        <div class="d-flex flex-column gap-2" id="laporanJurnalDetailStudents"></div>
                        <div class="text-muted small py-2 d-none" id="laporanJurnalDetailEmpty">
                            Tidak ada siswa Sakit/Izin/Alpha pada Jurnal ini.
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>