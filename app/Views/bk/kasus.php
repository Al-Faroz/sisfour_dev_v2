<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<div
    id="bkKasusApp"
    data-base-url="<?= esc(base_url()) ?>"
    data-can-manage="<?= !empty($initial['can_manage']) ? '1' : '0' ?>"
>
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Catatan Kasus</h4>
            <p class="text-muted mb-0">
                Data otomatis dibatasi sesuai scope user. Tindak lanjut dicatat sebagai histori terpisah.
            </p>
        </div>

        <?php if (!empty($initial['can_manage'])): ?>
            <button class="btn btn-primary" id="btnKasusBaru" type="button">
                <i class="bx bx-plus me-1"></i>
                Tambah Kasus
            </button>
        <?php endif; ?>
    </div>

    <?php if (empty($initial['success'])): ?>
        <div class="alert alert-danger">
            <?= esc($initial['message'] ?? 'Data tidak dapat dibuka.') ?>
        </div>
    <?php else: ?>
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="kasusSearch">Pencarian</label>
                        <input
                            id="kasusSearch"
                            class="form-control"
                            placeholder="Cari siswa / NISN / pelanggaran"
                        >
                    </div>

                    <div class="col-md-2">
                        <label class="form-label" for="kasusKategori">Kategori</label>
                        <select id="kasusKategori" class="form-select" data-searchable-off="1">
                            <option value="">Semua kategori</option>
                            <option value="Ringan">Ringan</option>
                            <option value="Sedang">Sedang</option>
                            <option value="Berat">Berat</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label" for="kasusMulai">Dari</label>
                        <input id="kasusMulai" type="date" class="form-control">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label" for="kasusSelesai">Sampai</label>
                        <input id="kasusSelesai" type="date" class="form-control">
                    </div>

                    <div class="col-md-2 d-grid align-self-end">
                        <button id="btnKasusCari" type="button" class="btn btn-outline-primary">
                            Tampilkan
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="kasusAlert" class="alert d-none"></div>

        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="mb-0">Riwayat Kasus</h5>

                <?php if (!empty($initial['can_manage'])): ?>
                    <a class="btn btn-sm btn-outline-primary" id="btnKasusExport" href="#">
                        Export XLSX
                    </a>
                <?php endif; ?>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Siswa</th>
                            <th>Pelanggaran</th>
                            <th>Kategori</th>
                            <th>Poin</th>
                            <th>Keterangan</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="kasusBody"></tbody>
                </table>
            </div>

            <div class="card-footer d-flex justify-content-between align-items-center gap-2">
                <small id="kasusInfo" class="text-muted"></small>
                <div class="btn-group">
                    <button id="kasusPrev" type="button" class="btn btn-sm btn-outline-secondary">
                        Sebelumnya
                    </button>
                    <button id="kasusNext" type="button" class="btn btn-sm btn-outline-secondary">
                        Berikutnya
                    </button>
                </div>
            </div>
        </div>

        <?php if (($initial['scope'] ?? '') !== 'DIRI_SENDIRI'): ?>
            <div class="mt-3">
                <a href="<?= esc(base_url('bk/kasus/top')) ?>" class="btn btn-outline-secondary">
                    Lihat Top 20 Poin
                </a>
            </div>
        <?php endif; ?>

        <?php if (!empty($initial['can_manage'])): ?>
            <div class="modal fade" id="modalKasus" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <form class="modal-content" id="formKasus">
                        <div class="modal-header">
                            <h5 class="modal-title" id="judulModalKasus">Catatan Kasus</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>

                        <div class="modal-body">
                            <input type="hidden" name="id">

                            <div class="mb-3">
                                <label class="form-label" for="kasusSiswa">Siswa</label>
                                <select
                                    id="kasusSiswa"
                                    name="id_siswa"
                                    class="form-select"
                                    required
                                    data-searchable-remote="<?= esc(base_url('ui/search/siswa')) ?>"
                                    data-searchable-context="bk_kasus"
                                    data-searchable-min-chars="2"
                                    data-search-placeholder="Ketik nama / NISN / NIK..."
                                >
                                    <option value="">Cari siswa</option>
                                </select>
                                <div class="form-text">Ketik minimal 2 karakter.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="kasusPelanggaran">Pelanggaran</label>
                                <select
                                    id="kasusPelanggaran"
                                    name="id_pelanggaran"
                                    class="form-select"
                                    required
                                    data-searchable-select
                                    data-search-placeholder="Cari pelanggaran..."
                                >
                                    <option value="">Pilih pelanggaran</option>
                                    <?php foreach (($initial['pelanggaran'] ?? []) as $p): ?>
                                        <option value="<?= (int) $p['id'] ?>">
                                            <?= esc(
                                                $p['nama_pelanggaran']
                                                . ' — '
                                                . $p['kategori']
                                                . ' ('
                                                . $p['poin']
                                                . ' poin)'
                                            ) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="kasusTanggal">Tanggal Kejadian</label>
                                <input id="kasusTanggal" name="tanggal" type="date" class="form-control" required>
                            </div>

                            <div>
                                <label class="form-label" for="kasusKeterangan">Keterangan</label>
                                <textarea id="kasusKeterangan" name="keterangan" class="form-control" rows="3"></textarea>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <div class="modal fade" id="modalDetailKasus" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Detail Kasus & Tindak Lanjut</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>

                    <div class="modal-body">
                        <div id="detailKasusLoading" class="text-center text-muted py-4">
                            Memuat data...
                        </div>

                        <div id="detailKasusContent" class="d-none">
                            <div class="card bg-label-secondary mb-4">
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <small class="text-muted d-block">Siswa</small>
                                            <strong id="detailKasusSiswa"></strong>
                                        </div>
                                        <div class="col-md-3">
                                            <small class="text-muted d-block">Tanggal</small>
                                            <strong id="detailKasusTanggal"></strong>
                                        </div>
                                        <div class="col-md-3">
                                            <small class="text-muted d-block">Kategori / Poin</small>
                                            <strong id="detailKasusKategori"></strong>
                                        </div>
                                        <div class="col-12">
                                            <small class="text-muted d-block">Pelanggaran</small>
                                            <strong id="detailKasusPelanggaran"></strong>
                                        </div>
                                        <div class="col-12">
                                            <small class="text-muted d-block">Keterangan</small>
                                            <span id="detailKasusKeterangan"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if (!empty($initial['can_manage'])): ?>
                                <form id="formTindakLanjut" class="card mb-4">
                                    <div class="card-header">
                                        <h6 class="mb-0" id="judulTindakLanjut">Tambah Tindak Lanjut</h6>
                                    </div>
                                    <div class="card-body">
                                        <input type="hidden" name="id">
                                        <input type="hidden" name="id_kasus">

                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="form-label" for="tindakTanggal">Tanggal</label>
                                                <input id="tindakTanggal" name="tanggal" type="date" class="form-control" required>
                                            </div>
                                            <div class="col-md-8">
                                                <label class="form-label" for="tindakJenis">Tindak Lanjut</label>
                                                <select id="tindakJenis" name="tindak_lanjut" class="form-select" required data-searchable-off="1">
                                                    <option value="">Pilih tindak lanjut</option>
                                                </select>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label" for="tindakKeterangan">Keterangan</label>
                                                <textarea id="tindakKeterangan" name="keterangan" class="form-control" rows="3"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer d-flex justify-content-end gap-2">
                                        <button type="button" id="btnBatalEditTindak" class="btn btn-outline-secondary d-none">Batal Edit</button>
                                        <button type="submit" class="btn btn-primary">Simpan Tindak Lanjut</button>
                                    </div>
                                </form>
                            <?php endif; ?>

                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0">Riwayat Tindak Lanjut</h6>
                                <span class="badge bg-label-primary" id="jumlahTindakLanjut">0</span>
                            </div>

                            <div id="timelineTindakLanjut" class="vstack gap-3"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
