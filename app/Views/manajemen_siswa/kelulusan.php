<?= $this->extend('main') ?>
<?= $this->section('content') ?>
<div id="kelulusanSiswaApp" data-base-url="<?= esc(base_url()) ?>">
    <div class="mb-4"><h4 class="fw-bold mb-1">Kelulusan Siswa</h4><p class="text-muted mb-0">Proses kelulusan siswa tingkat 9 secara terkontrol per kelas.</p></div>
    <div class="alert alert-warning"><i class="bx bx-error me-1"></i> Kelulusan mengubah status siswa menjadi <strong>Lulus</strong>, menutup histori aktif, dan menonaktifkan kartu pelajar sesuai business rule.</div>
    <div class="card">
        <div class="card-header"><h5 class="mb-0">Kelas Tingkat 9</h5></div>
        <div class="table-responsive"><table class="table table-hover align-middle">
            <thead><tr><th>Kelas</th><th>Jumlah Siswa</th><th style="width:160px;">Aksi</th></tr></thead>
            <tbody>
                <?php foreach ($sourceClasses as $kelas): ?>
                    <tr><td class="fw-semibold"><?= esc($kelas['nama_kelas']) ?></td><td><?= (int)$kelas['jumlah_siswa'] ?> siswa</td><td><button type="button" class="btn btn-sm btn-danger btn-proses-lulus" data-id="<?= (int)$kelas['id'] ?>" data-nama="<?= esc($kelas['nama_kelas']) ?>">Proses</button></td></tr>
                <?php endforeach; ?>
                <?php if ($sourceClasses === []): ?><tr><td colspan="3" class="text-center text-muted py-4">Tidak ada kelas tingkat 9 pada tahun ajaran aktif.</td></tr><?php endif; ?>
            </tbody>
        </table></div>
    </div>
    <div class="modal fade" id="modalKelulusan" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
        <form id="formKelulusan">
            <?= csrf_field() ?><input type="hidden" id="idKelasLulus">
            <div class="modal-header"><div><h5 class="modal-title">Proses Kelulusan</h5><div class="small text-muted" id="labelKelasLulus"></div></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body">
                <div class="d-flex justify-content-between mb-2"><strong id="jumlahLulusDipilih">0 siswa dipilih</strong><div class="d-flex gap-2"><button type="button" class="btn btn-sm btn-outline-primary" id="btnPilihSemuaLulus">Pilih Semua</button><button type="button" class="btn btn-sm btn-outline-secondary" id="btnKosongkanLulus">Kosongkan</button></div></div>
                <div class="table-responsive border rounded"><table class="table table-hover mb-0"><thead><tr><th style="width:50px;"></th><th>Nama</th><th>NISN</th><th>JK</th></tr></thead><tbody id="tbodyLulus"></tbody></table></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-danger">Proses Kelulusan</button></div>
        </form>
    </div></div></div>
</div>
<?= $this->endSection() ?>
