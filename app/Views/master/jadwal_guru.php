<?= $this->extend('main') ?>

<?= $this->section('content') ?>

<?php
$guruIdentifier = static function (array $guru): string {
    $nip = trim((string) ($guru['nip'] ?? ''));

    if ($nip !== '') {
        return $nip;
    }

    return trim((string) ($guru['nik'] ?? ''));
};
?>

<div
    id="masterJadwalApp"
    data-base-url="<?= esc(base_url()) ?>"
    data-can-manage="<?= !empty($canManage) ? '1' : '0' ?>"
>
    <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Master Jadwal Guru</h4>
            <p class="text-muted mb-0">
                Jadwal hanya diinput melalui import Excel.
            </p>
        </div>

        <?php if (!empty($canManage)): ?>
            <div class="d-flex flex-wrap gap-2">
                <a
                    href="<?= base_url('master/jadwal/template') ?>"
                    class="btn btn-outline-primary"
                >
                    <i class="bx bx-download me-1"></i>
                    Template
                </a>

                <button
                    type="button"
                    class="btn btn-primary"
                    id="btnImportJadwal"
                >
                    <i class="bx bx-import me-1"></i>
                    Import Jadwal
                </button>

                <a
                    href="#"
                    class="btn btn-outline-success"
                    id="btnExportJadwal"
                >
                    <i class="bx bx-export me-1"></i>
                    Export
                </a>
            </div>
        <?php endif; ?>
    </div>

    <div class="alert alert-info">
        <i class="bx bx-info-circle me-1"></i>
        Import menggunakan <strong>NIP atau NIK Guru</strong> sebagai identitas.
        Import baru hanya menonaktifkan jadwal aktif lama pada
        <strong>Tahun Ajaran/Semester yang dipilih</strong>, tanpa menghapus histori.
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form id="formFilterJadwal" class="row g-3">
                <div class="col-12 col-md-4 col-xl">
                    <label class="form-label" for="filterGuru">Guru</label>
                    <select
                        class="form-select"
                        id="filterGuru"
                        name="id_guru"
                    >
                        <option value="">Semua</option>
                        <?php foreach (($options['guru'] ?? []) as $guru): ?>
                            <?php $identifier = $guruIdentifier($guru); ?>
                            <option value="<?= (int) $guru['id'] ?>">
                                <?= esc(
                                    $guru['nama']
                                    . ($identifier !== '' ? ' (' . $identifier . ')' : '')
                                ) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-md-4 col-xl">
                    <label class="form-label" for="filterKelas">Kelas</label>
                    <select
                        class="form-select"
                        id="filterKelas"
                        name="id_kelas"
                    >
                        <option value="">Semua</option>
                        <?php foreach (($options['kelas'] ?? []) as $kelas): ?>
                            <option value="<?= (int) $kelas['id'] ?>">
                                <?= esc(
                                    $kelas['nama_tahun']
                                    . ' - '
                                    . $kelas['semester']
                                    . ' · '
                                    . $kelas['nama_kelas']
                                ) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-md-4 col-xl">
                    <label class="form-label" for="filterTahun">
                        Tahun Ajaran
                    </label>
                    <select
                        class="form-select"
                        id="filterTahun"
                        name="id_tahun"
                    >
                        <option value="">Semua</option>
                        <?php foreach (($options['tahun'] ?? []) as $tahun): ?>
                            <option value="<?= (int) $tahun['id'] ?>">
                                <?= esc(
                                    $tahun['nama_tahun']
                                    . ' - '
                                    . $tahun['semester']
                                    . ((int) $tahun['status_aktif'] === 1
                                        ? ' (Aktif)'
                                        : '')
                                ) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-md-4 col-xl">
                    <label class="form-label" for="filterHari">Hari</label>
                    <select
                        class="form-select"
                        id="filterHari"
                        name="hari"
                    >
                        <option value="">Semua</option>
                        <?php foreach (
                            ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu']
                            as $hari
                        ): ?>
                            <option value="<?= esc($hari) ?>">
                                <?= esc($hari) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-md-4 col-xl">
                    <label class="form-label" for="filterStatus">Status</label>
                    <select
                        class="form-select"
                        id="filterStatus"
                        name="status_jadwal"
                    >
                        <option value="">Semua</option>
                        <option value="Aktif">Aktif</option>
                        <option value="Nonaktif">Nonaktif</option>
                    </select>
                </div>

                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-filter-alt me-1"></i>
                        Terapkan
                    </button>

                    <button
                        type="button"
                        class="btn btn-outline-secondary"
                        id="btnResetFilter"
                    >
                        Reset
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Daftar Jadwal Guru</h5>

            <?php if (empty($canManage)): ?>
                <span class="badge bg-label-info">Readonly</span>
            <?php endif; ?>
        </div>

        <div class="card-datatable table-responsive">
            <table
                class="table table-hover align-middle"
                id="tableJadwal"
            >
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

                        <?php if (!empty($canManage)): ?>
                            <th style="width:80px;">Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <?php if (!empty($canManage)): ?>
        <div
            class="modal fade"
            id="modalImportJadwal"
            tabindex="-1"
            aria-hidden="true"
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="formImportJadwal">
                        <?= csrf_field() ?>

                        <div class="modal-header">
                            <h5 class="modal-title">
                                Import Jadwal Guru
                            </h5>

                            <button
                                type="button"
                                class="btn-close"
                                data-bs-dismiss="modal"
                                aria-label="Tutup"
                            ></button>
                        </div>

                        <div class="modal-body">
                            <div class="alert alert-warning">
                                Import bersifat <strong>atomic</strong>.
                                Satu baris error atau bentrok akan membatalkan
                                seluruh import.
                            </div>

                            <div class="alert alert-light border">
                                Kolom <strong>IDENTITAS_GURU</strong> menerima
                                NIP 18 digit atau NIK 16 digit dan wajib
                                disimpan sebagai <strong>Text</strong> di Excel.
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="importTahun">
                                    Tahun Ajaran / Semester Aktif
                                </label>

                                <select
                                    class="form-select"
                                    id="importTahun"
                                    name="id_tahun"
                                    required
                                >
                                    <option value="">Pilih</option>

                                    <?php foreach (($options['tahun'] ?? []) as $tahun): ?>
                                        <?php if ((int) $tahun['status_aktif'] === 1): ?>
                                            <option value="<?= (int) $tahun['id'] ?>">
                                                <?= esc(
                                                    $tahun['nama_tahun']
                                                    . ' - '
                                                    . $tahun['semester']
                                                    . ' (Aktif)'
                                                ) ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="form-label" for="fileImportJadwal">
                                    File Excel
                                </label>

                                <input
                                    type="file"
                                    class="form-control"
                                    id="fileImportJadwal"
                                    name="file"
                                    accept=".xlsx,.xls"
                                    required
                                >
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button
                                type="button"
                                class="btn btn-outline-secondary"
                                data-bs-dismiss="modal"
                            >
                                Batal
                            </button>

                            <button
                                type="submit"
                                class="btn btn-primary"
                                id="btnProsesImportJadwal"
                            >
                                <span
                                    class="spinner-border spinner-border-sm d-none me-1"
                                    aria-hidden="true"
                                ></span>
                                Import
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
