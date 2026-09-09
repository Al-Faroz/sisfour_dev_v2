<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<div id="settingsUserApp" data-base-url="<?= esc(base_url()) ?>">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Manajemen User</h4>
            <p class="text-muted mb-0">Primary role + secondary roles. Wali Kelas bukan role.</p>
        </div>
        <button class="btn btn-primary" id="btnUserBaru">Tambah User</button>
    </div>

    <div class="alert alert-info">
        <i class="bx bx-info-circle me-1"></i>
        Akun Guru/Pegawai dikelola dari Master Guru/Pegawai. Username dan password selalu mengikuti NIP jika tersedia, selain itu NIK. Dari halaman ini Admin hanya mengatur role dan status akun tersebut.
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-5"><input id="userSearch" class="form-control" placeholder="Cari username / nama / NIP / NIK / NISN"></div>
                <div class="col-md-3">
                    <select id="userRoleFilter" class="form-select">
                        <option value="">Semua role</option>
                        <?php foreach (($initial['roles'] ?? []) as $role): ?>
                            <option value="<?= esc($role) ?>"><?= esc(ucfirst($role)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select id="userStatusFilter" class="form-select">
                        <option value="">Semua status</option>
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                    </select>
                </div>
                <div class="col-md-2 d-grid"><button class="btn btn-outline-primary" id="btnUserCari">Tampilkan</button></div>
            </div>
        </div>
    </div>

    <div id="userAlert" class="alert d-none"></div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Username</th><th>Identitas</th><th>Primary</th><th>Secondary</th><th>Status</th><th>Auth Ver.</th><th class="text-end">Aksi</th></tr></thead>
                <tbody id="userBody"></tbody>
            </table>
        </div>
        <div class="card-footer d-flex justify-content-between">
            <small class="text-muted" id="userInfo"></small>
            <div class="btn-group">
                <button class="btn btn-sm btn-outline-secondary" id="userPrev">Sebelumnya</button>
                <button class="btn btn-sm btn-outline-secondary" id="userNext">Berikutnya</button>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalUser" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <form class="modal-content" id="formUser">
                <div class="modal-header">
                    <h5 class="modal-title">User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Username</label>
                            <input name="username" class="form-control" maxlength="50" required>
                            <div class="form-text" id="usernameHelp">Untuk Guru/Pegawai akan otomatis mengikuti NIP/NIK.</div>
                        </div>
                        <div class="col-md-6" id="passwordCreateWrap">
                            <label class="form-label">Password awal</label>
                            <input name="password" type="password" class="form-control" minlength="8">
                            <div class="form-text">Untuk Guru/Pegawai password otomatis mengikuti NIP/NIK.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Primary Role</label>
                            <select name="primary_role" class="form-select">
                                <option value="">NULL / Tanpa role</option>
                                <?php foreach (($initial['roles'] ?? []) as $role): ?>
                                    <option value="<?= esc($role) ?>"><?= esc(ucfirst($role)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status_aktif" class="form-select"><option value="1">Aktif</option><option value="0">Nonaktif</option></select>
                        </div>

                        <div class="col-12">
                            <label class="form-label d-block">Secondary Roles</label>
                            <div class="d-flex flex-wrap gap-3">
                                <?php foreach (($initial['roles'] ?? []) as $role): ?>
                                    <div class="form-check">
                                        <input class="form-check-input secondary-role" type="checkbox" name="secondary_roles[]" value="<?= esc($role) ?>" id="role_<?= esc($role) ?>">
                                        <label class="form-check-label" for="role_<?= esc($role) ?>"><?= esc(ucfirst($role)) ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Jenis Relasi</label>
                            <select id="identityType" class="form-select">
                                <option value="">Tanpa relasi</option><option value="guru">Guru</option><option value="pegawai">Pegawai</option><option value="siswa">Siswa</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Cari Identitas</label>
                            <div class="input-group">
                                <input id="identitySearch" class="form-control" placeholder="Nama / NIP / NIK / NISN">
                                <button type="button" class="btn btn-outline-secondary" id="btnIdentitySearch">Cari</button>
                            </div>
                            <select id="identityResult" class="form-select mt-2"><option value="">Pilih identitas</option></select>
                            <input type="hidden" name="id_guru"><input type="hidden" name="id_pegawai"><input type="hidden" name="id_siswa">
                            <div class="form-text" id="identityHelp"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer"><button class="btn btn-primary" type="submit">Simpan</button></div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalResetPassword" tabindex="-1">
        <div class="modal-dialog">
            <form class="modal-content" id="formResetPassword">
                <div class="modal-header"><h5 class="modal-title">Reset Password</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" name="id">
                    <div id="managedResetInfo" class="alert alert-info d-none">Password akun Guru/Pegawai akan direset ke identitas login NIP/NIK saat ini.</div>
                    <div id="manualResetFields">
                        <div class="mb-3"><label class="form-label">Password Baru</label><input name="password" type="password" minlength="8" class="form-control"></div>
                        <div><label class="form-label">Konfirmasi Password</label><input name="password_confirmation" type="password" minlength="8" class="form-control"></div>
                    </div>
                </div>
                <div class="modal-footer"><button class="btn btn-primary" type="submit">Reset Password</button></div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
