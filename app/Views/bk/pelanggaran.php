<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<div id="pelanggaranApp" data-base-url="<?= esc(base_url()) ?>">
    <div class="sisfour-page-header">
        <div class="sisfour-page-header__copy">
            <h4 class="fw-bold mb-1">Master Pelanggaran</h4>
            <p class="text-muted mb-0">
                Kelola referensi jenis dan kategori pelanggaran untuk Catatan Pelanggaran Siswa.
            </p>
        </div>

        <div class="sisfour-page-actions">
            <button type="button" class="btn btn-outline-success" id="btnExportPelanggaran">
                <i class="bx bx-export me-1"></i> Export
            </button>
            <button type="button" class="btn btn-primary" id="btnPelanggaranBaru">
                <i class="bx bx-plus me-1"></i> Tambah Pelanggaran
            </button>
        </div>
    </div>

    <div id="pelanggaranAlert" class="alert d-none" role="alert"></div>

    <div class="card sisfour-table-card">
        <div class="card-header">
            <h5 class="mb-0">Daftar Pelanggaran</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tablePelanggaran">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Kategori</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody id="pelanggaranBody">
                    <?php if (empty($rows)): ?>
                        <tr class="sisfour-empty-row">
                            <td colspan="3" class="text-muted">Belum ada Master Pelanggaran.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <tr
                                data-id="<?= (int) $row['id'] ?>"
                                data-nama="<?= esc($row['nama_pelanggaran'], 'attr') ?>"
                                data-kategori="<?= esc($row['kategori'], 'attr') ?>"
                            >
                                <td class="fw-semibold"><?= esc($row['nama_pelanggaran']) ?></td>
                                <td><span class="badge bg-label-secondary"><?= esc($row['kategori']) ?></span></td>
                                <td class="text-end">
                                    <div class="sisfour-row-actions justify-content-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-edit" title="Edit" aria-label="Edit pelanggaran">
                                            <i class="bx bx-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete" title="Hapus" aria-label="Hapus pelanggaran">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="modalPelanggaran" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" id="formPelanggaran">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalPelanggaranTitle">Tambah Pelanggaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id">
                    <div class="mb-3">
                        <label class="form-label" for="namaPelanggaran">Nama Pelanggaran</label>
                        <input id="namaPelanggaran" name="nama_pelanggaran" class="form-control" maxlength="150" required>
                    </div>
                    <div>
                        <label class="form-label" for="kategoriPelanggaran">Kategori</label>
                        <select id="kategoriPelanggaran" name="kategori" class="form-select" required data-searchable-off="1">
                            <option value="Ringan">Ringan</option>
                            <option value="Sedang">Sedang</option>
                            <option value="Berat">Berat</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer sisfour-modal-actions">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-primary" type="submit">
                        <span class="spinner-border spinner-border-sm d-none me-1" aria-hidden="true"></span>
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
