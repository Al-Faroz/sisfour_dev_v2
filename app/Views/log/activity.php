<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<?php
$initialRows = $initial['rows'] ?? [];
$modules = $initial['modules'] ?? [];
$actions = $initial['actions'] ?? [];
?>

<div id="logActivityApp" data-base-url="<?= esc(base_url(), 'attr') ?>">
  <div class="sisfour-page-header">
    <div class="sisfour-page-header__copy">
      <h4 class="fw-bold mb-1">Log Activity</h4>
      <p class="text-muted mb-0">Audit aktivitas aplikasi untuk Admin dan Operator.</p>
    </div>

    <div class="sisfour-page-actions">
      <button type="button" id="btnExportLog" class="btn btn-outline-success">
        <i class="bx bx-export me-1"></i> Export CSV
      </button>
    </div>
  </div>

  <div id="logAlert" class="alert d-none" role="alert"></div>

  <div class="card sisfour-filter-card mb-4">
    <div class="card-body">
      <div class="row g-3 align-items-end">
        <div class="col-12 col-md-4">
          <label class="form-label" for="logSearch">Pencarian</label>
          <input
            type="search"
            id="logSearch"
            class="form-control"
            maxlength="100"
            placeholder="User, aksi, modul, keterangan..."
          >
        </div>

        <div class="col-6 col-md-2">
          <label class="form-label" for="logModule">Modul</label>
          <select id="logModule" class="form-select" data-searchable-off="1">
            <option value="">Semua Modul</option>
            <?php foreach ($modules as $module): ?>
              <option value="<?= esc($module, 'attr') ?>"><?= esc($module) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-6 col-md-2">
          <label class="form-label" for="logAction">Aksi</label>
          <select id="logAction" class="form-select" data-searchable-off="1">
            <option value="">Semua Aksi</option>
            <?php foreach ($actions as $action): ?>
              <option value="<?= esc($action, 'attr') ?>"><?= esc($action) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-6 col-md-2">
          <label class="form-label" for="logStart">Tanggal Mulai</label>
          <input type="date" id="logStart" class="form-control">
        </div>

        <div class="col-6 col-md-2">
          <label class="form-label" for="logEnd">Tanggal Selesai</label>
          <input type="date" id="logEnd" class="form-control">
        </div>

        <div class="col-12 sisfour-filter-actions">
          <button type="button" id="btnResetLog" class="btn btn-outline-secondary">Reset</button>
          <button type="button" id="btnFilterLog" class="btn btn-primary">
            <i class="bx bx-filter-alt me-1"></i> Terapkan
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="card sisfour-table-card">
    <div class="card-header">
      <h5 class="mb-0">Riwayat Aktivitas</h5>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0" id="tableLogActivity">
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
            <tr class="sisfour-empty-row">
              <td colspan="5" class="text-muted">Belum ada log activity.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($initialRows as $row): ?>
              <tr>
                <td><?= esc($row['waktu']) ?></td>
                <td>
                  <?= esc(
                      $row['username']
                          ?? ($row['id_user'] !== null ? 'User #' . $row['id_user'] : 'System')
                  ) ?>
                </td>
                <td><span class="badge bg-label-primary"><?= esc($row['aksi']) ?></span></td>
                <td><?= esc($row['modul']) ?></td>
                <td class="text-wrap"><?= esc($row['keterangan'] ?? '-') ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?= $this->endSection() ?>