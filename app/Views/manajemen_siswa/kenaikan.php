<?= $this->extend('main') ?>
<?= $this->section('content') ?>
<?php $promotionMeta = $sourceClasses[0] ?? null; ?>
<div id="kenaikanSiswaApp" data-base-url="<?= esc(base_url()) ?>">
    <div class="sisfour-page-header">
        <div class="sisfour-page-header__copy">
            <h4 class="fw-bold mb-1">Kenaikan Kelas</h4>
            <p class="text-muted mb-0">Proses siswa tingkat 7 dan 8 menuju kelas pada tahun ajaran berikutnya.</p>
        </div>
    </div>

    <?php if ($tahunAktif): ?>
        <div class="alert alert-info sisfour-compact-note">
            Tahun ajaran aktif:
            <strong><?= esc($tahunAktif['nama_tahun'].' - '.$tahunAktif['semester']) ?></strong>
        </div>
    <?php endif; ?>

    <?php if ($promotionMeta): ?>
        <?php if (!empty($promotionMeta['source_is_active'])): ?>
            <div class="alert alert-primary sisfour-compact-note">
                Periode kenaikan:
                <strong><?= esc($promotionMeta['periode_sumber'] ?? '-') ?></strong>
                <i class="bx bx-right-arrow-alt mx-1"></i>
                <strong><?= esc($promotionMeta['periode_tujuan'] ?? '-') ?></strong>.
                Selesaikan seluruh kelas sebelum mengaktifkan Tahun Ajaran tujuan.
            </div>
        <?php else: ?>
            <div class="alert alert-warning sisfour-compact-note">
                Periode sumber <strong><?= esc($promotionMeta['periode_sumber'] ?? '-') ?></strong>
                sudah nonaktif. Data di bawah adalah <strong>ringkasan progress</strong> dan tidak dapat diproses ulang.
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="card sisfour-filter-card mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label" for="filterTingkatKenaikan">Tingkat</label>
                    <select class="form-select" id="filterTingkatKenaikan" data-searchable-off="1">
                        <option value="">Semua Tingkat</option>
                        <option value="7">Tingkat 7</option>
                        <option value="8">Tingkat 8</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card sisfour-table-card">
        <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <h5 class="mb-0">Progress Kenaikan per Kelas</h5>
            <div class="small text-muted">Belum Diproses · Sebagian · Selesai</div>
        </div>
        <div id="kenaikanMobileList" class="d-md-none list-group list-group-flush">
            <div class="list-group-item sisfour-mobile-state text-muted">Memuat progress kenaikan...</div>
        </div>
        <div class="d-none d-md-block table-responsive">
            <table class="table table-hover align-middle mb-0" id="tableKenaikanKelas">
                <thead>
                    <tr>
                        <th>Kelas</th>
                        <th>Tingkat</th>
                        <th>Jumlah Siswa</th>
                        <th>Progress</th>
                        <th style="width:180px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sourceClasses as $kelas): ?>
                        <?php
                            $progress = (string) ($kelas['progress_status'] ?? 'Belum Diproses');
                            $badgeClass = match ($progress) {
                                'Selesai' => 'success',
                                'Sebagian' => 'warning',
                                default => 'secondary',
                            };
                            $canProcess = !empty($kelas['can_process']);
                            $targetExists = !empty($kelas['target_year_exists']);
                            $sourceActive = !empty($kelas['source_is_active']);
                            $total = (int) ($kelas['jumlah_siswa'] ?? 0);
                            $done = (int) ($kelas['jumlah_dinaikkan'] ?? 0);
                        ?>
                        <tr
                            data-nama="<?= esc($kelas['nama_kelas'], 'attr') ?>"
                            data-tingkat="<?= esc($kelas['tingkat'], 'attr') ?>"
                            data-total="<?= $total ?>"
                            data-done="<?= $done ?>"
                            data-progress="<?= esc($progress, 'attr') ?>"
                            data-badge="<?= esc($badgeClass, 'attr') ?>"
                        >
                            <td class="fw-semibold"><?= esc($kelas['nama_kelas']) ?></td>
                            <td><?= esc($kelas['tingkat']) ?></td>
                            <td><?= $total ?> siswa</td>
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    <span class="badge bg-label-<?= esc($badgeClass) ?> align-self-start">
                                        <?= esc($progress) ?>
                                    </span>
                                    <span class="small text-muted"><?= $done ?> / <?= $total ?> siswa</span>
                                </div>
                            </td>
                            <td>
                                <button
                                    type="button"
                                    class="btn btn-sm <?= $canProcess ? 'btn-primary' : 'btn-outline-secondary' ?> sisfour-touch-target--compact btn-proses-naik"
                                    data-id="<?= (int)$kelas['id'] ?>"
                                    data-nama="<?= esc($kelas['nama_kelas']) ?>"
                                    <?= $canProcess ? '' : 'disabled' ?>
                                >
                                    <?php if ($progress === 'Selesai'): ?>
                                        Selesai
                                    <?php elseif (!$sourceActive): ?>
                                        Periode Ditutup
                                    <?php elseif (!$targetExists): ?>
                                        Target Belum Ada
                                    <?php elseif ($total <= 0): ?>
                                        Tidak Ada Siswa
                                    <?php else: ?>
                                        Proses
                                    <?php endif; ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($sourceClasses === []): ?>
                        <tr class="sisfour-empty-row"><td colspan="5" class="text-muted">Tidak ada data kelas kenaikan yang dapat ditampilkan.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="modalKenaikan" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-sm-down">
            <form id="formKenaikan" class="modal-content">
                <?= csrf_field() ?>
                <input type="hidden" id="idKelasAsal">
                <div class="modal-header py-2">
                    <div>
                        <h5 class="modal-title">Proses Kenaikan Kelas</h5>
                        <div class="small text-muted" id="labelKelasAsal"></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body overflow-auto py-3">
                    <div class="row g-3 mb-4">
                        <div class="col-md-8">
                            <label class="form-label" for="kelasTujuanNaik">Kelas Tujuan</label>
                            <select class="form-select" id="kelasTujuanNaik" name="id_kelas_tujuan" required>
                                <option value="">Pilih kelas tujuan</option>
                            </select>
                            <input type="hidden" id="idTahunBaru" name="id_tahun_baru">
                            <div class="form-text">Hanya kelas pada Ganjil tahun berikutnya dan tingkat yang valid yang ditampilkan.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Jumlah Dipilih</label>
                            <div class="form-control bg-light" id="jumlahNaikDipilih">0 siswa</div>
                        </div>
                    </div>
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-2">
                        <strong>Checklist Siswa</strong>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary sisfour-touch-target--compact" id="btnPilihSemuaNaik">Pilih Semua Belum Diproses</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary sisfour-touch-target--compact" id="btnKosongkanNaik">Kosongkan</button>
                        </div>
                    </div>
                    <div class="table-responsive border rounded sisfour-admin-matrix-scroll" data-mobile-exception="bulk-student-selection">
                        <table class="table table-hover mb-0">
                            <thead><tr><th style="width:50px;"></th><th>Nama</th><th>NISN</th><th>JK</th><th>Status</th></tr></thead>
                            <tbody id="tbodyNaik"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer py-2 sisfour-modal-actions">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Proses Kenaikan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
