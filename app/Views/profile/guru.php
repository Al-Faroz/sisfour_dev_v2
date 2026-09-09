<?= $this->extend('main') ?>

<?= $this->section('content') ?>

<div id="profileGuruApp" data-base-url="<?= esc(base_url()) ?>">
    <div class="mb-4">
        <h4 class="fw-bold mb-1">Profile Guru</h4>
        <p class="text-muted mb-0">
            Lengkapi biodata pribadi. NIK, NIP, dan status kepegawaian dikelola secara administratif melalui Master Guru.
        </p>
    </div>

    <?php if (! empty($profileError)): ?>
        <div class="alert alert-warning"><i class="bx bx-error-circle me-1"></i><?= esc($profileError) ?></div>
    <?php elseif (! empty($profile)): ?>
        <?php if (empty($profile['identity_complete'])): ?>
            <div class="alert alert-warning">
                <i class="bx bx-info-circle me-1"></i>
                NIK pada data Guru Anda belum lengkap. Hubungi Admin/Operator untuk melengkapi identitas administratif.
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-12 col-xl-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <?php
                        $foto = trim((string) ($profile['foto'] ?? ''));
                        $fotoUrl = $foto !== '' ? base_url('uploads/foto_guru/' . rawurlencode(basename($foto))) : '';
                        ?>
                        <img
                            src="<?= esc($fotoUrl) ?>"
                            alt="Foto <?= esc($profile['nama']) ?>"
                            class="rounded object-fit-cover mb-3 <?= $fotoUrl === '' ? 'd-none' : '' ?>"
                            width="180"
                            height="240"
                            id="profileGuruFoto"
                        >
                        <div
                            class="d-inline-flex align-items-center justify-content-center rounded bg-label-secondary mb-3 <?= $fotoUrl !== '' ? 'd-none' : '' ?>"
                            style="width:180px;height:240px;font-size:64px;"
                            id="profileGuruFotoFallback"
                        ><i class="bx bx-user"></i></div>

                        <h5 class="mb-1"><?= esc($profile['nama']) ?></h5>
                        <div class="text-muted mb-2">
                            Login: <?= esc($profile['login_identifier'] ?: '-') ?>
                        </div>
                        <span class="badge bg-label-primary">
                            <?= esc($profile['status_kepegawaian'] ?: 'Status belum diisi') ?>
                        </span>

                        <div class="d-grid mt-3">
                            <a href="<?= base_url('profile/guru/personalia') ?>" class="btn btn-outline-primary">
                                <i class="bx bx-folder-open me-1"></i> Riwayat &amp; Portofolio
                            </a>
                        </div>

                        <hr class="my-4">

                        <form id="formFotoProfileGuru">
                            <?= csrf_field() ?>
                            <label for="fotoProfileGuru" class="form-label text-start d-block">Ganti Foto</label>
                            <input type="file" class="form-control" id="fotoProfileGuru" name="foto" accept="image/png" required>
                            <div class="form-text text-start">PNG maksimal 2 MB. Crop otomatis rasio 3:4.</div>
                            <button type="submit" class="btn btn-outline-primary w-100 mt-3">
                                <span class="spinner-border spinner-border-sm d-none me-1" aria-hidden="true"></span>
                                Upload Foto
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-8">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Biodata</h5></div>
                    <div class="card-body">
                        <form id="formProfileGuru">
                            <?= csrf_field() ?>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">NIK</label>
                                    <input type="text" class="form-control" value="<?= esc($profile['nik'] ?: '-') ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">NIP</label>
                                    <input type="text" class="form-control" value="<?= esc($profile['nip'] ?: '-') ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Status Kepegawaian</label>
                                    <input type="text" class="form-control" value="<?= esc($profile['status_kepegawaian'] ?: '-') ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="nuptk">NUPTK</label>
                                    <input type="text" inputmode="numeric" class="form-control" id="nuptk" name="nuptk" maxlength="16" pattern="[0-9]{16}" value="<?= esc($profile['nuptk'] ?? '') ?>">
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label" for="nama">Nama Lengkap & Gelar <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nama" name="nama" maxlength="150" value="<?= esc($profile['nama']) ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="jenis_kelamin">Jenis Kelamin <span class="text-danger">*</span></label>
                                    <select class="form-select" id="jenis_kelamin" name="jenis_kelamin" required data-searchable-off="1">
                                        <option value="L" <?= $profile['jenis_kelamin'] === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                                        <option value="P" <?= $profile['jenis_kelamin'] === 'P' ? 'selected' : '' ?>>Perempuan</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="tempat_lahir">Tempat Lahir</label>
                                    <input type="text" class="form-control" id="tempat_lahir" name="tempat_lahir" maxlength="100" value="<?= esc($profile['tempat_lahir'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="tanggal_lahir">Tanggal Lahir</label>
                                    <input type="date" class="form-control" id="tanggal_lahir" name="tanggal_lahir" value="<?= esc($profile['tanggal_lahir'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="agama">Agama</label>
                                    <input type="text" class="form-control" id="agama" name="agama" maxlength="30" value="<?= esc($profile['agama'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="no_telepon">No. Telepon</label>
                                    <input type="text" class="form-control" id="no_telepon" name="no_telepon" maxlength="20" value="<?= esc($profile['no_telepon'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="email">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" maxlength="100" value="<?= esc($profile['email'] ?? '') ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="alamat">Alamat</label>
                                    <textarea class="form-control" id="alamat" name="alamat" rows="4"><?= esc($profile['alamat'] ?? '') ?></textarea>
                                </div>
                            </div>
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
