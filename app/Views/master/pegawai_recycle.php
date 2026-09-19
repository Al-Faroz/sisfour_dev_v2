<?= $this->extend('main') ?>

<?= $this->section('content') ?>
<div id="pegawaiRecycleApp" data-base-url="<?= esc(base_url()) ?>">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Recycle Bin Pegawai</h4>
            <p class="text-muted mb-0">Pulihkan data Pegawai atau hapus permanen bila sudah tidak direferensikan data lain.</p>
        </div>
        <a href="<?= base_url('master/pegawai') ?>" class="btn btn-outline-secondary sisfour-touch-target--compact">
            <i class="bx bx-arrow-back me-1"></i> Kembali
        </a>
    </div>

    <div class="card">
        <div id="pegawaiRecycleMobileList" class="d-md-none list-group list-group-flush"><div class="list-group-item sisfour-mobile-state text-muted">Memuat Recycle Bin Pegawai...</div></div>
        <div class="d-none d-md-block table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th style="width:56px">No.</th>
                        <th>Pegawai</th>
                        <th>NIK</th>
                        <th>NIP</th>
                        <th>Status</th>
                        <th>Dihapus</th>
                        <th style="width:220px">Aksi</th>
                    </tr>
                </thead>
                <tbody id="pegawaiRecycleBody"></tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
