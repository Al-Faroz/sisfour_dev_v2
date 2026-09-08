<?= $this->extend('main') ?>
<?= $this->section('content') ?>
<div id="bkKasusApp" data-base-url="<?= esc(base_url()) ?>">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div><h4 class="fw-bold mb-1">Catatan Kasus</h4><p class="text-muted mb-0">Data otomatis dibatasi sesuai scope user.</p></div>
        <?php if (!empty($initial['can_manage'])): ?><button class="btn btn-primary" id="btnKasusBaru">Tambah Kasus</button><?php endif; ?>
    </div>

    <?php if (empty($initial['success'])): ?>
        <div class="alert alert-danger"><?= esc($initial['message'] ?? 'Data tidak dapat dibuka.') ?></div>
    <?php else: ?>
        <div class="card mb-4"><div class="card-body"><div class="row g-3">
            <div class="col-md-4"><input id="kasusSearch" class="form-control" placeholder="Cari siswa / NISN / pelanggaran"></div>
            <div class="col-md-2"><select id="kasusKategori" class="form-select"><option value="">Semua kategori</option><option>Ringan</option><option>Sedang</option><option>Berat</option></select></div>
            <div class="col-md-2"><input id="kasusMulai" type="date" class="form-control"></div>
            <div class="col-md-2"><input id="kasusSelesai" type="date" class="form-control"></div>
            <div class="col-md-2 d-grid"><button id="btnKasusCari" class="btn btn-outline-primary">Tampilkan</button></div>
        </div></div></div>

        <div id="kasusAlert" class="alert d-none"></div>
        <div class="card">
            <div class="card-header d-flex justify-content-between"><h5 class="mb-0">Riwayat Kasus</h5><?php if (!empty($initial['can_manage'])): ?><a class="btn btn-sm btn-outline-primary" id="btnKasusExport" href="#">Export XLSX</a><?php endif; ?></div>
            <div class="table-responsive"><table class="table table-hover align-middle">
                <thead><tr><th>Tanggal</th><th>Siswa</th><th>Pelanggaran</th><th>Kategori</th><th>Poin</th><th>Keterangan</th><?php if (!empty($initial['can_manage'])): ?><th class="text-end">Aksi</th><?php endif; ?></tr></thead>
                <tbody id="kasusBody"></tbody>
            </table></div>
            <div class="card-footer d-flex justify-content-between"><small id="kasusInfo" class="text-muted"></small><div class="btn-group"><button id="kasusPrev" class="btn btn-sm btn-outline-secondary">Sebelumnya</button><button id="kasusNext" class="btn btn-sm btn-outline-secondary">Berikutnya</button></div></div>
        </div>

        <?php if (($initial['scope'] ?? '') !== 'DIRI_SENDIRI'): ?><div class="mt-3"><a href="<?= esc(base_url('bk/kasus/top')) ?>" class="btn btn-outline-secondary">Lihat Top 20 Poin</a></div><?php endif; ?>

        <?php if (!empty($initial['can_manage'])): ?>
        <div class="modal fade" id="modalKasus" tabindex="-1"><div class="modal-dialog"><form class="modal-content" id="formKasus">
            <div class="modal-header"><h5 class="modal-title" id="judulModalKasus">Catatan Kasus</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="id">
                <div class="mb-3"><label class="form-label">Siswa</label><select name="id_siswa" class="form-select" required><option value="">Pilih siswa</option><?php foreach (($initial['student_options'] ?? []) as $siswa): ?><option value="<?= (int) $siswa['id'] ?>"><?= esc($siswa['nisn'] . ' — ' . $siswa['nama']) ?></option><?php endforeach; ?></select></div>
                <div class="mb-3"><label class="form-label">Pelanggaran</label><select name="id_pelanggaran" class="form-select" required><option value="">Pilih</option><?php foreach (($initial['pelanggaran'] ?? []) as $p): ?><option value="<?= (int) $p['id'] ?>"><?= esc($p['nama_pelanggaran'] . ' — ' . $p['kategori'] . ' (' . $p['poin'] . ')') ?></option><?php endforeach; ?></select></div>
                <div class="mb-3"><label class="form-label">Tanggal</label><input name="tanggal" type="date" class="form-control" required></div>
                <div><label class="form-label">Keterangan</label><textarea name="keterangan" class="form-control" rows="3"></textarea></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">Simpan</button></div>
        </form></div></div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
