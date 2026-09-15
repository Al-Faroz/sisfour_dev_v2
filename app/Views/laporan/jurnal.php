<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<div id="laporanJurnalApp" data-base-url="<?= esc(base_url()) ?>">
    <div class="sisfour-page-header">
        <div class="sisfour-page-header__copy">
            <h4 class="fw-bold mb-1">Laporan Jurnal Mengajar</h4>
            <p class="text-muted mb-0">Histori tetap menampilkan Jurnal yang merujuk Jadwal Nonaktif.</p>
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
                        <select class="form-select" id="jurnalHari">
                            <option value="">Semua Hari</option>
                            <?php foreach (($options['hari'] ?? []) as $hari): ?>
                                <option value="<?= esc($hari) ?>"><?= esc($hari) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label" for="jurnalStatus">Status</label>
                        <select class="form-select" id="jurnalStatus">
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
                        <button type="button" class="btn btn-primary" id="btnJurnalCari">
                            <i class="bx bx-filter-alt me-1"></i> Tampilkan
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="jurnalAlert" class="alert alert-info d-none" role="alert"></div>

        <div class="card sisfour-table-card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="mb-0">Histori Jurnal</h5>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btnJurnalExport">
                    <i class="bx bx-export me-1"></i> Export XLSX
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Hari / Jam</th>
                            <th>Guru</th>
                            <th>Kelas</th>
                            <th>Mapel</th>
                            <th>Sesi</th>
                            <th>Status</th>
                            <th>Materi</th>
                        </tr>
                    </thead>
                    <tbody id="jurnalBody">
                        <tr class="sisfour-loading-row">
                            <td colspan="8" class="text-muted">Memuat data...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="card-footer">
                <div id="laporanJurnalPager"></div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>