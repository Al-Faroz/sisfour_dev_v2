<?= $this->extend('main') ?>

<?= $this->section('content') ?>

<div
    id="pegawaiRecycleApp"
    data-base-url="<?= esc(base_url()) ?>"
>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Recycle Bin Pegawai</h4>
            <p class="text-muted mb-0">
                Pulihkan data Pegawai atau hapus permanen.
            </p>
        </div>

        <a
            href="<?= base_url('master/pegawai') ?>"
            class="btn btn-outline-primary"
        >
            <i class="bx bx-arrow-back me-1"></i> Kembali
        </a>
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table table-hover" id="tablePegawaiRecycle">
                <thead>
                    <tr>
                        <th style="width: 56px;">No.</th>
                        <th>Nama Pegawai</th>
                        <th>NIP</th>
                        <th>JK</th>
                        <th>Jabatan</th>
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
