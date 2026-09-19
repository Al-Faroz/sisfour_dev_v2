<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<?php
$rows = $initial['rows'] ?? [];
?>

<div id="backupApp" data-base-url="<?= esc(base_url(), 'attr') ?>">
  <div class="sisfour-page-header">
    <div class="sisfour-page-header__copy">
      <h4 class="fw-bold mb-1">Backup Database</h4>
      <p class="text-muted mb-0">
        Backup SQL disimpan aman di <code>writable/backups/</code>.
      </p>
    </div>

    <div class="sisfour-page-actions">
      <button type="button" class="btn btn-primary sisfour-touch-target" id="btnCreateBackup">
        <i class="bx bx-data me-1"></i> Buat Backup
      </button>
    </div>
  </div>

  <div id="backupAlert" class="alert d-none" role="alert"></div>

  <div class="card sisfour-table-card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
      <h5 class="mb-0">Daftar Backup</h5>
      <button type="button" class="btn btn-sm btn-outline-secondary sisfour-touch-target--compact" id="btnReloadBackup">
        <i class="bx bx-refresh me-1"></i> Muat Ulang
      </button>
    </div>

    <div id="backupMobileList" class="d-md-none list-group list-group-flush">
      <?php if ($rows === []): ?>
        <div class="list-group-item sisfour-mobile-state text-muted">Belum ada file backup.</div>
      <?php else: ?>
        <?php foreach ($rows as $row): ?>
          <div class="list-group-item py-3">
            <div class="fw-semibold font-monospace sisfour-wrap-anywhere"><?= esc($row['filename']) ?></div>
            <div class="small text-muted mt-1"><?= number_format(((int) $row['size']) / 1024, 2, ',', '.') ?> KB · <?= esc($row['created_at'] ?? '-') ?></div>
            <div class="sisfour-mobile-actions mt-3">
              <a class="btn btn-sm btn-outline-primary sisfour-touch-target--compact" href="<?= base_url('backup/download/' . rawurlencode($row['filename'])) ?>">Download</a>
              <button type="button" class="btn btn-sm btn-outline-danger sisfour-touch-target--compact btn-delete-backup" data-filename="<?= esc($row['filename'], 'attr') ?>">Hapus</button>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <div class="d-none d-md-block table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr>
            <th>Nama File</th>
            <th>Ukuran</th>
            <th>Dibuat</th>
            <th class="text-end">Aksi</th>
          </tr>
        </thead>
        <tbody id="backupBody">
          <?php if ($rows === []): ?>
            <tr class="sisfour-empty-row">
              <td colspan="4" class="text-muted">Belum ada file backup.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($rows as $row): ?>
              <tr>
                <td><code><?= esc($row['filename']) ?></code></td>
                <td><?= number_format(((int) $row['size']) / 1024, 2, ',', '.') ?> KB</td>
                <td><?= esc($row['created_at'] ?? '-') ?></td>
                <td class="text-end">
                  <div class="sisfour-row-actions justify-content-end">
                    <a
                      class="btn btn-sm btn-outline-primary sisfour-touch-target--compact"
                      href="<?= base_url('backup/download/' . rawurlencode($row['filename'])) ?>"
                    >
                      <i class="bx bx-download me-1"></i> Download
                    </a>
                    <button
                      type="button"
                      class="btn btn-sm btn-outline-danger sisfour-touch-target--compact btn-delete-backup"
                      data-filename="<?= esc($row['filename'], 'attr') ?>"
                    >
                      <i class="bx bx-trash me-1"></i> Hapus
                    </button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="alert alert-warning sisfour-compact-note mt-4 mb-0">
    <strong>Catatan:</strong>
    Backup ini mencakup struktur tabel dan data database. File upload, foto, logo, dan asset aplikasi tidak termasuk.
  </div>
</div>

<?= $this->endSection() ?>