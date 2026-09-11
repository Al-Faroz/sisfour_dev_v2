<?= $this->extend('main') ?>

<?= $this->section('content') ?>

<?php if (! empty($personaliaError)): ?>
    <div class="alert alert-danger">
        <i class="bx bx-error-circle me-1"></i><?= esc($personaliaError) ?>
    </div>
<?php else: ?>
    <?php
    $owner = $owner ?? [];
    $ownerType = $owner_type ?? 'guru';
    $ownerLabel = $ownerType === 'pegawai' ? 'Pegawai' : 'Guru';
    $canEdit = ! empty($can_edit);
    $canViewDocuments = ! empty($can_view_documents);
    $isSelf = ! empty($is_self);

    $encodeRow = static function (array $row): string {
        return rawurlencode((string) json_encode(
            $row,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ));
    };

    $fileUrl = static function (string $category, int $id, string $field): string {
        return base_url('personalia/file/' . $category . '/' . $id . '/' . $field);
    };

    $display = static function ($value): string {
        $value = trim((string) $value);
        return $value !== '' ? $value : '-';
    };

    $formatDate = static function ($value): string {
        $value = trim((string) $value);
        if ($value === '') {
            return '-';
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return $value;
        }

        $months = [
            1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
            'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des',
        ];

        return date('d', $timestamp)
            . ' ' . $months[(int) date('n', $timestamp)]
            . ' ' . date('Y', $timestamp);
    };

    $gender = ($owner['jenis_kelamin'] ?? '') === 'P'
        ? 'Perempuan'
        : (($owner['jenis_kelamin'] ?? '') === 'L' ? 'Laki-laki' : '-');

    $foto = trim((string) ($owner['foto'] ?? ''));
    $fotoFolder = $ownerType === 'guru'
        ? 'uploads/foto_guru/'
        : 'uploads/foto_pegawai/';
    $fotoUrl = $foto !== ''
        ? base_url($fotoFolder . rawurlencode(basename($foto)))
        : '';

    $identityPrimary = trim((string) ($owner['nip'] ?? '')) !== ''
        ? 'NIP ' . trim((string) $owner['nip'])
        : (
            trim((string) ($owner['nik'] ?? '')) !== ''
                ? 'NIK ' . trim((string) $owner['nik'])
                : 'Identitas belum lengkap'
        );

    $pendidikanCount = count($pendidikan ?? []);
    $penugasanCount = count($penugasan ?? []);
    $pangkatCount = count($pangkat ?? []);
    $dokumenCount = count($dokumen ?? []);
    ?>

    <div
        id="personaliaApp"
        data-base-url="<?= esc(base_url(), 'attr') ?>"
        data-endpoint-base="<?= esc($endpointBase ?? '', 'attr') ?>"
        data-can-edit="<?= $canEdit ? '1' : '0' ?>"
        data-owner-type="<?= esc($ownerType, 'attr') ?>"
        data-owner-id="<?= (int) ($owner_id ?? 0) ?>"
    >
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-3">
            <div>
                <a href="<?= esc($backUrl ?? base_url(), 'attr') ?>" class="btn btn-sm btn-outline-secondary mb-2">
                    <i class="bx bx-arrow-back me-1"></i><?= esc($backLabel ?? 'Kembali') ?>
                </a>
                <div class="small text-muted">Personalia <?= esc($ownerLabel) ?></div>
            </div>

            <div class="d-flex flex-wrap gap-2 sisfour-profile-hero__actions">
                <a href="<?= esc($portfolioUrl ?? '#', 'attr') ?>" target="_blank" rel="noopener"
                    class="btn btn-outline-primary">
                    <i class="bx bx-show me-1"></i>Preview Portofolio
                </a>
                <a href="<?= esc(($portfolioUrl ?? '#') . '?download=1', 'attr') ?>" class="btn btn-primary">
                    <i class="bx bx-download me-1"></i>Download PDF
                </a>
            </div>
        </div>

        <div class="card sisfour-profile-hero mb-4">
            <div class="card-body">
                <div class="sisfour-profile-hero__body">
                    <div>
                        <?php if ($fotoUrl !== ''): ?>
                            <img src="<?= esc($fotoUrl, 'attr') ?>"
                                alt="Foto <?= esc($owner['nama'] ?? $ownerLabel, 'attr') ?>"
                                class="sisfour-profile-photo sisfour-profile-photo--lg">
                        <?php else: ?>
                            <div class="sisfour-profile-photo sisfour-profile-photo--lg sisfour-profile-photo__fallback"
                                aria-label="Foto belum tersedia">
                                <i class="bx bx-user"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="sisfour-profile-identity">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                            <span class="badge bg-label-primary"><?= esc($ownerLabel) ?></span>
                            <?php if (! empty($owner['status_kepegawaian'])): ?>
                                <span class="badge bg-label-secondary">
                                    <?= esc($owner['status_kepegawaian']) ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($isSelf): ?>
                                <span class="badge bg-label-info">Data Saya</span>
                            <?php elseif ($canEdit): ?>
                                <span class="badge bg-label-warning">Mode Kelola</span>
                            <?php else: ?>
                                <span class="badge bg-label-info">Readonly</span>
                            <?php endif; ?>
                        </div>

                        <h3 class="sisfour-profile-name"><?= esc($owner['nama'] ?? '-') ?></h3>

                        <div class="sisfour-meta-list">
                            <span class="sisfour-meta-list__item">
                                <i class="bx bx-id-card"></i><?= esc($identityPrimary) ?>
                            </span>
                            <?php if (! empty($owner['nuptk'])): ?>
                                <span class="sisfour-meta-list__item">
                                    <i class="bx bx-badge-check"></i>NUPTK <?= esc($owner['nuptk']) ?>
                                </span>
                            <?php endif; ?>
                            <?php if (! empty($owner['email'])): ?>
                                <span class="sisfour-meta-list__item">
                                    <i class="bx bx-envelope"></i><?= esc($owner['email']) ?>
                                </span>
                            <?php endif; ?>
                            <?php if (! empty($owner['no_telepon'])): ?>
                                <span class="sisfour-meta-list__item">
                                    <i class="bx bx-phone"></i><?= esc($owner['no_telepon']) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <?php if ($isSelf): ?>
                            <div class="mt-3">
                                <a href="<?= esc($backUrl ?? '#', 'attr') ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="bx bx-edit me-1"></i>Edit Biodata di Profile
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if (
            $ownerType === 'pegawai'
            && ! empty($owner['jabatan_legacy'])
            && empty($penugasan)
        ): ?>
            <div class="alert alert-secondary d-flex align-items-start gap-2">
                <i class="bx bx-briefcase fs-5 mt-1"></i>
                <div>
                    <strong>Jabatan lama masih tersimpan.</strong>
                    <div class="small mt-1">
                        <?= esc($owner['jabatan_legacy']) ?>. Tambahkan Riwayat Penugasan
                        dengan periode/SK yang benar; sistem tidak menebak tanggal legacy.
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="sisfour-stat-grid mb-4">
            <div class="sisfour-stat-card">
                <div class="sisfour-stat-card__value"><?= $pendidikanCount ?></div>
                <div class="sisfour-stat-card__label">Riwayat Pendidikan</div>
            </div>
            <div class="sisfour-stat-card">
                <div class="sisfour-stat-card__value"><?= $penugasanCount ?></div>
                <div class="sisfour-stat-card__label">Riwayat Penugasan</div>
            </div>
            <div class="sisfour-stat-card">
                <div class="sisfour-stat-card__value"><?= $pangkatCount ?></div>
                <div class="sisfour-stat-card__label">Riwayat Pangkat</div>
            </div>
            <div class="sisfour-stat-card">
                <div class="sisfour-stat-card__value"><?= $dokumenCount ?></div>
                <div class="sisfour-stat-card__label">Dokumen Personalia</div>
            </div>
        </div>

        <div class="nav-align-top nav-tabs-shadow sisfour-personalia-native-tabs">
            <ul class="nav nav-tabs nav-fill" role="tablist">
                <li class="nav-item">
                    <button
                        type="button"
                        class="nav-link active"
                        role="tab"
                        data-bs-toggle="tab"
                        data-bs-target="#tabBiodata"
                        aria-controls="tabBiodata"
                        aria-selected="true"
                        title="Biodata"
                    >
                        <i class="bx bx-user"></i>
                        <span class="d-none d-lg-inline">Biodata</span>
                    </button>
                </li>

                <li class="nav-item">
                    <button
                        type="button"
                        class="nav-link"
                        role="tab"
                        data-bs-toggle="tab"
                        data-bs-target="#tabPendidikan"
                        aria-controls="tabPendidikan"
                        aria-selected="false"
                        title="Pendidikan"
                    >
                        <i class="bx bx-book-open"></i>
                        <span class="d-none d-lg-inline">Pendidikan</span>
                        <span class="badge rounded-pill bg-label-secondary"><?= $pendidikanCount ?></span>
                    </button>
                </li>

                <li class="nav-item">
                    <button
                        type="button"
                        class="nav-link"
                        role="tab"
                        data-bs-toggle="tab"
                        data-bs-target="#tabPenugasan"
                        aria-controls="tabPenugasan"
                        aria-selected="false"
                        title="Penugasan"
                    >
                        <i class="bx bx-briefcase"></i>
                        <span class="d-none d-lg-inline">Penugasan</span>
                        <span class="badge rounded-pill bg-label-secondary"><?= $penugasanCount ?></span>
                    </button>
                </li>

                <li class="nav-item">
                    <button
                        type="button"
                        class="nav-link"
                        role="tab"
                        data-bs-toggle="tab"
                        data-bs-target="#tabPangkat"
                        aria-controls="tabPangkat"
                        aria-selected="false"
                        title="Kepangkatan"
                    >
                        <i class="bx bx-medal"></i>
                        <span class="d-none d-lg-inline">Kepangkatan</span>
                        <span class="badge rounded-pill bg-label-secondary"><?= $pangkatCount ?></span>
                    </button>
                </li>

                <li class="nav-item">
                    <button
                        type="button"
                        class="nav-link"
                        role="tab"
                        data-bs-toggle="tab"
                        data-bs-target="#tabDokumen"
                        aria-controls="tabDokumen"
                        aria-selected="false"
                        title="Dokumen"
                    >
                        <i class="bx bx-file"></i>
                        <span class="d-none d-lg-inline">Dokumen</span>
                        <span class="badge rounded-pill bg-label-secondary"><?= $dokumenCount ?></span>
                    </button>
                </li>

                <li class="nav-item">
                    <button
                        type="button"
                        class="nav-link"
                        role="tab"
                        data-bs-toggle="tab"
                        data-bs-target="#tabPortofolio"
                        aria-controls="tabPortofolio"
                        aria-selected="false"
                        title="Portofolio"
                    >
                        <i class="bx bx-file-blank"></i>
                        <span class="d-none d-lg-inline">Portofolio</span>
                    </button>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="tabBiodata" role="tabpanel">
                    <div class="sisfour-section-heading">
                        <div class="sisfour-section-heading__copy">
                            <h5 class="mb-1">Biodata <?= esc($ownerLabel) ?></h5>
                            <p>Ringkasan identitas administratif dan data pribadi.</p>
                        </div>
                    </div>

                    <div class="sisfour-info-grid">
                        <?php
                        $biodataItems = [
                            ['Nama Lengkap & Gelar', $owner['nama'] ?? null],
                            ['Jenis Kelamin', $gender],
                            ['NIK', $owner['nik'] ?? null],
                            ['NIP', $owner['nip'] ?? null],
                            ['Status Kepegawaian', $owner['status_kepegawaian'] ?? null],
                            ['NUPTK', $owner['nuptk'] ?? null],
                            ['Tempat Lahir', $owner['tempat_lahir'] ?? null],
                            ['Tanggal Lahir', $formatDate($owner['tanggal_lahir'] ?? null)],
                            ['Agama', $owner['agama'] ?? null],
                            ['Telepon', $owner['no_telepon'] ?? null],
                            ['Email', $owner['email'] ?? null],
                            ['Alamat', $owner['alamat'] ?? null],
                        ];
                        ?>
                        <?php foreach ($biodataItems as [$label, $value]): ?>
                            <div class="sisfour-info-item">
                                <span class="sisfour-info-item__label"><?= esc($label) ?></span>
                                <div class="sisfour-info-item__value"><?= nl2br(esc($display($value))) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="tab-pane fade" id="tabPendidikan" role="tabpanel">
                    <div class="sisfour-section-heading">
                        <div class="sisfour-section-heading__copy">
                            <h5 class="mb-1">Riwayat Pendidikan Formal</h5>
                            <p>Ijazah, institusi, program studi, dan dokumen pendukung.</p>
                        </div>
                        <?php if ($canEdit): ?>
                            <button class="btn btn-primary btn-add" data-category="pendidikan">
                                <i class="bx bx-plus me-1"></i>Tambah Pendidikan
                            </button>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($pendidikan)): ?>
                        <div class="sisfour-empty-state">
                            <div class="sisfour-empty-state__icon"><i class="bx bx-book-open"></i></div>
                            <h6 class="mb-1">Belum ada riwayat pendidikan</h6>
                            <p class="text-muted mb-3">Tambahkan pendidikan formal agar riwayat dan portofolio lebih lengkap.</p>
                            <?php if ($canEdit): ?>
                                <button class="btn btn-sm btn-outline-primary btn-add" data-category="pendidikan">
                                    <i class="bx bx-plus me-1"></i>Tambah Pendidikan
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive d-none d-lg-block">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Tingkat</th>
                                        <th>Institusi / Program Studi</th>
                                        <th>Tahun</th>
                                        <th>No. Ijazah</th>
                                        <th>Dokumen</th>
                                        <?php if ($canEdit): ?><th class="text-end">Aksi</th><?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($pendidikan as $row): ?>
                                    <tr>
                                        <td><span class="badge bg-label-primary"><?= esc($row['tingkat_pendidikan']) ?></span></td>
                                        <td>
                                            <strong><?= esc($row['nama_institusi']) ?></strong><br>
                                            <small class="text-muted"><?= esc($display($row['program_studi'] ?? null)) ?></small>
                                        </td>
                                        <td><?= esc($display($row['tahun_lulus'] ?? null)) ?></td>
                                        <td><?= esc($display($row['no_ijazah'] ?? null)) ?></td>
                                        <td class="text-nowrap">
                                            <?php if (
                                                ! $canViewDocuments
                                                && (! empty($row['file_ijazah']) || ! empty($row['file_transkrip']))
                                            ): ?>
                                                <span class="badge bg-label-secondary"><i class="bx bx-lock-alt me-1"></i>Terbatas</span>
                                            <?php else: ?>
                                                <?php if (! empty($row['file_ijazah'])): ?>
                                                    <a class="btn btn-sm btn-outline-secondary"
                                                        href="<?= esc($fileUrl('pendidikan', (int) $row['id'], 'file_ijazah'), 'attr') ?>">
                                                        <i class="bx bx-file me-1"></i>Ijazah
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (! empty($row['file_transkrip'])): ?>
                                                    <a class="btn btn-sm btn-outline-secondary"
                                                        href="<?= esc($fileUrl('pendidikan', (int) $row['id'], 'file_transkrip'), 'attr') ?>">
                                                        <i class="bx bx-file me-1"></i>Transkrip
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (empty($row['file_ijazah']) && empty($row['file_transkrip'])): ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <?php if ($canEdit): ?>
                                            <td class="text-end text-nowrap">
                                                <div class="sisfour-action-group">
                                                    <button class="btn btn-sm btn-outline-primary btn-edit-record"
                                                        data-category="pendidikan"
                                                        data-record="<?= esc($encodeRow($row), 'attr') ?>"
                                                        title="Edit pendidikan"><i class="bx bx-edit"></i></button>
                                                    <button class="btn btn-sm btn-outline-danger btn-delete-record"
                                                        data-category="pendidikan" data-id="<?= (int) $row['id'] ?>"
                                                        title="Hapus pendidikan"><i class="bx bx-trash"></i></button>
                                                </div>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="d-lg-none">
                            <?php foreach ($pendidikan as $row): ?>
                                <div class="sisfour-record-card">
                                    <div class="d-flex justify-content-between gap-2">
                                        <div>
                                            <div class="sisfour-record-card__title"><?= esc($row['nama_institusi']) ?></div>
                                            <div class="sisfour-record-card__meta">
                                                <?= esc($row['tingkat_pendidikan']) ?> ·
                                                <?= esc($display($row['program_studi'] ?? null)) ?>
                                            </div>
                                        </div>
                                        <span class="badge bg-label-primary align-self-start"><?= esc($row['tahun_lulus']) ?></span>
                                    </div>
                                    <div class="sisfour-record-card__meta mt-2">
                                        No. Ijazah: <?= esc($display($row['no_ijazah'] ?? null)) ?>
                                    </div>
                                    <div class="sisfour-record-card__footer">
                                        <div class="d-flex flex-wrap gap-1">
                                            <?php if (! $canViewDocuments && (! empty($row['file_ijazah']) || ! empty($row['file_transkrip']))): ?>
                                                <span class="badge bg-label-secondary">Dokumen terbatas</span>
                                            <?php else: ?>
                                                <?php if (! empty($row['file_ijazah'])): ?>
                                                    <a class="btn btn-sm btn-outline-secondary"
                                                        href="<?= esc($fileUrl('pendidikan', (int) $row['id'], 'file_ijazah'), 'attr') ?>">Ijazah</a>
                                                <?php endif; ?>
                                                <?php if (! empty($row['file_transkrip'])): ?>
                                                    <a class="btn btn-sm btn-outline-secondary"
                                                        href="<?= esc($fileUrl('pendidikan', (int) $row['id'], 'file_transkrip'), 'attr') ?>">Transkrip</a>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($canEdit): ?>
                                            <div class="sisfour-action-group">
                                                <button class="btn btn-sm btn-outline-primary btn-edit-record"
                                                    data-category="pendidikan"
                                                    data-record="<?= esc($encodeRow($row), 'attr') ?>"><i class="bx bx-edit"></i></button>
                                                <button class="btn btn-sm btn-outline-danger btn-delete-record"
                                                    data-category="pendidikan"
                                                    data-id="<?= (int) $row['id'] ?>"><i class="bx bx-trash"></i></button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="tab-pane fade" id="tabPenugasan" role="tabpanel">
                    <div class="sisfour-section-heading">
                        <div class="sisfour-section-heading__copy">
                            <h5 class="mb-1">Riwayat Penugasan &amp; Jabatan</h5>
                            <p>Tugas, jabatan, mapel, periode, SK, dan dokumen pendukung.</p>
                        </div>
                        <?php if ($canEdit): ?>
                            <button class="btn btn-primary btn-add" data-category="penugasan">
                                <i class="bx bx-plus me-1"></i>Tambah Penugasan
                            </button>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($penugasan)): ?>
                        <div class="sisfour-empty-state">
                            <div class="sisfour-empty-state__icon"><i class="bx bx-briefcase"></i></div>
                            <h6 class="mb-1">Belum ada riwayat penugasan</h6>
                            <p class="text-muted mb-3">Tambahkan tugas atau jabatan beserta periode yang benar.</p>
                            <?php if ($canEdit): ?>
                                <button class="btn btn-sm btn-outline-primary btn-add" data-category="penugasan">
                                    <i class="bx bx-plus me-1"></i>Tambah Penugasan
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive d-none d-lg-block">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Instansi</th><th>Jabatan / Tugas</th><th>Mapel</th>
                                        <th>Periode</th><th>No. SK</th><th>Dokumen</th>
                                        <?php if ($canEdit): ?><th class="text-end">Aksi</th><?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($penugasan as $row): ?>
                                    <tr>
                                        <td><?= esc($row['instansi_penugasan']) ?></td>
                                        <td><strong><?= esc($row['jabatan_tugas']) ?></strong></td>
                                        <td><?= esc($display($row['mata_pelajaran'] ?? null)) ?></td>
                                        <td>
                                            <?= esc($formatDate($row['tanggal_mulai'] ?? null)) ?><br>
                                            <small class="text-muted">
                                                s.d. <?= esc(! empty($row['tanggal_selesai']) ? $formatDate($row['tanggal_selesai']) : 'Sekarang') ?>
                                            </small>
                                        </td>
                                        <td><?= esc($display($row['no_sk_penugasan'] ?? null)) ?></td>
                                        <td>
                                            <?php if (! empty($row['file_sk_penugasan'])): ?>
                                                <?php if ($canViewDocuments): ?>
                                                    <a class="btn btn-sm btn-outline-secondary"
                                                        href="<?= esc($fileUrl('penugasan', (int) $row['id'], 'file_sk_penugasan'), 'attr') ?>">
                                                        <i class="bx bx-file me-1"></i>SK
                                                    </a>
                                                <?php else: ?>
                                                    <span class="badge bg-label-secondary">Terbatas</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <?php if ($canEdit): ?>
                                            <td class="text-end">
                                                <div class="sisfour-action-group">
                                                    <button class="btn btn-sm btn-outline-primary btn-edit-record"
                                                        data-category="penugasan"
                                                        data-record="<?= esc($encodeRow($row), 'attr') ?>"><i class="bx bx-edit"></i></button>
                                                    <button class="btn btn-sm btn-outline-danger btn-delete-record"
                                                        data-category="penugasan"
                                                        data-id="<?= (int) $row['id'] ?>"><i class="bx bx-trash"></i></button>
                                                </div>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="d-lg-none">
                            <?php foreach ($penugasan as $row): ?>
                                <div class="sisfour-record-card">
                                    <div class="sisfour-record-card__title"><?= esc($row['jabatan_tugas']) ?></div>
                                    <div class="sisfour-record-card__meta"><?= esc($row['instansi_penugasan']) ?></div>
                                    <div class="sisfour-record-card__meta">
                                        <?= esc($formatDate($row['tanggal_mulai'] ?? null)) ?> —
                                        <?= esc(! empty($row['tanggal_selesai']) ? $formatDate($row['tanggal_selesai']) : 'Sekarang') ?>
                                    </div>
                                    <?php if (! empty($row['mata_pelajaran'])): ?>
                                        <div class="mt-2"><span class="badge bg-label-info"><?= esc($row['mata_pelajaran']) ?></span></div>
                                    <?php endif; ?>
                                    <div class="sisfour-record-card__footer">
                                        <small class="text-muted">No. SK: <?= esc($display($row['no_sk_penugasan'] ?? null)) ?></small>
                                        <?php if ($canEdit): ?>
                                            <div class="sisfour-action-group">
                                                <button class="btn btn-sm btn-outline-primary btn-edit-record"
                                                    data-category="penugasan"
                                                    data-record="<?= esc($encodeRow($row), 'attr') ?>"><i class="bx bx-edit"></i></button>
                                                <button class="btn btn-sm btn-outline-danger btn-delete-record"
                                                    data-category="penugasan"
                                                    data-id="<?= (int) $row['id'] ?>"><i class="bx bx-trash"></i></button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="tab-pane fade" id="tabPangkat" role="tabpanel">
                    <div class="sisfour-section-heading">
                        <div class="sisfour-section-heading__copy">
                            <h5 class="mb-1">Riwayat Kepangkatan / Golongan</h5>
                            <p>Digunakan untuk ASN/PPPK dan boleh kosong bila tidak relevan.</p>
                        </div>
                        <?php if ($canEdit): ?>
                            <button class="btn btn-primary btn-add" data-category="pangkat">
                                <i class="bx bx-plus me-1"></i>Tambah Pangkat
                            </button>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($pangkat)): ?>
                        <div class="sisfour-empty-state">
                            <div class="sisfour-empty-state__icon"><i class="bx bx-medal"></i></div>
                            <h6 class="mb-1">Belum ada riwayat kepangkatan</h6>
                            <p class="text-muted mb-3">Kosongkan bagian ini bila tidak relevan dengan status kepegawaian.</p>
                            <?php if ($canEdit): ?>
                                <button class="btn btn-sm btn-outline-primary btn-add" data-category="pangkat">
                                    <i class="bx bx-plus me-1"></i>Tambah Pangkat
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive d-none d-lg-block">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Golongan / Ruang</th><th>Nama Pangkat</th>
                                        <th>TMT</th><th>No. SK</th><th>Dokumen</th>
                                        <?php if ($canEdit): ?><th class="text-end">Aksi</th><?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($pangkat as $row): ?>
                                    <tr>
                                        <td><span class="badge bg-label-primary"><?= esc($row['golongan_ruang']) ?></span></td>
                                        <td><?= esc($display($row['nama_pangkat'] ?? null)) ?></td>
                                        <td><?= esc($formatDate($row['tmt_pangkat'] ?? null)) ?></td>
                                        <td><?= esc($display($row['no_sk_pangkat'] ?? null)) ?></td>
                                        <td>
                                            <?php if (! empty($row['file_sk_pangkat'])): ?>
                                                <?php if ($canViewDocuments): ?>
                                                    <a class="btn btn-sm btn-outline-secondary"
                                                        href="<?= esc($fileUrl('pangkat', (int) $row['id'], 'file_sk_pangkat'), 'attr') ?>">
                                                        <i class="bx bx-file me-1"></i>SK
                                                    </a>
                                                <?php else: ?>
                                                    <span class="badge bg-label-secondary">Terbatas</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <?php if ($canEdit): ?>
                                            <td class="text-end">
                                                <div class="sisfour-action-group">
                                                    <button class="btn btn-sm btn-outline-primary btn-edit-record"
                                                        data-category="pangkat"
                                                        data-record="<?= esc($encodeRow($row), 'attr') ?>"><i class="bx bx-edit"></i></button>
                                                    <button class="btn btn-sm btn-outline-danger btn-delete-record"
                                                        data-category="pangkat"
                                                        data-id="<?= (int) $row['id'] ?>"><i class="bx bx-trash"></i></button>
                                                </div>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="d-lg-none">
                            <?php foreach ($pangkat as $row): ?>
                                <div class="sisfour-record-card">
                                    <div class="d-flex justify-content-between gap-2">
                                        <div>
                                            <div class="sisfour-record-card__title"><?= esc($display($row['nama_pangkat'] ?? null)) ?></div>
                                            <div class="sisfour-record-card__meta">
                                                TMT <?= esc($formatDate($row['tmt_pangkat'] ?? null)) ?>
                                            </div>
                                        </div>
                                        <span class="badge bg-label-primary align-self-start"><?= esc($row['golongan_ruang']) ?></span>
                                    </div>
                                    <div class="sisfour-record-card__footer">
                                        <small class="text-muted">No. SK: <?= esc($display($row['no_sk_pangkat'] ?? null)) ?></small>
                                        <?php if ($canEdit): ?>
                                            <div class="sisfour-action-group">
                                                <button class="btn btn-sm btn-outline-primary btn-edit-record"
                                                    data-category="pangkat"
                                                    data-record="<?= esc($encodeRow($row), 'attr') ?>"><i class="bx bx-edit"></i></button>
                                                <button class="btn btn-sm btn-outline-danger btn-delete-record"
                                                    data-category="pangkat"
                                                    data-id="<?= (int) $row['id'] ?>"><i class="bx bx-trash"></i></button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="tab-pane fade" id="tabDokumen" role="tabpanel">
                    <?php if (! $canViewDocuments): ?>
                        <div class="sisfour-empty-state">
                            <div class="sisfour-empty-state__icon"><i class="bx bx-lock-alt"></i></div>
                            <h6 class="mb-1">Dokumen mentah tidak tersedia pada mode readonly</h6>
                            <p class="text-muted mb-0">
                                File personalia hanya dapat dibuka oleh pemilik data dan actor dengan hak kelola Master.
                                Portofolio PDF tetap tersedia.
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="sisfour-section-heading">
                            <div class="sisfour-section-heading__copy">
                                <h5 class="mb-1">Dokumen Personalia</h5>
                                <p>File disimpan non-public dan hanya diakses melalui endpoint berotorisasi.</p>
                            </div>
                            <?php if ($canEdit): ?>
                                <button class="btn btn-primary btn-add" data-category="dokumen">
                                    <i class="bx bx-plus me-1"></i>Tambah Dokumen
                                </button>
                            <?php endif; ?>
                        </div>

                        <?php if (empty($dokumen)): ?>
                            <div class="sisfour-empty-state">
                                <div class="sisfour-empty-state__icon"><i class="bx bx-file"></i></div>
                                <h6 class="mb-1">Belum ada dokumen personalia</h6>
                                <p class="text-muted mb-3">Tambahkan dokumen pendukung sesuai kategori yang tersedia.</p>
                                <?php if ($canEdit): ?>
                                    <button class="btn btn-sm btn-outline-primary btn-add" data-category="dokumen">
                                        <i class="bx bx-plus me-1"></i>Tambah Dokumen
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive d-none d-lg-block">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Dokumen</th><th>Jenis</th><th>Nomor</th>
                                            <th>Tanggal</th><th>File</th>
                                            <?php if ($canEdit): ?><th class="text-end">Aksi</th><?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($dokumen as $row): ?>
                                        <tr>
                                            <td>
                                                <div class="sisfour-file-tile">
                                                    <span class="sisfour-file-tile__icon"><i class="bx bx-file"></i></span>
                                                    <span class="sisfour-file-tile__copy">
                                                        <strong><?= esc($row['nama_dokumen']) ?></strong>
                                                        <small class="text-muted"><?= esc($display($row['nama_file_asli'] ?? null)) ?></small>
                                                    </span>
                                                </div>
                                            </td>
                                            <td><span class="badge bg-label-secondary"><?= esc($row['jenis_dokumen']) ?></span></td>
                                            <td><?= esc($display($row['nomor_dokumen'] ?? null)) ?></td>
                                            <td><?= esc($formatDate($row['tanggal_dokumen'] ?? null)) ?></td>
                                            <td>
                                                <a class="btn btn-sm btn-outline-secondary"
                                                    href="<?= esc($fileUrl('dokumen', (int) $row['id'], 'file_path'), 'attr') ?>">
                                                    <i class="bx bx-download me-1"></i>Buka
                                                </a>
                                            </td>
                                            <?php if ($canEdit): ?>
                                                <td class="text-end">
                                                    <div class="sisfour-action-group">
                                                        <button class="btn btn-sm btn-outline-primary btn-edit-record"
                                                            data-category="dokumen"
                                                            data-record="<?= esc($encodeRow($row), 'attr') ?>"><i class="bx bx-edit"></i></button>
                                                        <button class="btn btn-sm btn-outline-danger btn-delete-record"
                                                            data-category="dokumen"
                                                            data-id="<?= (int) $row['id'] ?>"><i class="bx bx-trash"></i></button>
                                                    </div>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-lg-none">
                                <?php foreach ($dokumen as $row): ?>
                                    <div class="sisfour-record-card">
                                        <div class="sisfour-file-tile">
                                            <span class="sisfour-file-tile__icon"><i class="bx bx-file"></i></span>
                                            <span class="sisfour-file-tile__copy">
                                                <strong><?= esc($row['nama_dokumen']) ?></strong>
                                                <small class="text-muted"><?= esc($row['jenis_dokumen']) ?></small>
                                            </span>
                                        </div>
                                        <div class="sisfour-record-card__meta mt-2">
                                            Nomor: <?= esc($display($row['nomor_dokumen'] ?? null)) ?> ·
                                            <?= esc($formatDate($row['tanggal_dokumen'] ?? null)) ?>
                                        </div>
                                        <div class="sisfour-record-card__footer">
                                            <a class="btn btn-sm btn-outline-secondary"
                                                href="<?= esc($fileUrl('dokumen', (int) $row['id'], 'file_path'), 'attr') ?>">
                                                <i class="bx bx-download me-1"></i>Buka File
                                            </a>
                                            <?php if ($canEdit): ?>
                                                <div class="sisfour-action-group">
                                                    <button class="btn btn-sm btn-outline-primary btn-edit-record"
                                                        data-category="dokumen"
                                                        data-record="<?= esc($encodeRow($row), 'attr') ?>"><i class="bx bx-edit"></i></button>
                                                    <button class="btn btn-sm btn-outline-danger btn-delete-record"
                                                        data-category="dokumen"
                                                        data-id="<?= (int) $row['id'] ?>"><i class="bx bx-trash"></i></button>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="tab-pane fade" id="tabPortofolio" role="tabpanel">
                    <div class="row g-4 align-items-center">
                        <div class="col-12 col-lg-7">
                            <span class="badge bg-label-primary mb-3">PDF Dinamis</span>
                            <h5 class="mb-2">Portofolio Personalia <?= esc($ownerLabel) ?></h5>
                            <p class="text-muted">
                                Portofolio selalu dibuat dari biodata dan riwayat terbaru saat diminta.
                                Tidak ada snapshot atau salinan data terpisah.
                            </p>

                            <div class="sisfour-info-grid">
                                <div class="sisfour-info-item">
                                    <span class="sisfour-info-item__label">Pendidikan</span>
                                    <div class="sisfour-info-item__value"><?= $pendidikanCount ?> record</div>
                                </div>
                                <div class="sisfour-info-item">
                                    <span class="sisfour-info-item__label">Penugasan</span>
                                    <div class="sisfour-info-item__value"><?= $penugasanCount ?> record</div>
                                </div>
                                <div class="sisfour-info-item">
                                    <span class="sisfour-info-item__label">Kepangkatan</span>
                                    <div class="sisfour-info-item__value"><?= $pangkatCount ?> record</div>
                                </div>
                                <div class="sisfour-info-item">
                                    <span class="sisfour-info-item__label">Sumber Data</span>
                                    <div class="sisfour-info-item__value">Data personalia terbaru</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-lg-5">
                            <div class="card border shadow-none">
                                <div class="card-body text-center">
                                    <div class="sisfour-empty-state__icon mb-2"><i class="bx bx-file-blank"></i></div>
                                    <h6 class="mb-1">Portofolio PDF</h6>
                                    <div class="small text-muted mb-3">Siap dipreview atau diunduh.</div>
                                    <div class="d-grid gap-2">
                                        <a href="<?= esc($portfolioUrl ?? '#', 'attr') ?>" target="_blank" rel="noopener"
                                            class="btn btn-outline-primary">
                                            <i class="bx bx-show me-1"></i>Preview PDF
                                        </a>
                                        <a href="<?= esc(($portfolioUrl ?? '#') . '?download=1', 'attr') ?>" class="btn btn-primary">
                                            <i class="bx bx-download me-1"></i>Download PDF
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($canEdit): ?>
            <div class="modal fade" id="modalPendidikan" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <form class="personalia-form" data-category="pendidikan">
                            <?= csrf_field() ?>
                            <div class="modal-header">
                                <div>
                                    <h5 class="modal-title mb-1">Riwayat Pendidikan</h5>
                                    <div class="small text-muted">Pendidikan formal dan dokumen pendukung.</div>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="id">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Tingkat Pendidikan *</label>
                                        <input class="form-control" name="tingkat_pendidikan" maxlength="20"
                                            placeholder="S1 / S2 / D4 / ..." required>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label">Institusi *</label>
                                        <input class="form-control" name="nama_institusi" maxlength="150" required>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label">Program Studi</label>
                                        <input class="form-control" name="program_studi" maxlength="150">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Tahun Lulus *</label>
                                        <input type="number" class="form-control" name="tahun_lulus" min="1950" max="2100" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">No. Ijazah</label>
                                        <input class="form-control" name="no_ijazah" maxlength="100">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">File Ijazah</label>
                                        <input type="file" class="form-control" name="file_ijazah"
                                            accept="application/pdf,image/png,image/jpeg">
                                        <div class="form-text">PDF/PNG/JPG maks. 5 MB. Kosongkan saat edit untuk mempertahankan file lama.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">File Transkrip</label>
                                        <input type="file" class="form-control" name="file_transkrip"
                                            accept="application/pdf,image/png,image/jpeg">
                                        <div class="form-text">PDF/PNG/JPG maks. 5 MB.</div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary">
                                    <span class="spinner-border spinner-border-sm d-none me-1"></span>Simpan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="modalPenugasan" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <form class="personalia-form" data-category="penugasan">
                            <?= csrf_field() ?>
                            <div class="modal-header">
                                <div>
                                    <h5 class="modal-title mb-1">Riwayat Penugasan / Jabatan</h5>
                                    <div class="small text-muted">Catat tugas, jabatan, periode, dan SK secara faktual.</div>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="id">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label">Instansi / Sekolah *</label>
                                        <input class="form-control" name="instansi_penugasan" maxlength="150" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Jabatan / Tugas *</label>
                                        <input class="form-control" name="jabatan_tugas" maxlength="120"
                                            placeholder="Guru Mapel, Wali Kelas, Tenaga Administrasi, ..." required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Mata Pelajaran</label>
                                        <input class="form-control" name="mata_pelajaran" maxlength="120">
                                        <div class="form-text">Opsional; kosongkan untuk Pegawai/non-mapel.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Tanggal Mulai *</label>
                                        <input type="date" class="form-control" name="tanggal_mulai" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Tanggal Selesai</label>
                                        <input type="date" class="form-control" name="tanggal_selesai">
                                        <div class="form-text">Kosong = masih menjabat/bertugas.</div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">No. SK Penugasan</label>
                                        <input class="form-control" name="no_sk_penugasan" maxlength="100">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">File SK / Pembagian Tugas</label>
                                        <input type="file" class="form-control" name="file_sk_penugasan"
                                            accept="application/pdf,image/png,image/jpeg">
                                        <div class="form-text">PDF/PNG/JPG maks. 5 MB.</div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary">
                                    <span class="spinner-border spinner-border-sm d-none me-1"></span>Simpan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="modalPangkat" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <form class="personalia-form" data-category="pangkat">
                            <?= csrf_field() ?>
                            <div class="modal-header">
                                <div>
                                    <h5 class="modal-title mb-1">Riwayat Kepangkatan / Golongan</h5>
                                    <div class="small text-muted">Catat pangkat/golongan dan TMT sesuai dokumen resmi.</div>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="id">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Golongan / Ruang *</label>
                                        <input class="form-control" name="golongan_ruang" maxlength="30"
                                            placeholder="III/a, IV/a, IX, ..." required>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label">Nama Pangkat</label>
                                        <input class="form-control" name="nama_pangkat" maxlength="100"
                                            placeholder="Penata, Pembina, ...">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">TMT Pangkat *</label>
                                        <input type="date" class="form-control" name="tmt_pangkat" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">No. SK Pangkat</label>
                                        <input class="form-control" name="no_sk_pangkat" maxlength="100">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">File SK Pangkat</label>
                                        <input type="file" class="form-control" name="file_sk_pangkat"
                                            accept="application/pdf,image/png,image/jpeg">
                                        <div class="form-text">PDF/PNG/JPG maks. 5 MB.</div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary">
                                    <span class="spinner-border spinner-border-sm d-none me-1"></span>Simpan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="modalDokumen" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <form class="personalia-form" data-category="dokumen">
                            <?= csrf_field() ?>
                            <div class="modal-header">
                                <div>
                                    <h5 class="modal-title mb-1">Dokumen Personalia</h5>
                                    <div class="small text-muted">Simpan dokumen pendukung pada penyimpanan non-public.</div>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="id">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Jenis Dokumen *</label>
                                        <select class="form-select" name="jenis_dokumen" required data-searchable-off="1">
                                            <option value="">Pilih</option>
                                            <?php foreach (($document_types ?? []) as $jenis): ?>
                                                <option value="<?= esc($jenis, 'attr') ?>"><?= esc($jenis) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Nama Dokumen *</label>
                                        <input class="form-control" name="nama_dokumen" maxlength="150" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Nomor Dokumen</label>
                                        <input class="form-control" name="nomor_dokumen" maxlength="100">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Tanggal Dokumen</label>
                                        <input type="date" class="form-control" name="tanggal_dokumen">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">
                                            File Dokumen <span class="new-file-required text-danger">*</span>
                                        </label>
                                        <input type="file" class="form-control" name="file_dokumen"
                                            accept="application/pdf,image/png,image/jpeg">
                                        <div class="form-text">
                                            PDF/PNG/JPG maks. 5 MB. Saat edit boleh dikosongkan untuk mempertahankan file lama.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary">
                                    <span class="spinner-border spinner-border-sm d-none me-1"></span>Simpan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?= $this->endSection() ?>
