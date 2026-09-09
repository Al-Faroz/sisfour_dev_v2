<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<div id="prestasiApp" data-base-url="<?= esc(base_url()) ?>">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Prestasi Siswa</h4>
            <p class="text-muted mb-0">Data otomatis mengikuti scope user.</p>
        </div>

        <?php if (!empty($initial['can_manage'])): ?>
            <button class="btn btn-primary" id="btnPrestasiBaru" type="button" data-bs-toggle="modal" data-bs-target="#modalPrestasi">
                <i class="bx bx-plus me-1"></i>
                Tambah Prestasi
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
                        <label class="form-label" for="prestasiSearch">Pencarian</label>
                        <input id="prestasiSearch" class="form-control" placeholder="Cari siswa / prestasi / penyelenggara">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="prestasiTingkat">Tingkat</label>
                        <select id="prestasiTingkat" class="form-select" data-searchable-off="1">
                            <option value="">Semua tingkat</option>
                            <?php foreach (($initial['tingkat_options'] ?? []) as $t): ?>
                                <option value="<?= esc($t) ?>"><?= esc($t) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="prestasiMulai">Dari</label>
                        <input id="prestasiMulai" type="date" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="prestasiSelesai">Sampai</label>
                        <input id="prestasiSelesai" type="date" class="form-control">
                    </div>
                    <div class="col-md-1 d-grid align-self-end">
                        <button id="btnPrestasiCari" type="button" class="btn btn-outline-primary">Cari</button>
                    </div>
                </div>
            </div>
        </div>

        <div id="prestasiAlert" class="alert d-none"></div>

        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="mb-0">Riwayat Prestasi</h5>
                <a href="#" id="btnPrestasiExport" class="btn btn-sm btn-outline-primary">Export XLSX</a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Siswa</th>
                            <th>Prestasi</th>
                            <th>Tingkat</th>
                            <th>Penyelenggara</th>
                            <th>Keterangan</th>
                            <?php if (!empty($initial['can_manage'])): ?><th>Aksi</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="prestasiBody"></tbody>
                </table>
            </div>

            <div class="card-footer d-flex justify-content-between align-items-center gap-2">
                <small id="prestasiInfo" class="text-muted"></small>
                <div class="btn-group">
                    <button id="prestasiPrev" type="button" class="btn btn-sm btn-outline-secondary">Sebelumnya</button>
                    <button id="prestasiNext" type="button" class="btn btn-sm btn-outline-secondary">Berikutnya</button>
                </div>
            </div>
        </div>

        <?php if (!empty($initial['can_manage'])): ?>
            <div class="modal fade" id="modalPrestasi" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <form class="modal-content" id="formPrestasi">
                        <div class="modal-header">
                            <h5 class="modal-title" id="judulModalPrestasi">Prestasi Siswa</h5>
                            <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>

                        <div class="modal-body">
                            <input type="hidden" name="id">

                            <div class="mb-3">
                                <label class="form-label" for="prestasiSiswa">Siswa</label>
                                <select
                                    id="prestasiSiswa"
                                    name="id_siswa"
                                    class="form-select"
                                    required
                                    data-searchable-remote="<?= esc(base_url('ui/search/siswa')) ?>"
                                    data-searchable-context="prestasi"
                                    data-searchable-min-chars="2"
                                    data-search-placeholder="Ketik nama / NISN / NIK..."
                                >
                                    <option value="">Cari siswa</option>
                                </select>
                                <div class="form-text">Ketik minimal 2 karakter.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="namaPrestasi">Nama Prestasi</label>
                                <input id="namaPrestasi" name="nama_prestasi" maxlength="200" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="tingkatPrestasi">Tingkat</label>
                                <select id="tingkatPrestasi" name="tingkat" class="form-select" required data-searchable-off="1">
                                    <?php foreach (($initial['tingkat_options'] ?? []) as $t): ?>
                                        <option value="<?= esc($t) ?>"><?= esc($t) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="tanggalPrestasi">Tanggal</label>
                                <input id="tanggalPrestasi" name="tanggal" type="date" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="penyelenggaraPrestasi">Penyelenggara</label>
                                <input id="penyelenggaraPrestasi" name="penyelenggara" maxlength="200" class="form-control">
                            </div>

                            <div>
                                <label class="form-label" for="keteranganPrestasi">Keterangan</label>
                                <textarea id="keteranganPrestasi" name="keterangan" rows="3" class="form-control"></textarea>
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
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
