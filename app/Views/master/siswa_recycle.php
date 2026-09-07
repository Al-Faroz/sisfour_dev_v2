<?= $this->extend('main') ?>

<?= $this->section('content') ?>

<div id="siswaRecycleApp" data-base-url="<?= esc(base_url()) ?>">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Recycle Bin Siswa</h4>
            <p class="text-muted mb-0">
                Pulihkan Siswa atau hapus permanen jika tidak lagi direferensikan data lain.
            </p>
        </div>

        <a href="<?= base_url('master/siswa') ?>" class="btn btn-outline-primary">
            <i class="bx bx-arrow-back me-1"></i> Kembali
        </a>
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table table-hover" id="tableSiswaRecycle">
                <thead>
                    <tr>
                        <th style="width: 56px;">No.</th>
                        <th>Nama Siswa</th>
                        <th>NIK</th>
                        <th>NISN</th>
                        <th>Status</th>
                        <th>Dihapus</th>
                        <th style="width: 180px;">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
