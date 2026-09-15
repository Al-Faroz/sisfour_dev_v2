<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<div id="settingsMenuApp" data-base-url="<?= esc(base_url()) ?>">
    <div class="sisfour-page-header">
        <div class="sisfour-page-header__copy">
            <h4 class="fw-bold mb-1">Menu &amp; Role</h4>
            <p class="text-muted mb-0">Menu hanya tampilan navigasi. Authorization tetap berasal dari permission/Service.</p>
        </div>
    </div>

    <div id="menuAlert" class="alert d-none" role="alert"></div>

    <div class="card sisfour-table-card">
        <div class="card-header">
            <h5 class="mb-0">Mapping Menu ke Role</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="min-width:180px">Menu</th>
                        <th style="min-width:180px">Link</th>
                        <th style="min-width:220px">Permission Route</th>
                        <?php foreach (($initial['roles'] ?? []) as $role): ?>
                            <th class="text-center text-nowrap"><?= esc(ucfirst($role)) ?></th>
                        <?php endforeach; ?>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($initial['menus'] ?? []) as $menu): ?>
                    <tr data-menu-id="<?= (int) $menu['id'] ?>">
                        <td>
                            <strong><?= esc($menu['nama_menu']) ?></strong>
                            <?php if ($menu['parent_id']): ?>
                                <div class="small text-muted">Submenu</div>
                            <?php endif; ?>
                        </td>
                        <td><code class="text-wrap text-break"><?= esc($menu['link'] ?? '#') ?></code></td>
                        <td>
                            <?php if (empty($menu['required_permissions'])): ?>
                                <span class="text-muted">Parent / tidak dipetakan</span>
                            <?php else: ?>
                                <div class="d-flex flex-wrap gap-1">
                                    <?php foreach ($menu['required_permissions'] as $perm): ?>
                                        <span class="badge bg-label-primary"><?= esc($perm) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <?php foreach (($initial['roles'] ?? []) as $role): ?>
                            <td class="text-center">
                                <input
                                    type="checkbox"
                                    class="form-check-input menu-role"
                                    value="<?= esc($role) ?>"
                                    aria-label="Tampilkan <?= esc($menu['nama_menu'], 'attr') ?> untuk role <?= esc($role, 'attr') ?>"
                                    <?= in_array($role, $menu['roles'] ?? [], true) ? 'checked' : '' ?>
                                >
                            </td>
                        <?php endforeach; ?>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-primary btn-save-menu">
                                <i class="bx bx-save me-1"></i> Simpan
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>