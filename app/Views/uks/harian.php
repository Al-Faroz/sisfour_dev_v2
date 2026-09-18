<?= $this->extend('main') ?>
<?= $this->section('content') ?>
<?php
$scope = (string) ($initial['scope'] ?? '');
$selfScope = $scope === 'DIRI_SENDIRI';
$canManage = !empty($initial['can_manage']);
$canExport = !empty($initial['can_export']);
$refs = $initial['refs'] ?? ['keluhan' => [], 'tindakan' => [], 'hasil' => []];
?>

<div id="uksHarianApp" data-base-url="<?= esc(base_url()) ?>" data-manage="<?= $canManage ? '1' : '0' ?>">
    <div class="sisfour-page-header">
        <div class="sisfour-page-header__copy">
            <h4 class="fw-bold mb-1">Catatan Harian UKS</h4>
            <p class="text-muted mb-0">Satu kunjungan siswa = satu catatan UKS.</p>
        </div>
        <?php if ($canManage): ?><div class="sisfour-page-actions"><button class="btn btn-primary" id="btnHarianBaru" type="button"><i class="bx bx-plus me-1"></i> Tambah Kunjungan</button></div><?php endif; ?>
    </div>

    <?php if (empty($initial['success'])): ?>
        <div class="alert alert-danger"><?= esc($initial['message'] ?? 'Catatan UKS tidak dapat dibuka.') ?></div>
    <?php else: ?>
        <div class="card sisfour-filter-card mb-4"><div class="card-body"><div class="row g-3 align-items-end">
            <div class="col-12 col-md-3"><label class="form-label" for="harianTahun">Tahun Ajaran</label><select id="harianTahun" class="form-select" data-searchable-off="1"><?php foreach (($initial['tahun_options'] ?? []) as $ta): ?><option value="<?= (int) $ta['id'] ?>" <?= (int) ($initial['tahun_dipilih']['id'] ?? 0) === (int) $ta['id'] ? 'selected' : '' ?>><?= esc($ta['nama_tahun'] . ' - ' . $ta['semester'] . ((int) ($ta['status_aktif'] ?? 0) === 1 ? ' (Aktif)' : '')) ?></option><?php endforeach; ?></select></div>
            <?php if (!$selfScope): ?>
                <div class="col-12 col-md-3"><label class="form-label" for="harianKelas">Kelas</label><select id="harianKelas" class="form-select" data-searchable-off="1"><option value="">Semua kelas</option><?php foreach (($initial['kelas_options'] ?? []) as $kelas): ?><option value="<?= (int) $kelas['id'] ?>"><?= esc($kelas['nama_kelas']) ?></option><?php endforeach; ?></select></div>
                <div class="col-12 col-md-6"><label class="form-label" for="harianSearch">Pencarian</label><input id="harianSearch" class="form-control" placeholder="Nama / NISN / kelas / keluhan"></div>
            <?php endif; ?>
            <div class="col-6 col-md-3"><label class="form-label" for="harianMulai">Dari</label><input id="harianMulai" type="date" class="form-control"></div>
            <div class="col-6 col-md-3"><label class="form-label" for="harianSelesai">Sampai</label><input id="harianSelesai" type="date" class="form-control"></div>
            <div class="col-6 col-md-3"><label class="form-label" for="harianKeluhan">Keluhan</label><select id="harianKeluhan" class="form-select" data-searchable-off="1"><option value="">Semua</option><?php foreach (($refs['keluhan'] ?? []) as $row): ?><option value="<?= (int) $row['id'] ?>"><?= esc($row['nama']) ?></option><?php endforeach; ?></select></div>
            <div class="col-6 col-md-3"><label class="form-label" for="harianHasil">Hasil</label><select id="harianHasil" class="form-select" data-searchable-off="1"><option value="">Semua</option><?php foreach (($refs['hasil'] ?? []) as $row): ?><option value="<?= (int) $row['id'] ?>"><?= esc($row['nama']) ?></option><?php endforeach; ?></select></div>
            <div class="col-12"><div class="sisfour-filter-actions justify-content-md-end"><button id="btnHarianReset" class="btn btn-outline-secondary" type="button"><i class="bx bx-reset me-1"></i> Reset</button><button id="btnHarianCari" class="btn btn-primary" type="button"><i class="bx bx-filter-alt me-1"></i> Tampilkan</button></div></div>
        </div></div></div>

        <div id="harianAlert" class="alert d-none"></div>

        <div class="card sisfour-table-card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2"><h5 class="mb-0">Riwayat Kunjungan</h5><?php if ($canExport): ?><a href="#" id="btnHarianExport" class="btn btn-sm btn-outline-primary"><i class="bx bx-export me-1"></i> Export XLSX</a><?php endif; ?></div>
            <div id="harianMobileList" class="d-md-none list-group list-group-flush"></div>
            <div class="d-none d-md-block table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Waktu</th><th>Siswa</th><th>Keluhan</th><th>Tindakan</th><th>Hasil</th><th>Petugas</th><?php if ($canManage): ?><th>Aksi</th><?php endif; ?></tr></thead><tbody id="harianBody"></tbody></table></div>
            <div class="card-footer"><div id="uksHarianPager"></div></div>
        </div>

        <?php if ($canManage): ?>
        <div class="modal fade" id="modalHarian" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <form class="modal-content" id="formHarian">
                    <div class="modal-header"><h5 class="modal-title" id="judulModalHarian">Catatan UKS</h5><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
                    <div class="modal-body">
                        <input type="hidden" name="id">
                        <div class="row g-3">
                            <div class="col-12 col-md-6"><label class="form-label">Siswa</label><select name="id_siswa" class="form-select" required data-searchable-remote="<?= esc(base_url('ui/search/siswa')) ?>" data-searchable-context="uks_harian" data-searchable-min-chars="2"><option value="">Cari nama / NISN</option></select></div>
                            <div class="col-6 col-md-3"><label class="form-label">Tanggal</label><input name="tanggal" type="date" class="form-control" required></div>
                            <div class="col-6 col-md-3"><label class="form-label">Jam masuk</label><input name="jam_masuk" type="time" class="form-control" required></div>
                            <div class="col-12 col-md-4"><label class="form-label">Keluhan</label><select name="id_keluhan" class="form-select" required data-searchable-off="1"><?php foreach (($refs['keluhan'] ?? []) as $row): ?><option value="<?= (int) $row['id'] ?>"><?= esc($row['nama']) ?></option><?php endforeach; ?></select></div>
                            <div class="col-12 col-md-8"><label class="form-label">Catatan keluhan</label><input name="catatan_keluhan" class="form-control"></div>
                            <div class="col-6 col-md-3"><label class="form-label">Suhu tubuh °C</label><input name="suhu_tubuh" type="number" min="0" step="0.1" class="form-control"></div>
                            <div class="col-6 col-md-3"><label class="form-label">Tekanan darah</label><input name="tekanan_darah" maxlength="20" class="form-control" placeholder="120/80"></div>
                            <div class="col-12 col-md-6"><label class="form-label">Obat yang diberikan</label><input name="obat_diberikan" maxlength="255" class="form-control"></div>
                            <div class="col-12"><label class="form-label d-block">Tindakan</label><div class="row g-2"><?php foreach (($refs['tindakan'] ?? []) as $row): ?><div class="col-12 col-md-4"><div class="form-check"><input class="form-check-input harian-action" type="checkbox" name="tindakan[]" value="<?= (int) $row['id'] ?>" id="tindakan_<?= (int) $row['id'] ?>"><label class="form-check-label" for="tindakan_<?= (int) $row['id'] ?>"><?= esc($row['nama']) ?></label></div></div><?php endforeach; ?></div></div>
                            <div class="col-6 col-md-3"><label class="form-label">Jam keluar</label><input name="jam_keluar" type="time" class="form-control"></div>
                            <div class="col-12 col-md-5"><label class="form-label">Hasil</label><select name="id_hasil" class="form-select" required data-searchable-off="1"><?php foreach (($refs['hasil'] ?? []) as $row): ?><option value="<?= (int) $row['id'] ?>"><?= esc($row['nama']) ?></option><?php endforeach; ?></select></div>
                            <div class="col-6 col-md-4"><label class="form-label">Orang tua dihubungi</label><select name="orang_tua_dihubungi" class="form-select" data-searchable-off="1"><option value="Tidak">Tidak</option><option value="Ya">Ya</option></select></div>
                        </div>
                    </div>
                    <div class="modal-footer sisfour-modal-actions"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
