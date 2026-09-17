<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<div id="prestasiApp" data-base-url="<?= esc(base_url()) ?>">
    <div class="sisfour-page-header">
        <div class="sisfour-page-header__copy">
            <h4 class="fw-bold mb-1">Prestasi Siswa</h4>
            <p class="text-muted mb-0">Data otomatis mengikuti scope user.</p>
        </div>
        <?php if (!empty($initial['can_manage'])): ?>
            <div class="sisfour-page-actions"><button class="btn btn-primary" id="btnPrestasiBaru" type="button"><i class="bx bx-plus me-1"></i> Tambah Prestasi</button></div>
        <?php endif; ?>
    </div>

    <?php if (empty($initial['success'])): ?>
        <div class="alert alert-danger"><?= esc($initial['message'] ?? 'Data tidak dapat dibuka.') ?></div>
    <?php else: ?>
        <div class="card sisfour-filter-card mb-4"><div class="card-body"><div class="row g-3 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label" for="prestasiTahun">Tahun Ajaran</label>
                <select id="prestasiTahun" name="id_tahun" class="form-select" data-searchable-off="1">
                    <?php foreach (($initial['tahun_options'] ?? []) as $ta): ?>
                        <?php $aktif = (int) ($ta['status_aktif'] ?? 0) === 1; ?>
                        <option value="<?= (int) $ta['id'] ?>" <?= (int) ($initial['tahun_dipilih']['id'] ?? 0) === (int) $ta['id'] ? 'selected' : '' ?>><?= esc($ta['nama_tahun'] . ' - ' . $ta['semester'] . ($aktif ? ' (Aktif)' : '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-4"><label class="form-label" for="prestasiSearch">Pencarian</label><input id="prestasiSearch" class="form-control" placeholder="Cari siswa / prestasi / penyelenggara"></div>
            <div class="col-12 col-sm-6 col-md-2"><label class="form-label" for="prestasiTingkat">Tingkat</label><select id="prestasiTingkat" class="form-select" data-searchable-off="1"><option value="">Semua tingkat</option><?php foreach (($initial['tingkat_options'] ?? []) as $t): ?><option value="<?= esc($t) ?>"><?= esc($t) ?></option><?php endforeach; ?></select></div>
            <div class="col-6 col-md-3"><label class="form-label" for="prestasiMulai">Dari</label><input id="prestasiMulai" type="date" class="form-control"></div>
            <div class="col-6 col-md-3"><label class="form-label" for="prestasiSelesai">Sampai</label><input id="prestasiSelesai" type="date" class="form-control"></div>
            <div class="col-12 col-md-6"><div class="sisfour-filter-actions justify-content-md-end"><button id="btnPrestasiReset" type="button" class="btn btn-outline-secondary"><i class="bx bx-reset me-1"></i> Reset</button><button id="btnPrestasiCari" type="button" class="btn btn-primary"><i class="bx bx-filter-alt me-1"></i> Tampilkan</button></div></div>
        </div></div></div>

        <div id="prestasiAlert" class="alert d-none" role="alert"></div>
        <div class="card sisfour-table-card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2"><h5 class="mb-0">Riwayat Prestasi</h5><a href="#" id="btnPrestasiExport" class="btn btn-sm btn-outline-primary"><i class="bx bx-export me-1"></i> Export XLSX</a></div>
            <div id="prestasiMobileList" class="d-md-none list-group list-group-flush"></div>
            <div class="d-none d-md-block table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Tanggal</th><th>Siswa</th><th>Prestasi</th><th>Tingkat</th><th>Penyelenggara</th><th>Keterangan</th><?php if (!empty($initial['can_manage'])): ?><th>Aksi</th><?php endif; ?></tr></thead><tbody id="prestasiBody"></tbody></table></div>
            <div class="card-footer"><div id="bkPrestasiPager"></div></div>
        </div>

        <?php if (!empty($initial['can_manage'])): ?>
            <div class="modal fade" id="modalPrestasi" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-scrollable"><form class="modal-content" id="formPrestasi">
                <div class="modal-header"><h5 class="modal-title" id="judulModalPrestasi">Prestasi Siswa</h5><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
                <div class="modal-body">
                    <input type="hidden" name="id">
                    <div class="mb-3"><label class="form-label" for="prestasiSiswa">Siswa</label><select id="prestasiSiswa" name="id_siswa" class="form-select" required data-searchable-remote="<?= esc(base_url('ui/search/siswa')) ?>" data-searchable-context="prestasi" data-searchable-min-chars="2" data-search-placeholder="Ketik nama / NISN / NIK..."><option value="">Cari siswa</option></select><div class="form-text">Ketik minimal 2 karakter.</div></div>
                    <div class="mb-3"><label class="form-label" for="namaPrestasi">Nama Prestasi</label><input id="namaPrestasi" name="nama_prestasi" maxlength="200" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label" for="tingkatPrestasi">Tingkat</label><select id="tingkatPrestasi" name="tingkat" class="form-select" required data-searchable-off="1"><?php foreach (($initial['tingkat_options'] ?? []) as $t): ?><option value="<?= esc($t) ?>"><?= esc($t) ?></option><?php endforeach; ?></select></div>
                    <div class="mb-3"><label class="form-label" for="tanggalPrestasi">Tanggal</label><input id="tanggalPrestasi" name="tanggal" type="date" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label" for="penyelenggaraPrestasi">Penyelenggara</label><input id="penyelenggaraPrestasi" name="penyelenggara" maxlength="200" class="form-control"></div>
                    <div><label class="form-label" for="keteranganPrestasi">Keterangan</label><textarea id="keteranganPrestasi" name="keterangan" rows="3" class="form-control"></textarea></div>
                </div>
                <div class="modal-footer sisfour-modal-actions"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
            </form></div></div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>