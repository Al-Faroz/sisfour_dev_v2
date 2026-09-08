<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<div id="settingsMenuApp" data-base-url="<?= esc(base_url()) ?>">
    <div class="mb-4">
        <h4 class="fw-bold mb-1">Menu & Role</h4>
        <p class="text-muted mb-0">Menu hanya tampilan navigasi. Authorization tetap berasal dari permission/Service.</p>
    </div>

    <div id="menuAlert" class="alert d-none"></div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Menu</th>
                        <th>Link</th>
                        <th>Permission Route</th>
                        <?php foreach (($initial['roles'] ?? []) as $role): ?>
                            <th class="text-center"><?= esc(ucfirst($role)) ?></th>
                        <?php endforeach; ?>
                        <th></th>
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
                        <td><code><?= esc($menu['link'] ?? '#') ?></code></td>
                        <td>
                            <?php if (empty($menu['required_permissions'])): ?>
                                <span class="text-muted">Parent / tidak dipetakan</span>
                            <?php else: ?>
                                <?php foreach ($menu['required_permissions'] as $perm): ?>
                                    <span class="badge bg-label-primary me-1"><?= esc($perm) ?></span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </td>
                        <?php foreach (($initial['roles'] ?? []) as $role): ?>
                            <td class="text-center">
                                <input type="checkbox"
                                    class="form-check-input menu-role"
                                    value="<?= esc($role) ?>"
                                    <?= in_array($role, $menu['roles'] ?? [], true) ? 'checked' : '' ?>>
                            </td>
                        <?php endforeach; ?>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary btn-save-menu">Simpan</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
