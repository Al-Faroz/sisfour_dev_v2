<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<?php
$rows = $initial['rows'] ?? [];
?>

<div
  id="backupApp"
  data-base-url="<?= esc(base_url(), 'attr') ?>"
>
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
      <h4 class="fw-bold mb-1">Backup Database</h4>
      <p class="text-muted mb-0">
        Backup SQL disimpan aman di
        <code>writable/backups/</code>.
      </p>
    </div>

    <button
      type="button"
      class="btn btn-primary"
      id="btnCreateBackup"
    >
      <i class="bx bx-data me-1"></i>
      Buat Backup
    </button>
  </div>

  <div
    id="backupAlert"
    class="alert d-none"
    role="alert"
  ></div>

  <div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
      <h5 class="mb-0">Daftar Backup</h5>

      <button
        type="button"
        class="btn btn-sm btn-outline-secondary"
        id="btnReloadBackup"
      >
        <i class="bx bx-refresh me-1"></i>
        Muat Ulang
      </button>
    </div>

    <div class="table-responsive text-nowrap">
      <table class="table">
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
            <tr>
              <td
                colspan="4"
                class="text-center text-muted py-4"
              >
                Belum ada file backup.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($rows as $row): ?>
              <tr>
                <td>
                  <code><?= esc($row['filename']) ?></code>
                </td>
                <td>
                  <?= number_format(
                      ((int) $row['size']) / 1024,
                      2,
                      ',',
                      '.'
                  ) ?> KB
                </td>
                <td>
                  <?= esc($row['created_at'] ?? '-') ?>
                </td>
                <td class="text-end">
                  <a
                    class="btn btn-sm btn-outline-primary"
                    href="<?= base_url(
                        'backup/download/'
                        . rawurlencode($row['filename'])
                    ) ?>"
                  >
                    Download
                  </a>

                  <button
                    type="button"
                    class="btn btn-sm btn-outline-danger btn-delete-backup"
                    data-filename="<?= esc(
                        $row['filename'],
                        'attr'
                    ) ?>"
                  >
                    Hapus
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="alert alert-warning mt-4 mb-0">
    <strong>Catatan:</strong>
    Backup ini mencakup struktur tabel dan data database.
    File upload, foto, logo, dan asset aplikasi tidak termasuk.
  </div>
</div>

<?= $this->endSection() ?>
