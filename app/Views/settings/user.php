<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<div id="settingsUserApp" data-base-url="<?= esc(base_url()) ?>">
    <div class="sisfour-page-header">
        <div class="sisfour-page-header__copy">
            <h4 class="fw-bold mb-1">Manajemen User</h4>
            <p class="text-muted mb-0">Kelola primary role, secondary role, status akun, dan relasi identitas. Wali Kelas bukan role.</p>
        </div>
        <div class="sisfour-page-actions">
            <button type="button" class="btn btn-primary sisfour-touch-target" id="btnUserBaru">
                <i class="bx bx-plus me-1"></i> Tambah User
            </button>
        </div>
    </div>

    <div class="alert alert-info sisfour-compact-note">
        <i class="bx bx-info-circle me-1"></i>
        Akun Guru/Pegawai dikelola dari Master Guru/Pegawai. Username dan password selalu mengikuti NIP jika tersedia, selain itu NIK. Dari halaman ini Admin hanya mengatur role dan status akun tersebut.
    </div>

    <div class="card sisfour-filter-card mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-5">
                    <label class="form-label" for="userSearch">Pencarian</label>
                    <input id="userSearch" type="search" class="form-control" placeholder="Username / nama / NIP / NIK / NISN">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label" for="userRoleFilter">Role</label>
                    <select id="userRoleFilter" class="form-select" data-searchable-off="1">
                        <option value="">Semua role</option>
                        <?php foreach (($initial['roles'] ?? []) as $role): ?>
                            <option value="<?= esc($role) ?>"><?= esc(ucfirst($role)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label" for="userStatusFilter">Status</label>
                    <select id="userStatusFilter" class="form-select" data-searchable-off="1">
                        <option value="">Semua status</option>
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                    </select>
                </div>
                <div class="col-12 col-md-2 d-grid">
                    <button type="button" class="btn btn-primary sisfour-primary-action" id="btnUserCari">
                        <i class="bx bx-filter-alt me-1"></i> Tampilkan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="userAlert" class="alert d-none" role="alert"></div>

    <div class="card sisfour-table-card">
        <div class="card-header">
            <h5 class="mb-0">Daftar User</h5>
        </div>
        <div id="userMobileList" class="d-md-none list-group list-group-flush">
            <div class="list-group-item sisfour-mobile-state text-muted">Memuat user...</div>
        </div>
        <div class="d-none d-md-block table-responsive">
            <table class="table table-hover align-middle mb-0" id="tableUser">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Identitas</th>
                        <th>Primary</th>
                        <th>Secondary</th>
                        <th>Status</th>
                        <th>Auth Ver.</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody id="userBody"></tbody>
            </table>
        </div>
        <div class="card-footer">
            <div id="userPager"></div>
        </div>
    </div>

    <div class="modal fade" id="modalUser" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-sm-down">
            <form class="modal-content" id="formUser">
                <div class="modal-header">
                    <h5 class="modal-title">User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="userUsername">Username</label>
                            <input id="userUsername" name="username" class="form-control" maxlength="50" required>
                            <div class="form-text" id="usernameHelp">Untuk Guru/Pegawai akan otomatis mengikuti NIP/NIK.</div>
                        </div>
                        <div class="col-md-6" id="passwordCreateWrap">
                            <label class="form-label" for="userPassword">Password awal</label>
                            <input id="userPassword" name="password" type="password" class="form-control" minlength="8">
                            <div class="form-text">Untuk Guru/Pegawai password otomatis mengikuti NIP/NIK.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="userPrimaryRole">Primary Role</label>
                            <select id="userPrimaryRole" name="primary_role" class="form-select" data-searchable-off="1">
                                <option value="">NULL / Tanpa role</option>
                                <?php foreach (($initial['roles'] ?? []) as $role): ?>
                                    <option value="<?= esc($role) ?>"><?= esc(ucfirst($role)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="userStatusAktif">Status</label>
                            <select id="userStatusAktif" name="status_aktif" class="form-select" data-searchable-off="1">
                                <option value="1">Aktif</option>
                                <option value="0">Nonaktif</option>
                            </select>
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
                            <label class="form-label" for="identityType">Jenis Relasi</label>
                            <select id="identityType" class="form-select" data-searchable-off="1">
                                <option value="">Tanpa relasi</option>
                                <option value="guru">Guru</option>
                                <option value="pegawai">Pegawai</option>
                                <option value="siswa">Siswa</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label" for="identitySearch">Cari Identitas</label>
                            <div class="input-group">
                                <input id="identitySearch" class="form-control" placeholder="Nama / NIP / NIK / NISN">
                                <button type="button" class="btn btn-outline-secondary sisfour-touch-target--compact" id="btnIdentitySearch">Cari</button>
                            </div>
                            <select id="identityResult" class="form-select mt-2"><option value="">Pilih identitas</option></select>
                            <input type="hidden" name="id_guru">
                            <input type="hidden" name="id_pegawai">
                            <input type="hidden" name="id_siswa">
                            <div class="form-text" id="identityHelp"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer sisfour-modal-actions">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-primary" type="submit">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalResetPassword" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-sm-down">
            <form class="modal-content" id="formResetPassword">
                <div class="modal-header">
                    <h5 class="modal-title">Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id">
                    <div id="managedResetInfo" class="alert alert-info d-none sisfour-compact-note">Password akun Guru/Pegawai akan direset ke identitas login NIP/NIK saat ini.</div>
                    <div id="manualResetFields">
                        <div class="mb-3">
                            <label class="form-label" for="resetPassword">Password Baru</label>
                            <input id="resetPassword" name="password" type="password" minlength="8" class="form-control">
                        </div>
                        <div>
                            <label class="form-label" for="resetPasswordConfirmation">Konfirmasi Password</label>
                            <input id="resetPasswordConfirmation" name="password_confirmation" type="password" minlength="8" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer sisfour-modal-actions">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-primary" type="submit">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>