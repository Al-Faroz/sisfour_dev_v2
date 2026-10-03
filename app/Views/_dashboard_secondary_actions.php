<?php
$sections = is_array($secondaryActionSections ?? null)
    ? $secondaryActionSections
    : [];
?>

<?php foreach ($sections as $section): ?>
  <?php
  $actions = is_array($section['actions'] ?? null)
      ? $section['actions']
      : [];
  $primaryActions = array_values(array_filter(
      $actions,
      static fn (array $action): bool =>
          ($action['surface'] ?? '') === 'primary'
  ));
  $accessActions = array_values(array_filter(
      $actions,
      static fn (array $action): bool =>
          ($action['surface'] ?? '') !== 'primary'
  ));
  ?>
  <?php if ($actions !== []): ?>
    <section class="mb-4"
             data-dashboard-secondary-role="<?= esc((string) ($section['role'] ?? ''), 'attr') ?>">
      <div class="sisfour-dashboard-heading">
        <h5 class="mb-0"><?= esc((string) ($section['title'] ?? 'Akses Tambahan')) ?></h5>
        <small class="text-muted"><?= esc((string) ($section['subtitle'] ?? 'Role tambahan')) ?></small>
      </div>

      <?php foreach ($primaryActions as $action): ?>
        <a class="btn sisfour-action sisfour-action--hero sisfour-action--<?= esc((string) ($action['tone'] ?? 'indigo'), 'attr') ?> is-current mb-2"
           href="<?= base_url((string) ($action['url'] ?? '')) ?>"
           <?= ! empty($action['external']) ? 'target="_blank" rel="noopener"' : '' ?>>
          <span class="d-flex align-items-center gap-3 min-w-0">
            <i class="bx <?= esc((string) ($action['icon'] ?? 'bx-link'), 'attr') ?> fs-2 flex-shrink-0"></i>
            <span class="min-w-0">
              <strong class="d-block fs-5 text-wrap"><?= esc((string) ($action['label'] ?? '-')) ?></strong>
              <small class="d-block opacity-75 text-wrap"><?= esc((string) ($action['description'] ?? '')) ?></small>
            </span>
          </span>
          <i class="bx bx-chevron-right fs-3 flex-shrink-0"></i>
        </a>
      <?php endforeach; ?>

      <?php if ($accessActions !== []): ?>
        <div class="row g-2">
          <?php foreach ($accessActions as $action): ?>
            <div class="col-6 col-md-3">
              <a class="btn sisfour-action sisfour-action--tile sisfour-action--<?= esc((string) ($action['tone'] ?? 'indigo'), 'attr') ?> w-100 h-100 sisfour-touch-target"
                 href="<?= base_url((string) ($action['url'] ?? '')) ?>"
                 <?= ! empty($action['external']) ? 'target="_blank" rel="noopener"' : '' ?>>
                <span class="min-w-0">
                  <i class="bx <?= esc((string) ($action['icon'] ?? 'bx-link'), 'attr') ?> fs-4 d-block mb-1"></i>
                  <strong class="d-block text-wrap"><?= esc((string) ($action['label'] ?? '-')) ?></strong>
                  <small class="d-block opacity-75 mt-1 text-wrap"><?= esc((string) ($action['description'] ?? '')) ?></small>
                </span>
              </a>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  <?php endif; ?>
<?php endforeach; ?>
