<?= $this->extend('main') ?>

<?= $this->section('content') ?>

<div
    id="mappingWaliRecycleApp"
    data-base-url="<?= esc(base_url()) ?>"
>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">
                Histori / Recycle Bin Wali Kelas
            </h4>
            <p class="text-muted mb-0">
                Mapping nonaktif tetap disimpan sebagai histori.
            </p>
        </div>

        <a
            href="<?= base_url('master/wali-kelas') ?>"
            class="btn btn-outline-primary"
        >
            <i class="bx bx-arrow-back me-1"></i>
            Kembali
        </a>
    </div>

    <div class="alert alert-info">
        Restore langsung hanya dapat dilakukan jika guru dan kelas historis
        sama-sama belum mempunyai mapping aktif. Untuk memindahkan guru lama
        ke kelas baru, gunakan tombol <strong>Assign Wali</strong> pada halaman utama.
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table
                class="table table-hover align-middle"
                id="tableMappingWaliRecycle"
            >
                <thead>
                    <tr>
                        <th style="width: 56px;">No.</th>
                        <th>Guru</th>
                        <th>Kelas Historis</th>
                        <th>Tahun Ajaran</th>
                        <th>Dinonaktifkan</th>
                        <th style="width: 190px;">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
