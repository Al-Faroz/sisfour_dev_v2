<?= $this->extend('main') ?>

<?= $this->section('content') ?>

<div id="profileGuruApp" data-base-url="<?= esc(base_url(), 'attr') ?>">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
        <div>
            <div class="text-muted small mb-1">Profile Saya</div>
            <h4 class="fw-bold mb-1">Profile Guru</h4>
            <p class="text-muted mb-0">
                Kelola biodata pribadi, foto, kontak, serta akses Riwayat &amp; Portofolio.
            </p>
        </div>
        <a href="<?= esc(base_url('profile/guru/personalia'), 'attr') ?>" class="btn btn-outline-primary">
            <i class="bx bx-folder-open me-1"></i>Riwayat &amp; Portofolio
        </a>
    </div>

    <?php if (! empty($profileError)): ?>
        <div class="alert alert-warning">
            <i class="bx bx-error-circle me-1"></i><?= esc($profileError) ?>
        </div>
    <?php elseif (! empty($profile)): ?>
        <?php
        $foto = trim((string) ($profile['foto'] ?? ''));
        $fotoUrl = $foto !== ''
            ? base_url('uploads/foto_guru/' . rawurlencode(basename($foto)))
            : '';
        $genderLabel = ($profile['jenis_kelamin'] ?? '') === 'P'
            ? 'Perempuan'
            : (($profile['jenis_kelamin'] ?? '') === 'L' ? 'Laki-laki' : '-');
        ?>

        <?php if (empty($profile['identity_complete'])): ?>
            <div class="alert alert-warning d-flex align-items-start gap-2">
                <i class="bx bx-info-circle fs-5 mt-1"></i>
                <div>
                    <strong>Identitas administratif belum lengkap.</strong>
                    <div class="small mt-1">
                        NIK belum valid/lengkap. Hubungi Admin atau Operator untuk memperbarui
                        data pada Master Guru.
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="card sisfour-profile-hero mb-4">
            <div class="card-body">
                <div class="sisfour-profile-hero__body">
                    <div>
                        <img
                            src="<?= esc($fotoUrl, 'attr') ?>"
                            alt="Foto <?= esc($profile['nama'] ?? 'Guru', 'attr') ?>"
                            class="sisfour-profile-photo sisfour-profile-photo--lg <?= $fotoUrl === '' ? 'd-none' : '' ?>"
                            id="profileGuruFoto"
                        >
                        <div
                            class="sisfour-profile-photo sisfour-profile-photo--lg sisfour-profile-photo__fallback <?= $fotoUrl !== '' ? 'd-none' : '' ?>"
                            id="profileGuruFotoFallback"
                            aria-label="Foto belum tersedia"
                        >
                            <i class="bx bx-user"></i>
                        </div>
                    </div>

                    <div class="sisfour-profile-identity">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                            <span class="badge bg-label-primary">Guru</span>
                            <span class="badge bg-label-secondary">
                                <?= esc($profile['status_kepegawaian'] ?: 'Status belum diisi') ?>
                            </span>
                        </div>

                        <h3 class="sisfour-profile-name"><?= esc($profile['nama'] ?? '-') ?></h3>

                        <div class="sisfour-meta-list">
                            <span class="sisfour-meta-list__item">
                                <i class="bx bx-id-card"></i>
                                Login: <?= esc($profile['login_identifier'] ?: '-') ?>
                            </span>
                            <span class="sisfour-meta-list__item">
                                <i class="bx bx-user-pin"></i><?= esc($genderLabel) ?>
                            </span>
                            <?php if (! empty($profile['email'])): ?>
                                <span class="sisfour-meta-list__item">
                                    <i class="bx bx-envelope"></i><?= esc($profile['email']) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mt-3 sisfour-profile-hero__actions">
                            <a href="<?= esc(base_url('profile/guru/personalia'), 'attr') ?>" class="btn btn-primary">
                                <i class="bx bx-folder-open me-1"></i>Buka Personalia
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-xl-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-1">Foto Profile</h5>
                        <div class="small text-muted">Foto resmi rasio 3:4.</div>
                    </div>
                    <div class="card-body">
                        <form id="formFotoProfileGuru">
                            <?= csrf_field() ?>
                            <label for="fotoProfileGuru" class="form-label">Pilih Foto Baru</label>
                            <input
                                type="file"
                                class="form-control"
                                id="fotoProfileGuru"
                                name="foto"
                                accept="image/png"
                                required
                            >
                            <div class="form-text">
                                PNG maksimal 2 MB. Sistem melakukan crop otomatis rasio 3:4.
                            </div>
                            <button type="submit" class="btn btn-outline-primary w-100 mt-3">
                                <span class="spinner-border spinner-border-sm d-none me-1" aria-hidden="true"></span>
                                Upload Foto
                            </button>
                        </form>

                        <div class="alert alert-light border mt-4 mb-0 small">
                            <i class="bx bx-lock-alt me-1"></i>
                            NIK, NIP dan status kepegawaian hanya dapat diubah melalui Master Guru.
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-8">
                <div class="card">
                    <div class="card-header border-bottom">
                        <h5 class="mb-1">Biodata Guru</h5>
                        <div class="small text-muted">Data pribadi yang dapat Anda perbarui sendiri.</div>
                    </div>

                    <div class="card-body">
                        <form id="formProfileGuru">
                            <?= csrf_field() ?>

                            <section class="sisfour-profile-section">
                                <div class="sisfour-profile-section__title">Identitas Administratif</div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">NIK</label>
                                        <div class="sisfour-readonly-field"><?= esc($profile['nik'] ?: '-') ?></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">NIP</label>
                                        <div class="sisfour-readonly-field"><?= esc($profile['nip'] ?: '-') ?></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Status Kepegawaian</label>
                                        <div class="sisfour-readonly-field"><?= esc($profile['status_kepegawaian'] ?: '-') ?></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="nuptk">NUPTK</label>
                                        <input
                                            type="text"
                                            inputmode="numeric"
                                            class="form-control"
                                            id="nuptk"
                                            name="nuptk"
                                            maxlength="16"
                                            pattern="[0-9]{16}"
                                            value="<?= esc($profile['nuptk'] ?? '') ?>"
                                        >
                                    </div>
                                </div>
                            </section>

                            <section class="sisfour-profile-section">
                                <div class="sisfour-profile-section__title">Identitas Pribadi</div>
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label class="form-label" for="nama">
                                            Nama Lengkap &amp; Gelar <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="nama" name="nama"
                                            maxlength="150" value="<?= esc($profile['nama']) ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" for="jenis_kelamin">
                                            Jenis Kelamin <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select" id="jenis_kelamin" name="jenis_kelamin"
                                            required data-searchable-off="1">
                                            <option value="L" <?= $profile['jenis_kelamin'] === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                                            <option value="P" <?= $profile['jenis_kelamin'] === 'P' ? 'selected' : '' ?>>Perempuan</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" for="tempat_lahir">Tempat Lahir</label>
                                        <input type="text" class="form-control" id="tempat_lahir" name="tempat_lahir"
                                            maxlength="100" value="<?= esc($profile['tempat_lahir'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" for="tanggal_lahir">Tanggal Lahir</label>
                                        <input type="date" class="form-control" id="tanggal_lahir" name="tanggal_lahir"
                                            value="<?= esc($profile['tanggal_lahir'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" for="agama">Agama</label>
                                        <input type="text" class="form-control" id="agama" name="agama"
                                            maxlength="30" value="<?= esc($profile['agama'] ?? '') ?>">
                                    </div>
                                </div>
                            </section>

                            <section class="sisfour-profile-section">
                                <div class="sisfour-profile-section__title">Kontak &amp; Alamat</div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label" for="no_telepon">No. Telepon</label>
                                        <input type="text" class="form-control" id="no_telepon" name="no_telepon"
                                            maxlength="20" value="<?= esc($profile['no_telepon'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="email">Email</label>
                                        <input type="email" class="form-control" id="email" name="email"
                                            maxlength="100" value="<?= esc($profile['email'] ?? '') ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label" for="alamat">Alamat</label>
                                        <textarea class="form-control" id="alamat" name="alamat"
                                            rows="4"><?= esc($profile['alamat'] ?? '') ?></textarea>
                                    </div>
                                </div>
                            </section>

                            <div class="d-flex justify-content-end mt-4">
                                <button type="submit" class="btn btn-primary" id="btnSimpanProfileGuru">
                                    <span class="spinner-border spinner-border-sm d-none me-1" aria-hidden="true"></span>
                                    Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
