<?= $this->extend('main') ?>

<?= $this->section('content') ?>

<style>
    #presensiMengajarApp .jurnal-status-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .5rem;
        max-width: 28rem;
    }

    #presensiMengajarApp .jurnal-status,
    #presensiMengajarApp .jurnal-student-status {
        min-height: 44px;
    }

    #presensiMengajarApp .jurnal-sticky-actions {
        justify-content: space-between;
    }

    #presensiMengajarApp .jurnal-sticky-actions > small {
        flex: 1 1 16rem;
    }

    #presensiMengajarApp .jurnal-student-search-wrap {
        position: relative;
    }

    #presensiMengajarApp .jurnal-student-suggestions {
        position: absolute;
        z-index: 20;
        top: calc(100% + .25rem);
        left: 0;
        right: 0;
        max-height: 18rem;
        overflow-y: auto;
        background: var(--bs-body-bg, #fff);
        border: 1px solid rgba(67, 89, 113, .2);
        border-radius: .5rem;
        box-shadow: 0 .5rem 1rem rgba(67, 89, 113, .12);
    }

    #presensiMengajarApp .jurnal-student-suggestion {
        width: 100%;
        border: 0;
        border-bottom: 1px solid rgba(67, 89, 113, .08);
        background: transparent;
        text-align: left;
        padding: .75rem 1rem;
        min-height: 44px;
    }

    #presensiMengajarApp .jurnal-student-suggestion:last-child {
        border-bottom: 0;
    }

    #presensiMengajarApp .jurnal-student-row {
        border: 1px solid rgba(67, 89, 113, .12);
        border-radius: .625rem;
        padding: .75rem;
    }

    #presensiMengajarApp .jurnal-student-status-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .375rem;
        width: min(100%, 18rem);
    }

    #presensiMengajarApp .jurnal-student-section.is-disabled {
        opacity: .65;
    }

    @media (max-width: 575.98px) {
        #presensiMengajarApp .jurnal-sticky-actions > .btn {
            width: 100%;
        }

        #presensiMengajarApp #jurnalMateri,
        #presensiMengajarApp #jurnalCatatan {
            min-height: 8rem;
        }

        #presensiMengajarApp .jurnal-student-row {
            padding: .75rem;
        }
    }
</style>

<div
    id="presensiMengajarApp"
    data-base-url="<?= esc(base_url()) ?>"
    data-tanggal="<?= esc($tanggal ?? '') ?>"
    data-selected-guru="<?= (int) ($selectedGuru ?? 0) ?>"
    data-selected-jadwal="<?= (int) ($selectedJadwal ?? 0) ?>"
>
    <div class="sisfour-page-header">
        <div class="sisfour-page-header__copy">
            <h4 class="fw-bold mb-1"><?= esc($title ?? 'Presensi Mengajar / Jurnal') ?></h4>
            <p class="text-muted mb-0">
                Pilih Guru terlebih dahulu, lalu pilih Jadwal aktif Guru pada tanggal tersebut.
            </p>
        </div>

        <?php if (!empty($tahunAktif)): ?>
            <div class="sisfour-page-actions">
                <span class="badge bg-label-primary fs-6">
                    <?= esc($tahunAktif['nama_tahun'] ?? '') ?>
                    <?= esc($tahunAktif['semester'] ?? '') ?>
                </span>
            </div>
        <?php endif; ?>
    </div>

    <div class="card sisfour-filter-card mb-4">
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
                    <select
                        class="form-select"
                        id="jurnalGuru"
                        data-searchable-select
                        data-search-placeholder="Ketik nama atau NIP Guru..."
                    >
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

                <div class="col-12 sisfour-filter-actions justify-content-end">
                    <button type="button" class="btn btn-primary sisfour-primary-action" id="btnMuatJurnal">
                        <i class="bx bx-search-alt me-1"></i> Muat Jurnal
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="jurnalInfo" class="alert alert-info d-none" role="alert" aria-live="polite"></div>

    <div class="card d-none" id="jurnalCard">
        <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <div>
                <h5 class="mb-1" id="jurnalCardTitle">Jurnal Mengajar</h5>
                <small class="text-muted" id="jurnalCardMeta"></small>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <span class="badge bg-label-secondary" id="jurnalCapability"></span>
                <span class="badge bg-label-warning d-none" id="jurnalRevisionBadge">Mode Revisi</span>
            </div>
        </div>

        <div class="card-body">
            <div class="sisfour-mobile-form">
                <div>
                    <label class="form-label">Status Guru</label>
                    <div class="jurnal-status-grid" id="jurnalStatusGroup" role="group" aria-label="Status jurnal mengajar">
                        <button type="button" class="btn btn-outline-success jurnal-status sisfour-touch-target" data-status="Hadir" aria-pressed="false">
                            Hadir
                        </button>
                        <button type="button" class="btn btn-outline-warning jurnal-status sisfour-touch-target" data-status="Izin" aria-pressed="false">
                            Izin
                        </button>
                        <button type="button" class="btn btn-outline-danger jurnal-status sisfour-touch-target" data-status="Sakit" aria-pressed="false">
                            Sakit
                        </button>
                    </div>
                </div>

                <div>
                    <label class="form-label" for="jurnalMateri">Materi / Keterangan <span class="text-danger">*</span></label>
                    <textarea
                        class="form-control"
                        id="jurnalMateri"
                        rows="6"
                        autocomplete="off"
                        placeholder="Tuliskan materi pembelajaran. Untuk Izin/Sakit tetap wajib isi keterangan/tugas."
                    ></textarea>
                    <div class="form-text">
                        Materi/keterangan wajib untuk status Hadir, Izin, maupun Sakit.
                    </div>
                </div>

                <div>
                    <label class="form-label" for="jurnalCatatan">Catatan</label>
                    <textarea
                        class="form-control"
                        id="jurnalCatatan"
                        rows="4"
                        autocomplete="off"
                        placeholder="Catatan tambahan pembelajaran (opsional)."
                    ></textarea>
                </div>

                <div class="jurnal-student-section" id="jurnalStudentSection">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-2">
                        <div>
                            <label class="form-label mb-1" for="jurnalStudentSearch">Siswa Tidak Mengikuti Pembelajaran</label>
                            <div class="form-text mt-0">
                                Hanya tersimpan pada Jurnal Mengajar dan tidak mengubah Presensi Siswa resmi.
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-1" aria-label="Ringkasan status siswa">
                            <span class="badge bg-label-warning">S <span id="jurnalSakitCount">0</span></span>
                            <span class="badge bg-label-info">I <span id="jurnalIzinCount">0</span></span>
                            <span class="badge bg-label-danger">A <span id="jurnalAlphaCount">0</span></span>
                        </div>
                    </div>

                    <div class="jurnal-student-search-wrap mb-3">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bx bx-search"></i></span>
                            <input
                                type="search"
                                class="form-control"
                                id="jurnalStudentSearch"
                                placeholder="Cari nama atau NISN siswa kelas ini..."
                                autocomplete="off"
                            >
                        </div>
                        <div class="jurnal-student-suggestions d-none" id="jurnalStudentSuggestions"></div>
                    </div>

                    <div class="d-flex flex-column gap-2" id="jurnalStudentSelected"></div>
                    <div class="text-muted small py-2" id="jurnalStudentEmpty">
                        Belum ada siswa Sakit/Izin/Alpha pada Jurnal ini.
                    </div>
                </div>
            </div>
        </div>

        <div class="card-footer sisfour-sticky-actions jurnal-sticky-actions">
            <small class="text-muted" id="jurnalGeoNote">
                Geofence hanya diwajibkan untuk Guru dengan status Hadir. Izin/Sakit tidak memerlukan lokasi.
            </small>

            <button type="button" class="btn btn-success sisfour-primary-action" id="btnSimpanJurnal">
                <i class="bx bx-save me-1"></i> Simpan Jurnal
            </button>
        </div>
    </div>
</div>

<?= $this->endSection() ?>