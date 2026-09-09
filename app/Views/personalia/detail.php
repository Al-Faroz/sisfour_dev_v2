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
    $canEdit = ! empty($can_edit);
    $canViewDocuments = ! empty($can_view_documents);
    $isSelf = ! empty($is_self);
    $encodeRow = static function (array $row): string {
        return rawurlencode((string) json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    };
    $fileUrl = static function (string $category, int $id, string $field): string {
        return base_url('personalia/file/' . $category . '/' . $id . '/' . $field);
    };
    $gender = ($owner['jenis_kelamin'] ?? '') === 'P' ? 'Perempuan' : (($owner['jenis_kelamin'] ?? '') === 'L' ? 'Laki-laki' : '-');
    ?>

    <div
        id="personaliaApp"
        data-base-url="<?= esc(base_url(), 'attr') ?>"
        data-endpoint-base="<?= esc($endpointBase ?? '', 'attr') ?>"
        data-can-edit="<?= $canEdit ? '1' : '0' ?>"
        data-owner-type="<?= esc($ownerType, 'attr') ?>"
        data-owner-id="<?= (int) ($owner_id ?? 0) ?>"
    >
        <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3 mb-4">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <a href="<?= esc($backUrl ?? base_url(), 'attr') ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="bx bx-arrow-back me-1"></i><?= esc($backLabel ?? 'Kembali') ?>
                    </a>
                    <?php if ($isSelf): ?>
                        <span class="badge bg-label-primary">Data Saya</span>
                    <?php elseif ($canEdit): ?>
                        <span class="badge bg-label-warning">Mode Kelola</span>
                    <?php else: ?>
                        <span class="badge bg-label-info">Readonly</span>
                    <?php endif; ?>
                </div>
                <h4 class="fw-bold mb-1">Riwayat Personalia <?= esc(ucfirst($ownerType)) ?></h4>
                <p class="text-muted mb-0"><?= esc($owner['nama'] ?? '-') ?></p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= esc($portfolioUrl ?? '#', 'attr') ?>" target="_blank" class="btn btn-outline-primary">
                    <i class="bx bx-show me-1"></i> Preview Portofolio
                </a>
                <a href="<?= esc(($portfolioUrl ?? '#') . '?download=1', 'attr') ?>" class="btn btn-primary">
                    <i class="bx bx-download me-1"></i> Download PDF
                </a>
            </div>
        </div>

        <?php if ($ownerType === 'pegawai' && ! empty($owner['jabatan_legacy']) && empty($penugasan)): ?>
            <div class="alert alert-secondary">
                <i class="bx bx-briefcase me-1"></i>
                Jabatan legacy masih tersimpan: <strong><?= esc($owner['jabatan_legacy']) ?></strong>.
                Tambahkan Riwayat Penugasan dengan periode/SK yang benar; sistem tidak menebak tanggal legacy.
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header border-bottom pb-0">
                <ul class="nav nav-tabs card-header-tabs" role="tablist">
                    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabBiodata" type="button">Biodata</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabPendidikan" type="button">Pendidikan</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabPenugasan" type="button">Penugasan / Jabatan</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabPangkat" type="button">Kepangkatan</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabDokumen" type="button">Dokumen</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabPortofolio" type="button">Portofolio</button></li>
                </ul>
            </div>

            <div class="card-body pt-4 tab-content">
                <div class="tab-pane fade show active" id="tabBiodata">
                    <div class="row g-4">
                        <div class="col-12 col-lg-3 text-center">
                            <?php
                            $foto = trim((string) ($owner['foto'] ?? ''));
                            $fotoFolder = $ownerType === 'guru' ? 'uploads/foto_guru/' : 'uploads/foto_pegawai/';
                            $fotoUrl = $foto !== '' ? base_url($fotoFolder . rawurlencode(basename($foto))) : '';
                            ?>
                            <?php if ($fotoUrl !== ''): ?>
                                <img src="<?= esc($fotoUrl, 'attr') ?>" alt="Foto" width="150" height="200" class="rounded object-fit-cover border">
                            <?php else: ?>
                                <div class="d-inline-flex align-items-center justify-content-center rounded bg-label-secondary" style="width:150px;height:200px;font-size:56px"><i class="bx bx-user"></i></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-12 col-lg-9">
                            <div class="row g-3">
                                <div class="col-md-6"><small class="text-muted">Nama Lengkap & Gelar</small><div class="fw-semibold"><?= esc($owner['nama'] ?? '-') ?></div></div>
                                <div class="col-md-3"><small class="text-muted">NIK</small><div><?= esc($owner['nik'] ?: '-') ?></div></div>
                                <div class="col-md-3"><small class="text-muted">NIP</small><div><?= esc($owner['nip'] ?: '-') ?></div></div>
                                <div class="col-md-4"><small class="text-muted">Status Kepegawaian</small><div><?= esc($owner['status_kepegawaian'] ?: '-') ?></div></div>
                                <div class="col-md-4"><small class="text-muted">NUPTK</small><div><?= esc($owner['nuptk'] ?: '-') ?></div></div>
                                <div class="col-md-4"><small class="text-muted">Jenis Kelamin</small><div><?= esc($gender) ?></div></div>
                                <div class="col-md-4"><small class="text-muted">Tempat Lahir</small><div><?= esc($owner['tempat_lahir'] ?: '-') ?></div></div>
                                <div class="col-md-4"><small class="text-muted">Tanggal Lahir</small><div><?= esc($owner['tanggal_lahir'] ?: '-') ?></div></div>
                                <div class="col-md-4"><small class="text-muted">Agama</small><div><?= esc($owner['agama'] ?: '-') ?></div></div>
                                <div class="col-md-6"><small class="text-muted">Telepon</small><div><?= esc($owner['no_telepon'] ?: '-') ?></div></div>
                                <div class="col-md-6"><small class="text-muted">Email</small><div><?= esc($owner['email'] ?: '-') ?></div></div>
                                <div class="col-12"><small class="text-muted">Alamat</small><div><?= nl2br(esc($owner['alamat'] ?: '-')) ?></div></div>
                            </div>
                            <?php if ($isSelf): ?>
                                <div class="mt-4">
                                    <a href="<?= esc($backUrl ?? '#', 'attr') ?>" class="btn btn-outline-primary"><i class="bx bx-edit me-1"></i>Edit Biodata di Profile</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tabPendidikan">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div><h5 class="mb-1">Riwayat Pendidikan Formal</h5><p class="text-muted mb-0">Ijazah dan transkrip melekat pada record pendidikan.</p></div>
                        <?php if ($canEdit): ?><button class="btn btn-primary btn-add" data-category="pendidikan"><i class="bx bx-plus me-1"></i>Tambah</button><?php endif; ?>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead><tr><th>Tingkat</th><th>Institusi / Program Studi</th><th>Tahun</th><th>No. Ijazah</th><th>Dokumen</th><th>Aksi</th></tr></thead>
                            <tbody>
                            <?php if (empty($pendidikan)): ?>
                                <tr><td colspan="6" class="text-center text-muted py-4">Belum ada riwayat pendidikan.</td></tr>
                            <?php else: foreach ($pendidikan as $row): ?>
                                <tr>
                                    <td><strong><?= esc($row['tingkat_pendidikan']) ?></strong></td>
                                    <td><?= esc($row['nama_institusi']) ?><br><small class="text-muted"><?= esc($row['program_studi'] ?: '-') ?></small></td>
                                    <td><?= esc($row['tahun_lulus']) ?></td>
                                    <td><?= esc($row['no_ijazah'] ?: '-') ?></td>
                                    <td class="text-nowrap">
                                        <?php if (! $canViewDocuments && (! empty($row['file_ijazah']) || ! empty($row['file_transkrip']))): ?>
                                            <span class="badge bg-label-secondary">Terbatas</span>
                                        <?php else: ?>
                                            <?php if (! empty($row['file_ijazah'])): ?><a class="btn btn-sm btn-outline-secondary" href="<?= esc($fileUrl('pendidikan', (int) $row['id'], 'file_ijazah'), 'attr') ?>"><i class="bx bx-file"></i> Ijazah</a><?php endif; ?>
                                            <?php if (! empty($row['file_transkrip'])): ?><a class="btn btn-sm btn-outline-secondary" href="<?= esc($fileUrl('pendidikan', (int) $row['id'], 'file_transkrip'), 'attr') ?>"><i class="bx bx-file"></i> Transkrip</a><?php endif; ?>
                                            <?php if (empty($row['file_ijazah']) && empty($row['file_transkrip'])): ?>-<?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-nowrap">
                                        <?php if ($canEdit): ?>
                                            <button class="btn btn-sm btn-outline-primary btn-edit-record" data-category="pendidikan" data-record="<?= esc($encodeRow($row), 'attr') ?>"><i class="bx bx-edit"></i></button>
                                            <button class="btn btn-sm btn-outline-danger btn-delete-record" data-category="pendidikan" data-id="<?= (int) $row['id'] ?>"><i class="bx bx-trash"></i></button>
                                        <?php else: ?><span class="text-muted">Readonly</span><?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="tabPenugasan">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div><h5 class="mb-1">Riwayat Penugasan & Jabatan</h5><p class="text-muted mb-0">Mapel bersifat opsional agar struktur yang sama dapat dipakai Guru dan Pegawai.</p></div>
                        <?php if ($canEdit): ?><button class="btn btn-primary btn-add" data-category="penugasan"><i class="bx bx-plus me-1"></i>Tambah</button><?php endif; ?>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead><tr><th>Instansi</th><th>Jabatan / Tugas</th><th>Mapel</th><th>Periode</th><th>No. SK</th><th>Dokumen</th><th>Aksi</th></tr></thead>
                            <tbody>
                            <?php if (empty($penugasan)): ?>
                                <tr><td colspan="7" class="text-center text-muted py-4">Belum ada riwayat penugasan.</td></tr>
                            <?php else: foreach ($penugasan as $row): ?>
                                <tr>
                                    <td><?= esc($row['instansi_penugasan']) ?></td>
                                    <td><strong><?= esc($row['jabatan_tugas']) ?></strong></td>
                                    <td><?= esc($row['mata_pelajaran'] ?: '-') ?></td>
                                    <td><?= esc($row['tanggal_mulai']) ?><br><small class="text-muted">s.d. <?= esc($row['tanggal_selesai'] ?: 'Sekarang') ?></small></td>
                                    <td><?= esc($row['no_sk_penugasan'] ?: '-') ?></td>
                                    <td><?php if (! empty($row['file_sk_penugasan'])): ?><?php if ($canViewDocuments): ?><a class="btn btn-sm btn-outline-secondary" href="<?= esc($fileUrl('penugasan', (int) $row['id'], 'file_sk_penugasan'), 'attr') ?>"><i class="bx bx-file"></i> SK</a><?php else: ?><span class="badge bg-label-secondary">Terbatas</span><?php endif; ?><?php else: ?>-<?php endif; ?></td>
                                    <td class="text-nowrap">
                                        <?php if ($canEdit): ?>
                                            <button class="btn btn-sm btn-outline-primary btn-edit-record" data-category="penugasan" data-record="<?= esc($encodeRow($row), 'attr') ?>"><i class="bx bx-edit"></i></button>
                                            <button class="btn btn-sm btn-outline-danger btn-delete-record" data-category="penugasan" data-id="<?= (int) $row['id'] ?>"><i class="bx bx-trash"></i></button>
                                        <?php else: ?><span class="text-muted">Readonly</span><?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="tabPangkat">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div><h5 class="mb-1">Riwayat Kepangkatan / Golongan</h5><p class="text-muted mb-0">Dapat digunakan ASN/PPPK; kosongkan bagian ini bila tidak relevan.</p></div>
                        <?php if ($canEdit): ?><button class="btn btn-primary btn-add" data-category="pangkat"><i class="bx bx-plus me-1"></i>Tambah</button><?php endif; ?>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead><tr><th>Golongan / Ruang</th><th>Nama Pangkat</th><th>TMT</th><th>No. SK</th><th>Dokumen</th><th>Aksi</th></tr></thead>
                            <tbody>
                            <?php if (empty($pangkat)): ?>
                                <tr><td colspan="6" class="text-center text-muted py-4">Belum ada riwayat kepangkatan.</td></tr>
                            <?php else: foreach ($pangkat as $row): ?>
                                <tr>
                                    <td><strong><?= esc($row['golongan_ruang']) ?></strong></td>
                                    <td><?= esc($row['nama_pangkat'] ?: '-') ?></td>
                                    <td><?= esc($row['tmt_pangkat']) ?></td>
                                    <td><?= esc($row['no_sk_pangkat'] ?: '-') ?></td>
                                    <td><?php if (! empty($row['file_sk_pangkat'])): ?><?php if ($canViewDocuments): ?><a class="btn btn-sm btn-outline-secondary" href="<?= esc($fileUrl('pangkat', (int) $row['id'], 'file_sk_pangkat'), 'attr') ?>"><i class="bx bx-file"></i> SK</a><?php else: ?><span class="badge bg-label-secondary">Terbatas</span><?php endif; ?><?php else: ?>-<?php endif; ?></td>
                                    <td class="text-nowrap">
                                        <?php if ($canEdit): ?>
                                            <button class="btn btn-sm btn-outline-primary btn-edit-record" data-category="pangkat" data-record="<?= esc($encodeRow($row), 'attr') ?>"><i class="bx bx-edit"></i></button>
                                            <button class="btn btn-sm btn-outline-danger btn-delete-record" data-category="pangkat" data-id="<?= (int) $row['id'] ?>"><i class="bx bx-trash"></i></button>
                                        <?php else: ?><span class="text-muted">Readonly</span><?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="tabDokumen">
                    <?php if (! $canViewDocuments): ?>
                        <div class="alert alert-info mb-0">
                            <i class="bx bx-lock-alt me-1"></i>Dokumen personalia mentah hanya dapat dibuka oleh pemilik data dan actor dengan hak kelola Master. Portofolio tetap tersedia dalam mode readonly.
                        </div>
                    <?php else: ?>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div><h5 class="mb-1">Dokumen Personalia</h5><p class="text-muted mb-0">Dokumen disimpan non-public dan hanya dilayani melalui endpoint berotorisasi.</p></div>
                        <?php if ($canEdit): ?><button class="btn btn-primary btn-add" data-category="dokumen"><i class="bx bx-plus me-1"></i>Tambah</button><?php endif; ?>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead><tr><th>Jenis</th><th>Nama Dokumen</th><th>Nomor</th><th>Tanggal</th><th>File</th><th>Aksi</th></tr></thead>
                            <tbody>
                            <?php if (empty($dokumen)): ?>
                                <tr><td colspan="6" class="text-center text-muted py-4">Belum ada dokumen personalia.</td></tr>
                            <?php else: foreach ($dokumen as $row): ?>
                                <tr>
                                    <td><?= esc($row['jenis_dokumen']) ?></td>
                                    <td><strong><?= esc($row['nama_dokumen']) ?></strong></td>
                                    <td><?= esc($row['nomor_dokumen'] ?: '-') ?></td>
                                    <td><?= esc($row['tanggal_dokumen'] ?: '-') ?></td>
                                    <td><a class="btn btn-sm btn-outline-secondary" href="<?= esc($fileUrl('dokumen', (int) $row['id'], 'file_path'), 'attr') ?>"><i class="bx bx-download me-1"></i><?= esc($row['nama_file_asli'] ?: 'Dokumen') ?></a></td>
                                    <td class="text-nowrap">
                                        <?php if ($canEdit): ?>
                                            <button class="btn btn-sm btn-outline-primary btn-edit-record" data-category="dokumen" data-record="<?= esc($encodeRow($row), 'attr') ?>"><i class="bx bx-edit"></i></button>
                                            <button class="btn btn-sm btn-outline-danger btn-delete-record" data-category="dokumen" data-id="<?= (int) $row['id'] ?>"><i class="bx bx-trash"></i></button>
                                        <?php else: ?><span class="text-muted">Readonly</span><?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="tab-pane fade" id="tabPortofolio">
                    <div class="row g-4 align-items-center">
                        <div class="col-12 col-lg-8">
                            <h5>Portofolio Personalia</h5>
                            <p class="text-muted mb-2">PDF dibuat saat diminta dari biodata dan riwayat terbaru. Tidak ada snapshot Portofolio terpisah.</p>
                            <ul class="mb-0">
                                <li>Data Identitas Pribadi</li>
                                <li>Riwayat Pendidikan Formal</li>
                                <li>Riwayat Penugasan & Jabatan</li>
                                <li>Riwayat Kepangkatan</li>
                            </ul>
                        </div>
                        <div class="col-12 col-lg-4 d-grid gap-2">
                            <a href="<?= esc($portfolioUrl ?? '#', 'attr') ?>" target="_blank" class="btn btn-outline-primary btn-lg"><i class="bx bx-show me-1"></i>Preview PDF</a>
                            <a href="<?= esc(($portfolioUrl ?? '#') . '?download=1', 'attr') ?>" class="btn btn-primary btn-lg"><i class="bx bx-download me-1"></i>Download PDF</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($canEdit): ?>
            <div class="modal fade" id="modalPendidikan" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content"><form class="personalia-form" data-category="pendidikan">
                    <?= csrf_field() ?><div class="modal-header"><h5 class="modal-title">Riwayat Pendidikan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body"><input type="hidden" name="id"><div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Tingkat Pendidikan *</label><input class="form-control" name="tingkat_pendidikan" maxlength="20" placeholder="S1 / S2 / D4 / ..." required></div>
                        <div class="col-md-8"><label class="form-label">Institusi *</label><input class="form-control" name="nama_institusi" maxlength="150" required></div>
                        <div class="col-md-8"><label class="form-label">Program Studi</label><input class="form-control" name="program_studi" maxlength="150"></div>
                        <div class="col-md-4"><label class="form-label">Tahun Lulus *</label><input type="number" class="form-control" name="tahun_lulus" min="1950" max="2100" required></div>
                        <div class="col-12"><label class="form-label">No. Ijazah</label><input class="form-control" name="no_ijazah" maxlength="100"></div>
                        <div class="col-md-6"><label class="form-label">File Ijazah</label><input type="file" class="form-control" name="file_ijazah" accept="application/pdf,image/png,image/jpeg"><div class="form-text">PDF/PNG/JPG maks. 5 MB. Kosongkan saat edit untuk mempertahankan file lama.</div></div>
                        <div class="col-md-6"><label class="form-label">File Transkrip</label><input type="file" class="form-control" name="file_transkrip" accept="application/pdf,image/png,image/jpeg"><div class="form-text">PDF/PNG/JPG maks. 5 MB.</div></div>
                    </div></div>
                    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary"><span class="spinner-border spinner-border-sm d-none me-1"></span>Simpan</button></div>
                </form></div></div>
            </div>

            <div class="modal fade" id="modalPenugasan" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content"><form class="personalia-form" data-category="penugasan">
                    <?= csrf_field() ?><div class="modal-header"><h5 class="modal-title">Riwayat Penugasan / Jabatan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body"><input type="hidden" name="id"><div class="row g-3">
                        <div class="col-12"><label class="form-label">Instansi / Sekolah *</label><input class="form-control" name="instansi_penugasan" maxlength="150" required></div>
                        <div class="col-md-6"><label class="form-label">Jabatan / Tugas *</label><input class="form-control" name="jabatan_tugas" maxlength="120" placeholder="Guru Mapel, Wali Kelas, Tenaga Administrasi, ..." required></div>
                        <div class="col-md-6"><label class="form-label">Mata Pelajaran</label><input class="form-control" name="mata_pelajaran" maxlength="120"><div class="form-text">Opsional; kosongkan untuk Pegawai/non-mapel.</div></div>
                        <div class="col-md-6"><label class="form-label">Tanggal Mulai *</label><input type="date" class="form-control" name="tanggal_mulai" required></div>
                        <div class="col-md-6"><label class="form-label">Tanggal Selesai</label><input type="date" class="form-control" name="tanggal_selesai"><div class="form-text">Kosong = masih menjabat/bertugas.</div></div>
                        <div class="col-12"><label class="form-label">No. SK Penugasan</label><input class="form-control" name="no_sk_penugasan" maxlength="100"></div>
                        <div class="col-12"><label class="form-label">File SK / Pembagian Tugas</label><input type="file" class="form-control" name="file_sk_penugasan" accept="application/pdf,image/png,image/jpeg"><div class="form-text">PDF/PNG/JPG maks. 5 MB.</div></div>
                    </div></div>
                    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary"><span class="spinner-border spinner-border-sm d-none me-1"></span>Simpan</button></div>
                </form></div></div>
            </div>

            <div class="modal fade" id="modalPangkat" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content"><form class="personalia-form" data-category="pangkat">
                    <?= csrf_field() ?><div class="modal-header"><h5 class="modal-title">Riwayat Kepangkatan / Golongan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body"><input type="hidden" name="id"><div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Golongan / Ruang *</label><input class="form-control" name="golongan_ruang" maxlength="30" placeholder="III/a, IV/a, IX, ..." required></div>
                        <div class="col-md-8"><label class="form-label">Nama Pangkat</label><input class="form-control" name="nama_pangkat" maxlength="100" placeholder="Penata, Pembina, ..."></div>
                        <div class="col-md-6"><label class="form-label">TMT Pangkat *</label><input type="date" class="form-control" name="tmt_pangkat" required></div>
                        <div class="col-md-6"><label class="form-label">No. SK Pangkat</label><input class="form-control" name="no_sk_pangkat" maxlength="100"></div>
                        <div class="col-12"><label class="form-label">File SK Pangkat</label><input type="file" class="form-control" name="file_sk_pangkat" accept="application/pdf,image/png,image/jpeg"><div class="form-text">PDF/PNG/JPG maks. 5 MB.</div></div>
                    </div></div>
                    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary"><span class="spinner-border spinner-border-sm d-none me-1"></span>Simpan</button></div>
                </form></div></div>
            </div>

            <div class="modal fade" id="modalDokumen" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content"><form class="personalia-form" data-category="dokumen">
                    <?= csrf_field() ?><div class="modal-header"><h5 class="modal-title">Dokumen Personalia</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body"><input type="hidden" name="id"><div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Jenis Dokumen *</label><select class="form-select" name="jenis_dokumen" required data-searchable-off="1"><option value="">Pilih</option><?php foreach (($document_types ?? []) as $jenis): ?><option value="<?= esc($jenis, 'attr') ?>"><?= esc($jenis) ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-6"><label class="form-label">Nama Dokumen *</label><input class="form-control" name="nama_dokumen" maxlength="150" required></div>
                        <div class="col-md-6"><label class="form-label">Nomor Dokumen</label><input class="form-control" name="nomor_dokumen" maxlength="100"></div>
                        <div class="col-md-6"><label class="form-label">Tanggal Dokumen</label><input type="date" class="form-control" name="tanggal_dokumen"></div>
                        <div class="col-12"><label class="form-label">File Dokumen <span class="new-file-required text-danger">*</span></label><input type="file" class="form-control" name="file_dokumen" accept="application/pdf,image/png,image/jpeg"><div class="form-text">PDF/PNG/JPG maks. 5 MB. Saat edit boleh dikosongkan untuk mempertahankan file lama.</div></div>
                    </div></div>
                    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary"><span class="spinner-border spinner-border-sm d-none me-1"></span>Simpan</button></div>
                </form></div></div>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?= $this->endSection() ?>
