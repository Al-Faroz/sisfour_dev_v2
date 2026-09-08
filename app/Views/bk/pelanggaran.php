<?= $this->extend('main') ?>
<?= $this->section('content') ?>
<div id="pelanggaranApp" data-base-url="<?= esc(base_url()) ?>">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Master Pelanggaran</h4>
        <button class="btn btn-primary" id="btnPelanggaranBaru">Tambah</button>
    </div>
    <div id="pelanggaranAlert" class="alert d-none"></div>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Nama</th><th>Kategori</th><th>Poin</th><th class="text-end">Aksi</th></tr></thead>
                <tbody id="pelanggaranBody">
                    <?php foreach ($rows as $row): ?>
                    <tr data-id="<?= (int) $row['id'] ?>" data-nama="<?= esc($row['nama_pelanggaran']) ?>" data-kategori="<?= esc($row['kategori']) ?>" data-poin="<?= (int) $row['poin'] ?>">
                        <td><?= esc($row['nama_pelanggaran']) ?></td><td><?= esc($row['kategori']) ?></td><td><?= (int) $row['poin'] ?></td>
                        <td class="text-end"><button class="btn btn-sm btn-outline-primary btn-edit">Edit</button> <button class="btn btn-sm btn-outline-danger btn-delete">Hapus</button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="modalPelanggaran" tabindex="-1">
        <div class="modal-dialog">
            <form class="modal-content" id="formPelanggaran">
                <div class="modal-header"><h5 class="modal-title">Master Pelanggaran</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" name="id">
                    <div class="mb-3"><label class="form-label">Nama Pelanggaran</label><input name="nama_pelanggaran" class="form-control" maxlength="150" required></div>
                    <div class="mb-3"><label class="form-label">Kategori</label><select name="kategori" class="form-select" required><option>Ringan</option><option>Sedang</option><option>Berat</option></select></div>
                    <div><label class="form-label">Poin</label><input name="poin" type="number" min="0" max="10000" class="form-control" required></div>
                </div>
                <div class="modal-footer"><button class="btn btn-primary" type="submit">Simpan</button></div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
