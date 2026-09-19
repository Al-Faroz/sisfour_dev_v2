<?= $this->extend('main') ?>

<?= $this->section('content') ?>

<?php
$guruIdentifier = static function (array $guru): string {
    $nip = trim((string) ($guru['nip'] ?? ''));
    if ($nip !== '') return $nip;
    return trim((string) ($guru['nik'] ?? ''));
};
?>

<div
    id="masterJadwalApp"
    data-base-url="<?= esc(base_url()) ?>"
    data-can-manage="<?= !empty($canManage) ? '1' : '0' ?>"
>
    <div class="sisfour-page-header">
        <div class="sisfour-page-header__copy">
            <h4 class="fw-bold mb-1">Master Jadwal Guru</h4>
            <p class="text-muted mb-0">Jadwal dikelola melalui import Excel dan tetap divalidasi terhadap bentrok serta topology sesi.</p>
        </div>

        <?php if (!empty($canManage)): ?>
            <div class="sisfour-page-actions">
                <a href="<?= base_url('master/jadwal/template') ?>" class="btn btn-outline-primary">
                    <i class="bx bx-download me-1"></i> Template
                </a>
                <button type="button" class="btn btn-outline-primary" id="btnImportJadwal">
                    <i class="bx bx-import me-1"></i> Import Jadwal
                </button>
                <a href="#" class="btn btn-outline-success sisfour-touch-target--compact" id="btnExportJadwal">
                    <i class="bx bx-export me-1"></i> Export
                </a>
            </div>
        <?php endif; ?>
    </div>

    <div class="alert alert-info sisfour-compact-note">
        <i class="bx bx-info-circle me-1"></i>
        Import menggunakan <strong>NIP atau NIK Guru</strong> sebagai identitas dan bersifat atomic.
        Import baru hanya menonaktifkan jadwal aktif lama pada Tahun Ajaran/Semester yang dipilih tanpa menghapus histori.
    </div>

    <div class="card sisfour-filter-card mb-4">
        <div class="card-body">
            <form id="formFilterJadwal" class="row g-3 align-items-end">
                <div class="col-12 col-md-4 col-xl">
                    <label class="form-label" for="filterGuru">Guru</label>
                    <select class="form-select" id="filterGuru" name="id_guru">
                        <option value="">Semua</option>
                        <?php foreach (($options['guru'] ?? []) as $guru): ?>
                            <?php $identifier = $guruIdentifier($guru); ?>
                            <option value="<?= (int) $guru['id'] ?>">
                                <?= esc($guru['nama'] . ($identifier !== '' ? ' (' . $identifier . ')' : '')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-md-4 col-xl">
                    <label class="form-label" for="filterKelas">Kelas</label>
                    <select class="form-select" id="filterKelas" name="id_kelas">
                        <option value="">Semua</option>
                        <?php foreach (($options['kelas'] ?? []) as $kelas): ?>
                            <option value="<?= (int) $kelas['id'] ?>">
                                <?= esc($kelas['nama_tahun'] . ' - ' . $kelas['semester'] . ' · ' . $kelas['nama_kelas']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-md-4 col-xl">
                    <label class="form-label" for="filterTahun">Tahun Ajaran</label>
                    <select class="form-select" id="filterTahun" name="id_tahun">
                        <option value="">Semua</option>
                        <?php foreach (($options['tahun'] ?? []) as $tahun): ?>
                            <option value="<?= (int) $tahun['id'] ?>">
                                <?= esc($tahun['nama_tahun'] . ' - ' . $tahun['semester'] . ((int) $tahun['status_aktif'] === 1 ? ' (Aktif)' : '')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-6 col-md-4 col-xl">
                    <label class="form-label" for="filterHari">Hari</label>
                    <select class="form-select" id="filterHari" name="hari" data-searchable-off="1">
                        <option value="">Semua</option>
                        <?php foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'] as $hari): ?>
                            <option value="<?= esc($hari) ?>"><?= esc($hari) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-6 col-md-4 col-xl">
                    <label class="form-label" for="filterStatus">Status</label>
                    <select class="form-select" id="filterStatus" name="status_jadwal" data-searchable-off="1">
                        <option value="">Semua</option>
                        <option value="Aktif">Aktif</option>
                        <option value="Nonaktif">Nonaktif</option>
                    </select>
                </div>

                <div class="col-12 sisfour-filter-actions">
                    <button type="button" class="btn btn-outline-secondary sisfour-touch-target--compact" id="btnResetFilter">Reset</button>
                    <button type="submit" class="btn btn-primary sisfour-touch-target">
                        <i class="bx bx-filter-alt me-1"></i> Terapkan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card sisfour-table-card">
        <div class="card-header d-flex justify-content-between align-items-start flex-wrap gap-2">
            <h5 class="mb-0">Daftar Jadwal Guru</h5>
            <?php if (empty($canManage)): ?><span class="badge bg-label-info">Readonly</span><?php endif; ?>
        </div>

        <div id="jadwalMobileList" class="d-md-none list-group list-group-flush">
            <div class="list-group-item sisfour-mobile-state text-muted">Memuat jadwal...</div>
        </div>
        <div class="d-none d-md-block table-responsive">
            <table class="table table-hover align-middle mb-0" id="tableJadwal">
                <thead>
                    <tr>
                        <th style="width:56px;">No.</th>
                        <th>Guru</th>
                        <th>Kelas</th>
                        <th>Mata Pelajaran</th>
                        <th>Hari</th>
                        <th>Jam</th>
                        <th>Sesi</th>
                        <th>Tahun Ajaran</th>
                        <th>Status</th>
                        <?php if (!empty($canManage)): ?><th style="width:80px;">Aksi</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <?php if (!empty($canManage)): ?>
        <div class="modal fade" id="modalImportJadwal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-sm-down">
                <form id="formImportJadwal" class="modal-content">
                    <?= csrf_field() ?>
                    <div class="modal-header py-2">
                        <h5 class="modal-title">Import Jadwal Guru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>

                    <div class="modal-body overflow-auto py-3">
                        <div class="alert alert-warning sisfour-compact-note">
                            Import bersifat <strong>atomic</strong>. Satu baris error atau bentrok akan membatalkan seluruh import.
                        </div>
                        <div class="alert alert-light border sisfour-compact-note">
                            Kolom <strong>IDENTITAS_GURU</strong> menerima NIP 18 digit atau NIK 16 digit dan wajib disimpan sebagai <strong>Text</strong> di Excel.
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="importTahun">Tahun Ajaran / Semester Aktif</label>
                            <select class="form-select" id="importTahun" name="id_tahun" required>
                                <option value="">Pilih</option>
                                <?php foreach (($options['tahun'] ?? []) as $tahun): ?>
                                    <?php if ((int) $tahun['status_aktif'] === 1): ?>
                                        <option value="<?= (int) $tahun['id'] ?>">
                                            <?= esc($tahun['nama_tahun'] . ' - ' . $tahun['semester'] . ' (Aktif)') ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="form-label" for="fileImportJadwal">File Excel</label>
                            <input type="file" class="form-control" id="fileImportJadwal" name="file" accept=".xlsx,.xls" required>
                        </div>
                    </div>

                    <div class="modal-footer py-2 sisfour-modal-actions">
                        <button type="button" class="btn btn-outline-secondary sisfour-touch-target--compact" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary sisfour-touch-target" id="btnProsesImportJadwal">
                            <span class="spinner-border spinner-border-sm d-none me-1" aria-hidden="true"></span>
                            Import
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>