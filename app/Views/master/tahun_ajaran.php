<?= $this->extend('main') ?>

<?= $this->section('content') ?>

<div id="masterTahunAjaranApp" data-base-url="<?= esc(base_url()) ?>">
    <div class="sisfour-page-header">
        <div class="sisfour-page-header__copy">
            <h4 class="fw-bold mb-1">Master Tahun Ajaran</h4>
            <p class="text-muted mb-0">
                Kelola tahun ajaran dan semester aktif yang digunakan sistem.
            </p>
        </div>

        <div class="sisfour-page-actions">
            <a href="<?= base_url('master/tahun/recycle') ?>" class="btn btn-outline-secondary">
                <i class="bx bx-trash me-1"></i> Recycle Bin
            </a>
            <button type="button" class="btn btn-primary" id="btnTambahTahun">
                <i class="bx bx-plus me-1"></i> Tambah Tahun Ajaran
            </button>
        </div>
    </div>

    <div class="alert alert-info sisfour-compact-note">
        <i class="bx bx-info-circle me-1"></i>
        Hanya <strong>satu</strong> tahun ajaran/semester yang boleh aktif.
        Transisi Ganjil ke Genap menggunakan tombol <strong>Siapkan Genap</strong> agar Kelas, Anggota, Wali, Jadwal, dan histori diproses secara atomic.
    </div>

    <div class="card sisfour-table-card">
        <div class="card-header">
            <h5 class="mb-0">Daftar Tahun Ajaran</h5>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tableTahunAjaran">
                <thead>
                    <tr>
                        <th style="width: 56px;">No.</th>
                        <th>Tahun Ajaran</th>
                        <th>Semester</th>
                        <th>Status</th>
                        <th>Kelas</th>
                        <th>Anggota</th>
                        <th>Jadwal</th>
                        <th style="min-width: 180px;">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="modalTahunAjaran" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <form id="formTahunAjaran" class="modal-content">
                <?= csrf_field() ?>

                <div class="modal-header py-2">
                    <h5 class="modal-title" id="modalTahunAjaranTitle">Tambah Tahun Ajaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>

                <div class="modal-body overflow-auto py-3">
                    <input type="hidden" id="tahunAjaranId">

                    <div class="mb-3">
                        <label class="form-label" for="nama_tahun">Tahun Ajaran</label>
                        <input
                            type="text"
                            class="form-control"
                            id="nama_tahun"
                            name="nama_tahun"
                            maxlength="20"
                            placeholder="2026/2027"
                            required
                        >
                        <div class="form-text">Format YYYY/YYYY, contoh 2026/2027.</div>
                    </div>

                    <div>
                        <label class="form-label" for="semester">Semester</label>
                        <select class="form-select" id="semester" name="semester" required data-searchable-off="1">
                            <option value="">Pilih</option>
                            <option value="Ganjil">Ganjil</option>
                            <option value="Genap">Genap</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer py-2 sisfour-modal-actions">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSimpanTahun">
                        <span class="spinner-border spinner-border-sm d-none me-1" aria-hidden="true"></span>
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>