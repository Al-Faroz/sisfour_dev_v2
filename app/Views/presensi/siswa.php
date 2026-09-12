<?= $this->extend('main') ?>

<?= $this->section('content') ?>

<?php
$isDirectScheduledTeacher = isset($initialResult)
    && is_array($initialResult)
    && !empty($initialResult['success'])
    && ($initialResult['capability'] ?? '') === 'GURU_TERJADWAL';
?>

<div
    id="presensiSiswaApp"
    data-base-url="<?= esc(base_url()) ?>"
    data-tanggal="<?= esc($tanggal ?? '') ?>"
    data-selected-kelas="<?= (int) ($selectedKelas ?? 0) ?>"
    data-selected-sesi="<?= esc($selectedSesi ?? 'Sesi Awal') ?>"
>
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1"><?= esc($title ?? 'Presensi Siswa') ?></h4>
            <p class="text-muted mb-0">
                Input Presensi Sesi Awal dan Sesi Akhir sesuai Jadwal Guru atau hak Wali Kelas.
            </p>
        </div>

        <?php if (!empty($tahunAktif)): ?>
            <span class="badge bg-label-primary fs-6">
                <?= esc($tahunAktif['nama_tahun'] ?? '') ?>
                <?= esc($tahunAktif['semester'] ?? '') ?>
            </span>
        <?php endif; ?>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label" for="presensiTanggal">Tanggal</label>
                    <input
                        type="date"
                        class="form-control"
                        id="presensiTanggal"
                        value="<?= esc($tanggal ?? '') ?>"
                        <?= $isDirectScheduledTeacher ? 'disabled' : '' ?>
                    >
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label" for="presensiKelas">Kelas</label>
                    <select class="form-select" id="presensiKelas" <?= $isDirectScheduledTeacher ? 'disabled' : '' ?>>
                        <option value="">Pilih kelas</option>
                        <?php foreach (($kelasOptions ?? []) as $kelas): ?>
                            <option
                                value="<?= (int) $kelas['id'] ?>"
                                <?= (int) ($selectedKelas ?? 0) === (int) $kelas['id'] ? 'selected' : '' ?>
                            >
                                <?= esc($kelas['nama_kelas']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-md-2">
                    <label class="form-label" for="presensiSesi">Sesi</label>
                    <select class="form-select" id="presensiSesi" <?= $isDirectScheduledTeacher ? 'disabled' : '' ?>>
                        <option value="Sesi Awal" <?= ($selectedSesi ?? 'Sesi Awal') === 'Sesi Awal' ? 'selected' : '' ?>>Sesi Awal</option>
                        <option value="Sesi Akhir" <?= ($selectedSesi ?? '') === 'Sesi Akhir' ? 'selected' : '' ?>>Sesi Akhir</option>
                    </select>
                </div>

                <div class="col-12 col-md-2 d-grid <?= $isDirectScheduledTeacher ? 'd-none' : '' ?>">
                    <button type="button" class="btn btn-primary" id="btnMuatPresensi">
                        <i class="bx bx-search-alt me-1"></i> Muat
                    </button>
                </div>
            </div>

            <?php if ($isDirectScheduledTeacher): ?>
                <div class="small text-muted mt-3">
                    <i class="bx bx-lock-alt me-1"></i>
                    Tanggal, kelas, dan sesi mengikuti jadwal mengajar yang dipilih dari Dashboard.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div id="presensiInfo" class="alert alert-info d-none" role="alert"></div>

    <div class="card d-none" id="presensiCard">
        <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <div>
                <h5 class="mb-1" id="presensiCardTitle">Daftar Siswa</h5>
                <small class="text-muted" id="presensiCardMeta"></small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-label-secondary" id="presensiCapability"></span>
                <span class="badge bg-label-warning d-none" id="presensiRevisionBadge">Mode Revisi</span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 60px;">No.</th>
                        <th>Siswa</th>
                        <th>NISN</th>
                        <th style="min-width: 390px;">Status</th>
                    </tr>
                </thead>
                <tbody id="presensiTableBody"></tbody>
            </table>
        </div>

        <div class="card-footer d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <small class="text-muted" id="presensiGeoNote">
                Lokasi hanya diminta bila geofencing wajib untuk jalur Guru Terjadwal.
            </small>

            <button type="button" class="btn btn-success" id="btnSimpanPresensi">
                <i class="bx bx-save me-1"></i> Simpan Presensi
            </button>
        </div>
    </div>
</div>

<?php if (isset($initialResult) && is_array($initialResult)): ?>
<script type="application/json" id="presensiInitialResult"><?= json_encode(
    $initialResult,
    JSON_UNESCAPED_UNICODE
    | JSON_UNESCAPED_SLASHES
    | JSON_HEX_TAG
    | JSON_HEX_AMP
    | JSON_HEX_APOS
    | JSON_HEX_QUOT
) ?></script>
<?php endif; ?>

<?= $this->endSection() ?>