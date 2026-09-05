<?php
$roleLabel = [
    'admin' => 'Admin', 'operator' => 'Operator', 'pimpinan' => 'Pimpinan',
    'bk' => 'BK', 'guru' => ($authUser['is_wali'] ?? false) ? 'Wali Kelas' : 'Guru', 'siswa' => 'Siswa',
][$authUser['role'] ?? ''] ?? '-';
?>
<nav class="layout-navbar container-xxl navbar-detached navbar navbar-expand-xl align-items-center bg-navbar-theme" id="layout-navbar">
  <div class="layout-menu-toggle navbar-nav align-items-xl-center me-4 me-xl-0 d-xl-none">
    <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
      <i class="icon-base bx bx-menu icon-md"></i>
    </a>
  </div>

  <div class="navbar-nav-right d-flex align-items-center justify-content-end" id="navbar-collapse">
    <div class="navbar-nav align-items-center me-auto">
      <span class="fw-semibold"><?= esc($pageTitle ?? 'Dashboard') ?></span>
    </div>

    <ul class="navbar-nav flex-row align-items-center ms-md-auto">
      <li class="nav-item navbar-dropdown dropdown-user dropdown">
        <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown">
          <div class="avatar avatar-online">
            <span class="avatar-initial rounded-circle bg-label-primary">
              <?= strtoupper(substr($authUser['username'] ?? '?', 0, 1)) ?>
            </span>
          </div>
        </a>
        <ul class="dropdown-menu dropdown-menu-end">
          <li>
            <form
    method="post"
    action="<?= base_url('auth/logout') ?>"
    class="m-0"
>
    <?= csrf_field() ?>

    <button
        type="submit"
        class="dropdown-item"
    >
        <i class="bx bx-log-out me-2"></i>
        <span>Logout</span>
    </button>
</form>
              <div class="d-flex">
                <div class="flex-shrink-0 me-3">
                  <div class="avatar avatar-online">
                    <span class="avatar-initial rounded-circle bg-label-primary">
                      <?= strtoupper(substr($authUser['username'] ?? '?', 0, 1)) ?>
                    </span>
                  </div>
                </div>
                <div class="flex-grow-1">
                  <span class="fw-semibold d-block"><?= esc($authUser['username'] ?? '') ?></span>
                  <small class="text-muted"><?= esc($roleLabel) ?></small>
                </div>
              </div>
            </a>
          </li>
          <li><div class="dropdown-divider"></div></li>
          <?php if (in_array($authUser['role'] ?? '', ['guru', 'bk', 'pimpinan'], true)): ?>
          <li>
            <a class="dropdown-item" href="<?= base_url('profile/guru') ?>">
              <i class="icon-base bx bx-user me-2 icon-md"></i><span>Profile Saya</span>
            </a>
          </li>
          <?php elseif (($authUser['role'] ?? '') === 'siswa'): ?>
          <li>
            <a class="dropdown-item" href="<?= base_url('profile/siswa') ?>">
              <i class="icon-base bx bx-user me-2 icon-md"></i><span>Profile Saya</span>
            </a>
          </li>
          <?php endif; ?>
          <li><div class="dropdown-divider"></div></li>
          <li>
            <a class="dropdown-item" href="<?= base_url('auth/logout') ?>">
              <i class="icon-base bx bx-power-off me-2 icon-md"></i><span>Log Out</span>
            </a>
          </li>
        </ul>
      </li>
    </ul>
  </div>
</nav>
