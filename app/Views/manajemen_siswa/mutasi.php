<?= $this->extend('main') ?>
<?= $this->section('content') ?>
<div id="mutasiSiswaApp" data-base-url="<?= esc(base_url()) ?>">
    <div class="mb-4">
        <h4 class="fw-bold mb-1">Mutasi Siswa</h4>
        <p class="text-muted mb-0">Gunakan menu ini untuk siswa yang pindah sekolah atau keluar. Pindah kelas tidak dilakukan dari menu ini.</p>
    </div>
    <div class="card mb-4"><div class="card-body">
        <form id="formFilterMutasi" class="row g-3">
            <div class="col-md-7"><label class="form-label" for="filterMutasiQ">Cari Siswa</label><input type="text" class="form-control" id="filterMutasiQ" name="q" placeholder="Nama, NISN, atau NIK"></div>
            <div class="col-md-5"><label class="form-label" for="filterMutasiKelas">Kelas</label><select class="form-select" id="filterMutasiKelas" name="id_kelas"><option value="">Semua</option><?php foreach ($kelasOptions as $kelas): ?><option value="<?= (int)$kelas['id'] ?>"><?= esc($kelas['nama_kelas']) ?></option><?php endforeach; ?></select></div>
            <div class="col-12 d-flex gap-2"><button type="submit" class="btn btn-primary">Terapkan</button><button type="button" class="btn btn-outline-secondary" id="btnResetMutasi">Reset</button></div>
        </form>
    </div></div>
    <div class="card"><div class="table-responsive">
        <table class="table table-hover align-middle" id="tableMutasiSiswa">
            <thead><tr><th style="width:56px;">No.</th><th>Nama</th><th>NISN</th><th>Kelas</th><th>JK</th><th style="width:140px;">Aksi</th></tr></thead>
            <tbody></tbody>
        </table>
    </div></div>
    <div class="modal fade" id="modalMutasi" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><div class="modal-content">
        <form id="formMutasi">
            <?= csrf_field() ?><input type="hidden" id="idSiswaMutasi">
            <div class="modal-header"><h5 class="modal-title">Proses Mutasi Siswa</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Siswa</label><input type="text" class="form-control" id="namaSiswaMutasi" readonly></div>
                <div class="mb-3"><label class="form-label" for="statusMutasi">Status</label><select class="form-select" id="statusMutasi" name="status" required><option value="">Pilih</option><option value="Pindah">Pindah Sekolah</option><option value="Keluar">Keluar</option></select></div>
                <div><label class="form-label" for="keteranganMutasi">Keterangan</label><textarea class="form-control" id="keteranganMutasi" name="keterangan" rows="4" required></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-danger">Proses Mutasi</button></div>
        </form>
    </div></div></div>
</div>
<?= $this->endSection() ?>
