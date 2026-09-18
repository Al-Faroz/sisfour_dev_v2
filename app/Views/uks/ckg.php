<?= $this->extend('main') ?>
<?= $this->section('content') ?>
<?php
$scope = (string) ($initial['scope'] ?? '');
$selfScope = $scope === 'DIRI_SENDIRI';
$canManage = !empty($initial['can_manage']);
$canImport = !empty($initial['can_import']);
$canExport = !empty($initial['can_export']);
$options = $initial['fixed_options'] ?? [];
?>

<div id="uksCkgApp" data-base-url="<?= esc(base_url()) ?>" data-manage="<?= $canManage ? '1' : '0' ?>">
    <div class="sisfour-page-header">
        <div class="sisfour-page-header__copy">
            <h4 class="fw-bold mb-1">Data CKG</h4>
            <p class="text-muted mb-0">Pemeriksaan kesehatan siswa periodik Tahun Ajaran.</p>
        </div>
        <div class="sisfour-page-actions d-flex flex-wrap gap-2">
            <?php if ($canImport): ?>
                <a class="btn btn-outline-primary" href="<?= esc(base_url('uks/ckg/template')) ?>"><i class="bx bx-download me-1"></i> Template</a>
                <button class="btn btn-outline-primary" id="btnCkgImport" type="button"><i class="bx bx-import me-1"></i> Import</button>
            <?php endif; ?>
            <?php if ($canManage): ?>
                <button class="btn btn-primary" id="btnCkgBaru" type="button"><i class="bx bx-plus me-1"></i> Tambah CKG</button>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($initial['success'])): ?>
        <div class="alert alert-danger"><?= esc($initial['message'] ?? 'Data CKG tidak dapat dibuka.') ?></div>
    <?php else: ?>
        <div class="card sisfour-filter-card mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-3">
                        <label class="form-label" for="ckgTahun">Tahun Ajaran</label>
                        <select id="ckgTahun" class="form-select" data-searchable-off="1">
                            <?php foreach (($initial['tahun_options'] ?? []) as $ta): ?>
                                <option value="<?= (int) $ta['id'] ?>" <?= (int) ($initial['tahun_dipilih']['id'] ?? 0) === (int) $ta['id'] ? 'selected' : '' ?>>
                                    <?= esc($ta['nama_tahun'] . ' - ' . $ta['semester'] . ((int) ($ta['status_aktif'] ?? 0) === 1 ? ' (Aktif)' : '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if (!$selfScope): ?>
                        <div class="col-12 col-md-3">
                            <label class="form-label" for="ckgKelas">Kelas</label>
                            <select id="ckgKelas" class="form-select" data-searchable-off="1">
                                <option value="">Semua kelas</option>
                                <?php foreach (($initial['kelas_options'] ?? []) as $kelas): ?>
                                    <option value="<?= (int) $kelas['id'] ?>"><?= esc($kelas['nama_kelas']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="ckgSearch">Pencarian</label>
                            <input id="ckgSearch" class="form-control" placeholder="Nama / NISN / kelas">
                        </div>
                    <?php endif; ?>
                    <div class="col-6 col-md-3">
                        <label class="form-label" for="ckgMulai">Dari</label>
                        <input id="ckgMulai" type="date" class="form-control">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label" for="ckgSelesai">Sampai</label>
                        <input id="ckgSelesai" type="date" class="form-control">
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="sisfour-filter-actions justify-content-md-end">
                            <button class="btn btn-outline-secondary" id="btnCkgReset" type="button"><i class="bx bx-reset me-1"></i> Reset</button>
                            <button class="btn btn-primary" id="btnCkgCari" type="button"><i class="bx bx-filter-alt me-1"></i> Tampilkan</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="ckgAlert" class="alert d-none" role="alert"></div>

        <div class="card sisfour-table-card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="mb-0">Riwayat CKG</h5>
                <?php if ($canExport): ?><a href="#" id="btnCkgExport" class="btn btn-sm btn-outline-primary"><i class="bx bx-export me-1"></i> Export XLSX</a><?php endif; ?>
            </div>
            <div id="ckgMobileList" class="d-md-none list-group list-group-flush"></div>
            <div class="d-none d-md-block table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Tanggal</th><th>Siswa</th><th>Kelas</th><th>BB/TB</th><th>Status</th><th>Tekanan/Gula</th><?php if ($canManage): ?><th>Aksi</th><?php endif; ?></tr></thead>
                    <tbody id="ckgBody"></tbody>
                </table>
            </div>
            <div class="card-footer"><div id="uksCkgPager"></div></div>
        </div>

        <?php if ($canManage): ?>
        <div class="modal fade" id="modalCkg" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <form class="modal-content" id="formCkg">
                    <div class="modal-header"><h5 class="modal-title" id="judulModalCkg">Data CKG</h5><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
                    <div class="modal-body">
                        <input type="hidden" name="id">
                        <div class="row g-3">
                            <div class="col-12 col-md-8">
                                <label class="form-label" for="ckgSiswa">Siswa</label>
                                <select id="ckgSiswa" name="id_siswa" class="form-select" required data-searchable-remote="<?= esc(base_url('ui/search/siswa')) ?>" data-searchable-context="uks_ckg" data-searchable-min-chars="2"><option value="">Cari nama / NISN</option></select>
                            </div>
                            <div class="col-12 col-md-4"><label class="form-label" for="ckgTanggal">Tanggal</label><input id="ckgTanggal" name="tanggal" type="date" class="form-control" required></div>

                            <?php
                            $numberFields = [
                                'berat_badan' => ['Berat badan (kg)', '0.01'],
                                'tinggi_badan' => ['Tinggi badan (cm)', '0.01'],
                                'lingkar_perut' => ['Lingkar perut (cm)', '0.01'],
                                'tekanan_sistol' => ['Tekanan darah sistol', '1'],
                                'tekanan_diastol' => ['Tekanan darah diastol', '1'],
                                'gula_darah' => ['Gula darah (mg/dL)', '0.01'],
                            ];
                            foreach ($numberFields as $name => [$label, $step]):
                            ?>
                                <div class="col-6 col-md-4"><label class="form-label"><?= esc($label) ?></label><input name="<?= esc($name) ?>" type="number" min="0" step="<?= esc($step) ?>" class="form-control"></div>
                            <?php endforeach; ?>

                            <?php
                            $selectFields = [
                                'status_gizi' => 'Status gizi (BB/U)',
                                'status_tinggi' => 'Status tinggi (TB/U)',
                                'kondisi_gigi_mulut' => 'Kondisi gigi dan mulut',
                                'buta_warna' => 'Buta warna',
                                'hasil_pendengaran' => 'Hasil tes pendengaran',
                                'skrining_talasemia' => 'Skrining talasemia (kelas VII)',
                                'skrining_tuberkulosis' => 'Skrining tuberkulosis',
                            ];
                            foreach ($selectFields as $name => $label):
                            ?>
                                <div class="col-12 col-md-4">
                                    <label class="form-label"><?= esc($label) ?></label>
                                    <select name="<?= esc($name) ?>" class="form-select" data-searchable-off="1">
                                        <option value="">-</option>
                                        <?php foreach (($options[$name] ?? []) as $opt): ?><option value="<?= esc($opt) ?>"><?= esc($opt) ?></option><?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endforeach; ?>

                            <div class="col-6"><label class="form-label">Visus mata kanan</label><input name="visus_kanan" maxlength="30" class="form-control"></div>
                            <div class="col-6"><label class="form-label">Visus mata kiri</label><input name="visus_kiri" maxlength="30" class="form-control"></div>
                        </div>
                    </div>
                    <div class="modal-footer sisfour-modal-actions"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" type="submit">Simpan</button></div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($canImport): ?>
        <div class="modal fade" id="modalCkgImport" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form class="modal-content" id="formCkgImport" enctype="multipart/form-data">
                    <div class="modal-header"><h5 class="modal-title">Import CKG</h5><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
                    <div class="modal-body">
                        <div class="alert alert-info small">Stable key: <strong>NISN + tanggal</strong>. Record aktif yang sama diperbarui; record soft-deleted tidak dipulihkan otomatis.</div>
                        <label class="form-label" for="ckgImportFile">File XLSX/XLS</label>
                        <input id="ckgImportFile" name="file" type="file" accept=".xlsx,.xls" class="form-control" required>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" type="submit">Import</button></div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
