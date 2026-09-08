<?= $this->extend('main') ?>

<?= $this->section('content') ?>

<div
    id="presensiMengajarApp"
    data-base-url="<?= esc(base_url()) ?>"
    data-tanggal="<?= esc($tanggal ?? '') ?>"
    data-selected-guru="<?= (int) ($selectedGuru ?? 0) ?>"
    data-selected-jadwal="<?= (int) ($selectedJadwal ?? 0) ?>"
>
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1"><?= esc($title ?? 'Presensi Mengajar / Jurnal') ?></h4>
            <p class="text-muted mb-0">
                Pilih Guru terlebih dahulu, lalu pilih Jadwal aktif Guru pada tanggal tersebut.
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
                <div class="col-12 col-lg-3">
                    <label class="form-label" for="jurnalTanggal">Tanggal</label>
                    <input
                        type="date"
                        class="form-control"
                        id="jurnalTanggal"
                        value="<?= esc($tanggal ?? '') ?>"
                    >
                </div>

                <div class="col-12 col-lg-5">
                    <label class="form-label" for="jurnalGuru">Nama Guru</label>
                    <select class="form-select" id="jurnalGuru">
                        <option value="">Pilih Guru</option>
                        <?php foreach (($guruOptions ?? []) as $guru): ?>
                            <option
                                value="<?= (int) $guru['id'] ?>"
                                <?= (int) ($selectedGuru ?? 0) === (int) $guru['id'] ? 'selected' : '' ?>
                            >
                                <?= esc($guru['nama']) ?>
                                <?= !empty($guru['nip']) ? ' — ' . esc($guru['nip']) : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-lg-4">
                    <label class="form-label" for="jurnalJadwal">Jadwal Guru</label>
                    <select
                        class="form-select"
                        id="jurnalJadwal"
                        <?= empty($jadwalOptions) ? 'disabled' : '' ?>
                    >
                        <option value="">Pilih Jadwal</option>
                        <?php foreach (($jadwalOptions ?? []) as $jadwal): ?>
                            <option
                                value="<?= (int) $jadwal['id'] ?>"
                                <?= (int) ($selectedJadwal ?? 0) === (int) $jadwal['id'] ? 'selected' : '' ?>
                            >
                                <?= esc(
                                    ($jadwal['jam_mulai'] ?? '')
                                    . ' - ' . ($jadwal['jam_selesai'] ?? '')
                                    . ' | ' . ($jadwal['nama_kelas'] ?? '')
                                    . ' | ' . ($jadwal['nama_mapel'] ?? '')
                                    . ' | ' . ($jadwal['sesi'] ?? '')
                                ) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 d-grid d-md-flex justify-content-md-end">
                    <button type="button" class="btn btn-primary" id="btnMuatJurnal">
                        <i class="bx bx-search-alt me-1"></i> Muat Jurnal
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="jurnalInfo" class="alert alert-info d-none" role="alert"></div>

    <div class="card d-none" id="jurnalCard">
        <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <div>
                <h5 class="mb-1" id="jurnalCardTitle">Jurnal Mengajar</h5>
                <small class="text-muted" id="jurnalCardMeta"></small>
            </div>
            <div class="d-flex gap-2">
                <span class="badge bg-label-secondary" id="jurnalCapability"></span>
                <span class="badge bg-label-warning d-none" id="jurnalRevisionBadge">Mode Revisi</span>
            </div>
        </div>

        <div class="card-body">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Status</label>
                    <div class="d-flex flex-wrap gap-2" id="jurnalStatusGroup">
                        <button type="button" class="btn btn-outline-success jurnal-status" data-status="Hadir">
                            Hadir
                        </button>
                        <button type="button" class="btn btn-outline-warning jurnal-status" data-status="Izin">
                            Izin
                        </button>
                        <button type="button" class="btn btn-outline-danger jurnal-status" data-status="Sakit">
                            Sakit
                        </button>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label" for="jurnalMateri">Materi / Keterangan</label>
                    <textarea
                        class="form-control"
                        id="jurnalMateri"
                        rows="5"
                        placeholder="Tuliskan materi pembelajaran. Untuk Izin/Sakit tetap wajib isi keterangan/tugas."
                    ></textarea>
                    <div class="form-text">
                        Materi/keterangan wajib untuk status Hadir, Izin, maupun Sakit.
                    </div>
                </div>
            </div>
        </div>

        <div class="card-footer d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <small class="text-muted" id="jurnalGeoNote">
                Geofence hanya diwajibkan untuk Guru dengan status Hadir. Izin/Sakit tidak memerlukan lokasi.
            </small>

            <button type="button" class="btn btn-success" id="btnSimpanJurnal">
                <i class="bx bx-save me-1"></i> Simpan Jurnal
            </button>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
