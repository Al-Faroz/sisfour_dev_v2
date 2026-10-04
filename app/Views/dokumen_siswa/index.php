<?= $this->extend('main') ?>
<?= $this->section('content') ?>
<?php
$filter = $initial['filter'] ?? [];
$currentQuery = service('request')->getGet();
$exportQuery = http_build_query(is_array($currentQuery) ? $currentQuery : []);
$classesByPeriod = $initial['classes_by_period'] ?? [];
?>
<div id="dokumenSiswaApp"
     data-base-url="<?= esc(base_url()) ?>"
     data-can-manage="<?= ! empty($initial['can_manage']) ? '1' : '0' ?>"
     data-focus-import="<?= ! empty($focusImport) ? '1' : '0' ?>">
  <script type="application/json" id="dokumenClassesData"><?= json_encode($classesByPeriod, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>

  <div class="sisfour-page-header">
    <div class="sisfour-page-header__copy">
      <h4 class="fw-bold mb-1">Dokumen Siswa</h4>
      <p class="text-muted mb-0">Registry link Google Drive berbasis siswa/tingkat dan exact Tahun Ajaran + Semester. Format hanya PDF atau Image.</p>
    </div>
    <?php if (! empty($initial['success'])): ?>
      <div class="sisfour-page-actions">
        <?php if (! empty($initial['can_export'])): ?>
          <a class="btn btn-outline-success" href="<?= esc(base_url('dokumen-siswa/export') . ($exportQuery !== '' ? '?' . $exportQuery : '')) ?>"><i class="bx bx-export me-1"></i> Export Metadata</a>
        <?php endif; ?>
        <?php if (! empty($initial['can_manage'])): ?>
          <button class="btn btn-outline-primary" id="btnBulkDokumen" type="button"><i class="bx bx-import me-1"></i> Bulk Import</button>
          <button class="btn btn-primary" id="btnDokumenBaru" type="button"><i class="bx bx-plus me-1"></i> Tambah Dokumen</button>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

  <?php if (empty($initial['success'])): ?>
    <div class="alert alert-danger"><?= esc($initial['message'] ?? 'Dokumen Siswa tidak dapat dibuka.') ?></div>
  <?php else: ?>
    <div id="dokumenAlert" class="alert d-none" role="alert"></div>

    <div class="card sisfour-filter-card mb-4"><div class="card-body">
      <form method="get" action="<?= esc(base_url('dokumen-siswa')) ?>" class="row g-3 align-items-end">
        <div class="col-12 col-md-3">
          <label class="form-label">Tahun Ajaran / Semester</label>
          <select class="form-select" name="id_tahun" data-searchable-off="1">
            <?php foreach (($initial['tahun_options'] ?? []) as $ta): ?>
              <?php $aktif = (int) ($ta['status_aktif'] ?? 0) === 1; ?>
              <option value="<?= (int) $ta['id'] ?>" <?= (int) ($initial['tahun_dipilih']['id'] ?? 0) === (int) $ta['id'] ? 'selected' : '' ?>>
                <?= esc($ta['nama_tahun'] . ' - ' . $ta['semester'] . ($aktif ? ' (Aktif)' : '')) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-6 col-md-2"><label class="form-label">Target</label><select class="form-select" name="target_type"><option value="">Semua</option><option value="INDIVIDU" <?= ($filter['target_type']??'')==='INDIVIDU'?'selected':'' ?>>Individu</option><option value="TINGKAT" <?= ($filter['target_type']??'')==='TINGKAT'?'selected':'' ?>>Tingkat</option></select></div>
        <div class="col-6 col-md-2"><label class="form-label">Format</label><select class="form-select" name="format_file"><option value="">Semua</option><option value="PDF" <?= ($filter['format_file']??'')==='PDF'?'selected':'' ?>>PDF</option><option value="IMAGE" <?= ($filter['format_file']??'')==='IMAGE'?'selected':'' ?>>Image</option></select></div>
        <div class="col-6 col-md-2"><label class="form-label">Status</label><select class="form-select" name="status"><option value="">Semua</option><option value="PUBLISHED" <?= ($filter['status']??'')==='PUBLISHED'?'selected':'' ?>>Published</option><option value="ARCHIVED" <?= ($filter['status']??'')==='ARCHIVED'?'selected':'' ?>>Archived</option></select></div>
        <div class="col-6 col-md-1"><label class="form-label">Tingkat</label><select class="form-select" name="tingkat"><option value="">-</option><?php foreach(['7','8','9'] as $lv): ?><option value="<?= $lv ?>" <?= ($filter['tingkat']??'')===$lv?'selected':'' ?>><?= $lv ?></option><?php endforeach; ?></select></div>
        <div class="col-12 col-md-2"><label class="form-label">Cari</label><input class="form-control" name="search" value="<?= esc($filter['search']??'') ?>" placeholder="Judul / nama / NISN"></div>
        <div class="col-12 d-flex justify-content-end"><button class="btn btn-primary" type="submit"><i class="bx bx-filter-alt me-1"></i> Tampilkan</button></div>
      </form>
    </div></div>

    <div class="card sisfour-table-card">
      <div class="card-header d-flex justify-content-between align-items-center gap-2"><h5 class="mb-0">Data Dokumen</h5><span class="small text-muted"><?= number_format((int)($initial['total']??0),0,',','.') ?> dokumen</span></div>
      <div class="d-md-none list-group list-group-flush">
        <?php foreach (($initial['rows']??[]) as $row): ?>
          <?php $payload=rawurlencode(json_encode($row,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)); ?>
          <div class="list-group-item" data-json="<?= esc($payload,'attr') ?>">
            <div class="d-flex justify-content-between gap-2"><strong><?= esc($row['judul']??'-') ?></strong><span class="badge bg-label-<?= ($row['status']??'')==='PUBLISHED'?'success':'secondary' ?>"><?= esc($row['status']??'-') ?></span></div>
            <div class="small text-muted mt-1"><?= esc(($row['target_type']??'-')==='INDIVIDU' ? (($row['nama_siswa']??'-').' · '.($row['nama_kelas']??'-')) : ('Tingkat '.($row['tingkat']??'-'))) ?> · <?= esc($row['format_file']??'-') ?></div>
            <?php if (!empty($initial['can_manage'])): ?><div class="d-flex gap-2 mt-2"><button class="btn btn-sm btn-outline-primary btn-edit-dokumen" type="button">Edit</button><?php if(($row['status']??'')==='PUBLISHED'):?><button class="btn btn-sm btn-outline-secondary btn-archive-dokumen" type="button" data-id="<?= (int)$row['id'] ?>">Arsipkan</button><?php endif;?></div><?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="d-none d-md-block table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Judul</th><th>Target</th><th>Periode</th><th>Format</th><th>Status</th><th>Pencatat</th><th class="text-end">Aksi</th></tr></thead><tbody>
        <?php if (($initial['rows']??[])===[]): ?><tr><td colspan="7" class="text-center text-muted py-4">Belum ada Dokumen pada filter ini.</td></tr><?php endif; ?>
        <?php foreach (($initial['rows']??[]) as $row): ?>
          <?php $payload=rawurlencode(json_encode($row,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)); ?>
          <tr data-json="<?= esc($payload,'attr') ?>"><td><strong><?= esc($row['judul']??'-') ?></strong><div class="small text-muted"><?= esc(($row['target_type']??'')==='INDIVIDU' ? ($row['nisn']??'') : '') ?></div></td>
            <td><?= ($row['target_type']??'')==='INDIVIDU' ? esc(($row['nama_siswa']??'-').' · '.($row['nama_kelas']??'-')) : esc('Tingkat '.($row['tingkat']??'-')) ?></td>
            <td><?= esc(($row['nama_tahun']??'-').' - '.($row['semester']??'-')) ?></td><td><span class="badge bg-label-info"><?= esc($row['format_file']??'-') ?></span></td>
            <td><span class="badge bg-label-<?= ($row['status']??'')==='PUBLISHED'?'success':'secondary' ?>"><?= esc($row['status']??'-') ?></span></td><td><?= esc($row['nama_pencatat']??$row['username_pencatat']??'-') ?></td>
            <td class="text-end"><?php if(!empty($initial['can_manage'])):?><button class="btn btn-sm btn-outline-primary btn-edit-dokumen" type="button">Edit</button><?php if(($row['status']??'')==='PUBLISHED'):?> <button class="btn btn-sm btn-outline-secondary btn-archive-dokumen" type="button" data-id="<?= (int)$row['id'] ?>">Arsipkan</button><?php endif;?><?php endif;?></td>
          </tr>
        <?php endforeach; ?>
      </tbody></table></div>
    </div>

    <?php if (! empty($initial['can_manage'])): ?>
      <div class="card sisfour-table-card mt-4">
        <div class="card-header"><h5 class="mb-0">Batch Import Terakhir</h5></div>
        <div class="table-responsive"><table class="table table-sm align-middle mb-0">
          <thead><tr><th>ID</th><th>Judul</th><th>Format</th><th>Jumlah</th><th>Status</th><th>Pencatat</th><th class="text-end">Aksi</th></tr></thead>
          <tbody>
          <?php if (($initial['batches'] ?? []) === []): ?><tr><td colspan="7" class="text-center text-muted py-3">Belum ada batch import pada periode ini.</td></tr><?php endif; ?>
          <?php foreach (($initial['batches'] ?? []) as $batch): ?><tr>
            <td>#<?= (int) $batch['id'] ?></td>
            <td><?= esc($batch['judul'] ?? '-') ?><div class="small text-muted"><?= esc($batch['source_filename'] ?? '-') ?></div></td>
            <td><?= esc($batch['format_file'] ?? '-') ?></td>
            <td><?= (int) ($batch['total_valid'] ?? 0) ?></td>
            <td><span class="badge bg-label-<?= ($batch['status'] ?? '') === 'COMMITTED' ? 'success' : 'secondary' ?>"><?= esc($batch['status'] ?? '-') ?></span></td>
            <td><?= esc($batch['nama_pencatat'] ?? $batch['username_pencatat'] ?? '-') ?></td>
            <td class="text-end"><?php if (($batch['status'] ?? '') === 'COMMITTED'): ?><button class="btn btn-sm btn-outline-danger btn-rollback-batch" type="button" data-id="<?= (int) $batch['id'] ?>">Rollback Metadata</button><?php endif; ?></td>
          </tr><?php endforeach; ?>
          </tbody>
        </table></div>
      </div>
    <?php endif; ?>

    <?php if (! empty($initial['can_manage'])): ?>
    <div class="modal fade" id="modalDokumen" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-scrollable"><form id="formDokumen" class="modal-content">
      <div class="modal-header"><div><h5 class="modal-title" id="dokumenModalTitle">Tambah Dokumen</h5><small class="text-muted">Link Google Drive manual. File fisik tidak diupload ke SisFour.</small></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" name="id">
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">Tahun Ajaran / Semester</label><select id="dokumenFormTahun" class="form-select" name="id_tahun" required><?php foreach(($initial['tahun_options']??[]) as $ta):?><option value="<?= (int)$ta['id'] ?>" <?= (int)($initial['tahun_dipilih']['id']??0)===(int)$ta['id']?'selected':'' ?>><?= esc($ta['nama_tahun'].' - '.$ta['semester']) ?></option><?php endforeach;?></select></div>
          <div class="col-md-3"><label class="form-label">Target</label><select id="dokumenTarget" class="form-select" name="target_type" required><option value="INDIVIDU">Individu</option><option value="TINGKAT">Tingkat</option></select></div>
          <div class="col-md-3"><label class="form-label">Format</label><select class="form-select" name="format_file" required><option value="PDF">PDF</option><option value="IMAGE">Image (JPG/JPEG/PNG)</option></select></div>
          <div class="col-12" id="dokumenStudentField"><label class="form-label">Siswa pada periode terpilih</label><div class="input-group mb-2"><input id="dokumenStudentQuery" class="form-control" placeholder="Ketik nama / NISN minimal 2 karakter"><button id="btnCariDokumenSiswa" class="btn btn-outline-primary" type="button">Cari</button></div><select id="dokumenIdSiswa" class="form-select" name="id_siswa"><option value="">Pilih hasil pencarian</option></select></div>
          <div class="col-md-4 d-none" id="dokumenLevelField"><label class="form-label">Tingkat</label><select class="form-select" name="tingkat"><option value="">Pilih</option><option value="7">7</option><option value="8">8</option><option value="9">9</option></select></div>
          <div class="col-12"><label class="form-label">Judul Dokumen</label><input class="form-control" name="judul" maxlength="200" required></div>
          <div class="col-12"><label class="form-label">Link Google Drive</label><input class="form-control" type="url" name="link_gdrive" placeholder="https://drive.google.com/..." required><div class="form-text">Hanya HTTPS drive.google.com atau docs.google.com.</div></div>
          <div class="col-md-4"><label class="form-label">Status</label><select class="form-select" name="status"><option value="PUBLISHED">Published</option><option value="ARCHIVED">Archived</option></select></div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
    </form></div></div>

    <div class="modal fade" id="modalBulkDokumen" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
      <div class="modal-header"><div><h5 class="modal-title">Bulk Import Dokumen Individu</h5><small class="text-muted">Satu batch = satu judul + satu exact semester + satu format. XLSX berisi NISN + Link Google Drive.</small></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="card bg-label-secondary mb-4"><div class="card-body">
          <h6>1. Download Template</h6>
          <div class="row g-3 align-items-end"><div class="col-md-4"><label class="form-label">Periode</label><select id="templateTahun" class="form-select"><?php foreach(($initial['tahun_options']??[]) as $ta):?><option value="<?= (int)$ta['id'] ?>" <?= (int)($initial['tahun_dipilih']['id']??0)===(int)$ta['id']?'selected':'' ?>><?= esc($ta['nama_tahun'].' - '.$ta['semester']) ?></option><?php endforeach;?></select></div>
          <div class="col-md-3"><label class="form-label">Filter</label><select id="templateFilter" class="form-select"><option value="all">Semua Siswa</option><option value="tingkat">Per Tingkat</option><option value="kelas">Per Kelas</option></select></div>
          <div class="col-md-2 d-none" id="templateLevelWrap"><label class="form-label">Tingkat</label><select id="templateTingkat" class="form-select"><option value="7">7</option><option value="8">8</option><option value="9">9</option></select></div>
          <div class="col-md-3 d-none" id="templateClassWrap"><label class="form-label">Kelas</label><select id="templateKelas" class="form-select"></select></div>
          <div class="col-md-3"><button id="btnDownloadTemplateDokumen" class="btn btn-outline-primary w-100" type="button"><i class="bx bx-download me-1"></i> Download Template</button></div></div>
        </div></div>

        <form id="formBulkDokumen">
          <h6>2. Isi Context dan Upload XLSX</h6>
          <div class="row g-3"><div class="col-md-5"><label class="form-label">Judul Dokumen</label><input class="form-control" name="judul" maxlength="200" required placeholder="Contoh: Rapor Semester Ganjil"></div>
          <div class="col-md-4"><label class="form-label">Tahun Ajaran / Semester</label><select class="form-select" name="id_tahun" required><?php foreach(($initial['tahun_options']??[]) as $ta):?><option value="<?= (int)$ta['id'] ?>" <?= (int)($initial['tahun_dipilih']['id']??0)===(int)$ta['id']?'selected':'' ?>><?= esc($ta['nama_tahun'].' - '.$ta['semester']) ?></option><?php endforeach;?></select></div>
          <div class="col-md-3"><label class="form-label">Format</label><select class="form-select" name="format_file" required><option value="PDF">PDF</option><option value="IMAGE">Image (JPG/JPEG/PNG)</option></select></div>
          <div class="col-12"><label class="form-label">File XLSX</label><input class="form-control" type="file" name="file" accept=".xlsx" required></div></div>
          <div class="text-end mt-3"><button class="btn btn-primary" type="submit"><i class="bx bx-search-alt me-1"></i> Preview Import</button></div>
        </form>

        <div id="bulkDokumenPreview" class="d-none mt-4">
          <hr><div class="d-flex flex-wrap gap-2 mb-3"><span class="badge bg-label-primary" id="bulkTotal">0 total</span><span class="badge bg-label-success" id="bulkValid">0 valid</span><span class="badge bg-label-danger" id="bulkError">0 error</span></div>
          <div id="bulkWarnings" class="alert alert-warning d-none"></div><div id="bulkErrors" class="alert alert-danger d-none"></div>
          <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Row</th><th>NISN</th><th>Nama</th><th>Kelas</th><th>Link</th></tr></thead><tbody id="bulkPreviewBody"></tbody></table></div>
          <div class="text-end"><button id="btnCommitBulkDokumen" class="btn btn-success d-none" type="button"><i class="bx bx-check me-1"></i> Commit Import</button></div>
        </div>
      </div>
    </div></div></div>
    <?php endif; ?>
  <?php endif; ?>
</div>
<?= $this->endSection() ?>
