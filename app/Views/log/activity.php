<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<?php
$initialRows = $initial['rows'] ?? [];
$modules = $initial['modules'] ?? [];
$actions = $initial['actions'] ?? [];
?>

<div
  id="logActivityApp"
  data-base-url="<?= esc(base_url(), 'attr') ?>"
>
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
      <h4 class="fw-bold mb-1">Log Activity</h4>
      <p class="text-muted mb-0">
        Audit aktivitas aplikasi untuk Admin dan Operator.
      </p>
    </div>

    <button
      type="button"
      id="btnExportLog"
      class="btn btn-outline-primary"
    >
      <i class="bx bx-export me-1"></i>
      Export CSV
    </button>
  </div>

  <div
    id="logAlert"
    class="alert d-none"
    role="alert"
  ></div>

  <div class="card mb-4">
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Pencarian</label>
          <input
            type="search"
            id="logSearch"
            class="form-control"
            maxlength="100"
            placeholder="User, aksi, modul, keterangan..."
          >
        </div>

        <div class="col-md-2">
          <label class="form-label">Modul</label>
          <select
            id="logModule"
            class="form-select"
          >
            <option value="">Semua Modul</option>
            <?php foreach ($modules as $module): ?>
              <option value="<?= esc($module, 'attr') ?>">
                <?= esc($module) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label">Aksi</label>
          <select
            id="logAction"
            class="form-select"
          >
            <option value="">Semua Aksi</option>
            <?php foreach ($actions as $action): ?>
              <option value="<?= esc($action, 'attr') ?>">
                <?= esc($action) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label">Tanggal Mulai</label>
          <input
            type="date"
            id="logStart"
            class="form-control"
          >
        </div>

        <div class="col-md-2">
          <label class="form-label">Tanggal Selesai</label>
          <input
            type="date"
            id="logEnd"
            class="form-control"
          >
        </div>
      </div>

      <div class="d-flex gap-2 mt-3">
        <button
          type="button"
          id="btnFilterLog"
          class="btn btn-primary"
        >
          Terapkan
        </button>

        <button
          type="button"
          id="btnResetLog"
          class="btn btn-outline-secondary"
        >
          Reset
        </button>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th style="min-width:150px">Waktu</th>
            <th style="min-width:140px">User</th>
            <th style="min-width:110px">Aksi</th>
            <th style="min-width:150px">Modul</th>
            <th>Keterangan</th>
          </tr>
        </thead>
        <tbody id="logBody">
          <?php if ($initialRows === []): ?>
            <tr>
              <td
                colspan="5"
                class="text-center text-muted py-4"
              >
                Belum ada log activity.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($initialRows as $row): ?>
              <tr>
                <td><?= esc($row['waktu']) ?></td>
                <td>
                  <?= esc(
                      $row['username']
                          ?? (
                              $row['id_user'] !== null
                                  ? 'User #' . $row['id_user']
                                  : 'System'
                          )
                  ) ?>
                </td>
                <td>
                  <span class="badge bg-label-primary">
                    <?= esc($row['aksi']) ?>
                  </span>
                </td>
                <td><?= esc($row['modul']) ?></td>
                <td class="text-wrap">
                  <?= esc($row['keterangan'] ?? '-') ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="card-footer d-flex flex-wrap align-items-center justify-content-between gap-3">
      <div
        id="logInfo"
        class="text-muted small"
      ></div>

      <div class="d-flex gap-2">
        <button
          type="button"
          id="logPrev"
          class="btn btn-sm btn-outline-secondary"
        >
          Sebelumnya
        </button>

        <button
          type="button"
          id="logNext"
          class="btn btn-sm btn-outline-secondary"
        >
          Berikutnya
        </button>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
