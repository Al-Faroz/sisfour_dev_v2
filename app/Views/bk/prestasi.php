<?= $this->extend('main') ?>
<?= $this->section('content') ?>
<div id="prestasiApp" data-base-url="<?= esc(base_url()) ?>">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div><h4 class="fw-bold mb-1">Prestasi Siswa</h4><p class="text-muted mb-0">Data otomatis mengikuti scope user.</p></div>
        <?php if (!empty($initial['can_manage'])): ?><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPrestasi">Tambah Prestasi</button><?php endif; ?>
    </div>

    <?php if (empty($initial['success'])): ?>
        <div class="alert alert-danger"><?= esc($initial['message'] ?? 'Data tidak dapat dibuka.') ?></div>
    <?php else: ?>
        <div class="card mb-4"><div class="card-body"><div class="row g-3">
            <div class="col-md-4"><input id="prestasiSearch" class="form-control" placeholder="Cari siswa / prestasi / penyelenggara"></div>
            <div class="col-md-3"><select id="prestasiTingkat" class="form-select"><option value="">Semua tingkat</option><?php foreach (($initial['tingkat_options'] ?? []) as $t): ?><option><?= esc($t) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-2"><input id="prestasiMulai" type="date" class="form-control"></div>
            <div class="col-md-2"><input id="prestasiSelesai" type="date" class="form-control"></div>
            <div class="col-md-1 d-grid"><button id="btnPrestasiCari" class="btn btn-outline-primary">Cari</button></div>
        </div></div></div>

        <div id="prestasiAlert" class="alert d-none"></div>
        <div class="card">
            <div class="card-header d-flex justify-content-between"><h5 class="mb-0">Riwayat Prestasi</h5><a href="#" id="btnPrestasiExport" class="btn btn-sm btn-outline-primary">Export XLSX</a></div>
            <div class="table-responsive"><table class="table table-hover align-middle">
                <thead><tr><th>Tanggal</th><th>Siswa</th><th>Prestasi</th><th>Tingkat</th><th>Penyelenggara</th><th>Keterangan</th><?php if (!empty($initial['can_manage'])): ?><th>Aksi</th><?php endif; ?></tr></thead>
                <tbody id="prestasiBody"></tbody>
            </table></div>
            <div class="card-footer d-flex justify-content-between"><small id="prestasiInfo" class="text-muted"></small><div class="btn-group"><button id="prestasiPrev" class="btn btn-sm btn-outline-secondary">Sebelumnya</button><button id="prestasiNext" class="btn btn-sm btn-outline-secondary">Berikutnya</button></div></div>
        </div>

        <?php if (!empty($initial['can_manage'])): ?>
        <div class="modal fade" id="modalPrestasi" tabindex="-1">
            <div class="modal-dialog"><form class="modal-content" id="formPrestasi">
                <div class="modal-header"><h5 class="modal-title">Prestasi Siswa</h5><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" name="id">
                    <div class="mb-3">
                        <label class="form-label">Siswa</label>
                        <select name="id_siswa" class="form-select" required>
                            <option value="">Pilih siswa</option>
                            <?php foreach (($initial['student_options'] ?? []) as $siswa): ?>
                                <option value="<?= (int) $siswa['id'] ?>"><?= esc($siswa['nisn'] . ' — ' . $siswa['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Nama Prestasi</label><input name="nama_prestasi" maxlength="200" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Tingkat</label><select name="tingkat" class="form-select" required><?php foreach (($initial['tingkat_options'] ?? []) as $t): ?><option><?= esc($t) ?></option><?php endforeach; ?></select></div>
                    <div class="mb-3"><label class="form-label">Tanggal</label><input name="tanggal" type="date" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Penyelenggara</label><input name="penyelenggara" maxlength="200" class="form-control"></div>
                    <div><label class="form-label">Keterangan</label><textarea name="keterangan" rows="3" class="form-control"></textarea></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">Simpan</button></div>
            </form></div>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
