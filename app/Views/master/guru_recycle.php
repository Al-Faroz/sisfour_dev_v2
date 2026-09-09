<?= $this->extend('main') ?>

<?= $this->section('content') ?>
<div id="guruRecycleApp" data-base-url="<?= esc(base_url()) ?>">
    <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Recycle Bin Guru</h4>
            <p class="text-muted mb-0">Pulihkan data Guru atau hapus permanen bila sudah tidak direferensikan data lain.</p>
        </div>
        <a href="<?= base_url('master/guru') ?>" class="btn btn-outline-secondary">
            <i class="bx bx-arrow-back me-1"></i> Kembali
        </a>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th style="width:56px">No.</th>
                        <th>Guru</th>
                        <th>NIK</th>
                        <th>NIP</th>
                        <th>Status</th>
                        <th>Dihapus</th>
                        <th style="width:220px">Aksi</th>
                    </tr>
                </thead>
                <tbody id="guruRecycleBody"></tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
