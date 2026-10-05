<?php
/**
 * _sidebar.php
 *
 * PENTING: partial ini murni "dumb renderer". Tidak ada if(role === '...')
 * di sini sama sekali. Sumber kebenaran akses menu ada di tabel `role_menus`
 * dan sudah diproses oleh MenuService/BaseController.
 *
 * Single-role memakai $menuTree existing. Multi-role memakai $sidebarMenu
 * yang sudah dikelompokkan Primary/Secondary oleh composition service.
 * Partial ini hanya merender hasil composition; authorization tetap bukan
 * tanggung jawab view.
 */

if (! function_exists('render_menu_items')) {
    function render_menu_items(array $items): void
    {
        foreach ($items as $item) {
            $hasChildren = ! empty($item['children']);
            $isActive = ! empty($item['active']);
            $isOpen = ! empty($item['open']);

            $liClass = 'menu-item';

            if ($isActive) {
                $liClass .= ' active';
            }

            if ($hasChildren && $isOpen) {
                $liClass .= ' open';
            }

            $linkClass = 'menu-link' . ($hasChildren ? ' menu-toggle' : '');
            $href = $hasChildren
                ? 'javascript:void(0);'
                : base_url(ltrim($item['link'] ?? '#', '/'));

            echo '<li class="' . $liClass . '">';
            echo '<a href="' . $href . '" class="' . $linkClass . '">';

            if (! empty($item['icon'])) {
                echo '<i class="menu-icon tf-icons bx '
                    . esc(
                        str_replace(
                            'bx bx-',
                            'bx-',
                            $item['icon']
                        ),
                        'attr'
                    )
                    . '"></i>';
            } else {
                echo '<i class="menu-icon tf-icons bx bx-circle" '
                    . 'style="font-size:.4rem;opacity:.5"></i>';
            }

            echo '<div class="text-truncate">'
                . esc($item['nama_menu'])
                . '</div>';
            echo '</a>';

            if ($hasChildren) {
                echo '<ul class="menu-sub">';
                render_menu_items($item['children']);
                echo '</ul>';
            }

            echo '</li>';
        }
    }
}

$settings = $systemSettings ?? [];
$namaSekolah = trim(
    (string) ($settings['nama_sekolah'] ?? 'MTsN 4 Jombang')
);
$logoSekolah = trim(
    (string) ($settings['logo_sekolah'] ?? '')
);

if ($namaSekolah === '') {
    $namaSekolah = 'MTsN 4 Jombang';
}

$hasLogo = $logoSekolah !== ''
    && is_file(FCPATH . ltrim($logoSekolah, '/\\'));

$logoUrl = $hasLogo
    ? base_url(ltrim($logoSekolah, '/'))
    : '';
?>
<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
  <div class="app-brand demo">
    <a href="<?= base_url('dashboard') ?>" class="app-brand-link">
      <span class="app-brand-logo demo">
        <?php if ($hasLogo): ?>
          <img
            src="<?= esc($logoUrl, 'attr') ?>"
            alt="<?= esc($namaSekolah, 'attr') ?>"
            style="width:34px;height:34px;object-fit:contain"
          />
        <?php else: ?>
          <i class="bx bx-buildings bx-md text-primary"></i>
        <?php endif; ?>
      </span>
      <span class="app-brand-text demo menu-text fw-bold ms-2">SisisFour</span>
    </a>
    <a
      href="javascript:void(0);"
      class="layout-menu-toggle menu-link text-large ms-auto d-none d-xl-block"
      aria-label="Minimalkan sidebar"
      aria-controls="layout-menu"
      aria-expanded="true"
      title="Minimalkan / perluas sidebar"
    >
      <i class="icon-base bx bx-chevron-left icon-sm align-middle" data-sidebar-toggle-icon></i>
    </a>
  </div>

  <div class="menu-inner-shadow"></div>

  <ul class="menu-inner py-1">
    <?php
    $resolvedSidebar = is_array($sidebarMenu ?? null)
        ? $sidebarMenu
        : [];
    $isMultiRoleSidebar = ! empty($resolvedSidebar['is_multi_role']);
    ?>

    <?php if ($isMultiRoleSidebar): ?>
      <?php render_menu_items($resolvedSidebar['global_items'] ?? []); ?>

      <?php foreach (($resolvedSidebar['role_sections'] ?? []) as $section): ?>
        <?php
        $sectionKind = (string) ($section['kind'] ?? 'secondary');
        $sectionPrefix = $sectionKind === 'primary'
            ? 'Utama'
            : 'Tambahan';
        $sectionLabel = trim((string) ($section['label'] ?? ''));
        ?>
        <?php if ($sectionLabel !== ''): ?>
          <li class="menu-header sisfour-menu-role-header">
            <span class="menu-header-text">
              <?= esc($sectionPrefix) ?> · <?= esc($sectionLabel) ?>
            </span>
          </li>
        <?php endif; ?>

        <?php render_menu_items($section['items'] ?? []); ?>
      <?php endforeach; ?>

      <?php if (! empty($resolvedSidebar['account_items'])): ?>
        <li class="menu-header sisfour-menu-role-header">
          <span class="menu-header-text">Akun</span>
        </li>
        <?php render_menu_items($resolvedSidebar['account_items']); ?>
      <?php endif; ?>
    <?php else: ?>
      <?php render_menu_items($menuTree ?? []); ?>
    <?php endif; ?>
  </ul>
</aside>
