<?= $this->extend('main') ?>
<?= $this->section('content') ?>
<div id="bkKasusKelompokApp" data-base-url="<?= esc(base_url()) ?>">
  <div class="sisfour-page-header">
    <div class="sisfour-page-header__copy">
      <h4 class="fw-bold mb-1">Pelanggaran Kelompok</h4>
      <p class="text-muted mb-0">Satu kejadian kelompok menghasilkan Catatan Pelanggaran individual untuk setiap siswa.</p>
    </div>
    <div class="sisfour-page-actions">
      <a class="btn sisfour-action sisfour-action--compact sisfour-action--blue" href="<?= esc(base_url('bk/kasus')) ?>"><i class="bx bx-left-arrow-alt me-1"></i> Catatan Individu</a>
      <?php if (! empty($initial['success'])): ?>
        <button class="btn sisfour-action sisfour-action--compact sisfour-action--indigo" type="button" id="btnKasusKelompokBaru"><i class="bx bx-group me-1"></i> Tambah Kelompok</button>
      <?php endif; ?>
    </div>
  </div>

  <?php if (empty($initial['success'])): ?>
    <div class="alert alert-danger"><?= esc($initial['message'] ?? 'Pelanggaran Kelompok tidak dapat dibuka.') ?></div>
  <?php else: ?>
    <div class="card sisfour-filter-card mb-4"><div class="card-body">
      <form method="get" class="row g-3 align-items-end">
        <div class="col-12 col-md-4">
          <label class="form-label" for="groupKasusTahun">Tahun Ajaran</label>
          <select id="groupKasusTahun" name="id_tahun" class="form-select" data-searchable-off="1">
            <?php foreach (($initial['tahun_options'] ?? []) as $ta): ?>
              <?php $aktif=(int)($ta['status_aktif']??0)===1; ?>
              <option value="<?= (int)$ta['id'] ?>" <?= (int)($initial['tahun_dipilih']['id']??0)===(int)$ta['id']?'selected':'' ?>>
                <?= esc($ta['nama_tahun'].' - '.$ta['semester'].($aktif?' (Aktif)':'')) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-12 col-md-5"><label class="form-label" for="groupKasusSearch">Pencarian</label><input id="groupKasusSearch" name="search" class="form-control" value="<?= esc((string)(service('request')->getGet('search') ?? '')) ?>" placeholder="Pelanggaran / keterangan"></div>
        <div class="col-12 col-md-3"><button class="btn btn-primary w-100" type="submit"><i class="bx bx-filter-alt me-1"></i> Tampilkan</button></div>
      </form>
    </div></div>

    <div id="groupKasusAlert" class="alert d-none" role="alert"></div>
    <div class="card sisfour-table-card">
      <div class="card-header d-flex justify-content-between align-items-center gap-2">
        <h5 class="mb-0">Riwayat Kejadian Kelompok</h5>
        <span class="text-muted small"><?= number_format((int)($initial['total']??0),0,',','.') ?> kejadian</span>
      </div>
      <div class="d-md-none list-group list-group-flush">
        <?php foreach (($initial['rows']??[]) as $row): ?>
          <button type="button" class="list-group-item list-group-item-action btn-group-kasus-detail text-start" data-id="<?= (int)$row['id'] ?>">
            <div class="d-flex justify-content-between gap-2"><strong><?= esc($row['nama_pelanggaran']??'-') ?></strong><span class="badge bg-label-secondary"><?= (int)($row['jumlah_anggota']??0) ?> siswa</span></div>
            <div class="small text-muted mt-1"><?= esc($row['tanggal']??'-') ?> · <?= esc($row['kategori']??'-') ?></div>
            <?php if (! empty($row['keterangan'])): ?><div class="small mt-1"><?= esc($row['keterangan']) ?></div><?php endif; ?>
          </button>
        <?php endforeach; ?>
      </div>
      <div class="d-none d-md-block table-responsive">
        <table class="table table-hover align-middle mb-0"><thead><tr><th>Tanggal</th><th>Pelanggaran</th><th>Kategori</th><th>Anggota</th><th>Keterangan</th><th class="text-end">Aksi</th></tr></thead><tbody>
          <?php if (($initial['rows']??[])===[]): ?><tr><td colspan="6" class="text-center text-muted py-4">Belum ada Pelanggaran Kelompok pada periode ini.</td></tr><?php endif; ?>
          <?php foreach (($initial['rows']??[]) as $row): ?><tr>
            <td><?= esc($row['tanggal']??'-') ?></td><td><?= esc($row['nama_pelanggaran']??'-') ?></td>
            <td><span class="badge bg-label-secondary"><?= esc($row['kategori']??'-') ?></span></td>
            <td><?= (int)($row['jumlah_anggota']??0) ?> siswa</td><td><?= esc($row['keterangan']??'-') ?></td>
            <td class="text-end"><button class="btn btn-sm btn-outline-primary btn-group-kasus-detail" type="button" data-id="<?= (int)$row['id'] ?>">Detail</button></td>
          </tr><?php endforeach; ?>
        </tbody></table>
      </div>
    </div>

    <div class="modal fade" id="modalKasusKelompokBaru" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-scrollable"><form class="modal-content" id="formKasusKelompok">
      <div class="modal-header"><div><h5 class="modal-title mb-1">Tambah Pelanggaran Kelompok</h5><small class="text-muted">Minimal 2 siswa. Seluruh anggota disimpan dalam satu transaksi.</small></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-3"><label class="form-label" for="groupKasusSiswa">Cari Siswa</label><div class="input-group"><select id="groupKasusSiswa" class="form-select" data-searchable-remote="<?= esc(base_url('ui/search/siswa')) ?>" data-searchable-context="bk_kasus" data-searchable-min-chars="2"><option value="">Cari nama / NISN</option></select><button class="btn btn-outline-primary" id="btnTambahGroupKasusSiswa" type="button">Tambah</button></div></div>
        <div class="border rounded p-2 mb-3"><div class="small fw-semibold mb-2">Anggota dipilih <span id="groupKasusCount" class="badge bg-label-primary">0</span></div><div id="groupKasusMembers" class="d-flex flex-column gap-2"><span class="text-muted small">Belum ada siswa dipilih.</span></div></div>
        <div class="row g-3">
          <div class="col-12 col-md-6"><label class="form-label">Tanggal</label><input class="form-control" type="date" name="tanggal" required></div>
          <div class="col-12 col-md-6"><label class="form-label">Jenis Pelanggaran</label><select class="form-select" name="id_pelanggaran" required><option value="">Pilih pelanggaran</option><?php foreach(($initial['pelanggaran']??[]) as $p): ?><option value="<?= (int)$p['id'] ?>"><?= esc($p['kategori'].' — '.$p['nama_pelanggaran']) ?></option><?php endforeach; ?></select></div>
          <div class="col-12"><label class="form-label">Keterangan</label><textarea class="form-control" name="keterangan" rows="3"></textarea></div>
        </div>
      </div>
      <div class="modal-footer"><button class="btn btn-label-secondary" type="button" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" type="submit">Simpan Kelompok</button></div>
    </form></div></div>

    <div class="modal fade" id="modalKasusKelompokDetail" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Detail Pelanggaran Kelompok</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body"><div id="groupKasusDetailLoading" class="text-muted">Memuat data...</div><div id="groupKasusDetailContent" class="d-none">
        <div class="mb-3"><strong id="groupKasusDetailTitle">-</strong><div id="groupKasusDetailMeta" class="text-muted small"></div><div id="groupKasusDetailNote" class="mt-2"></div></div>
        <h6>Anggota</h6><div id="groupKasusDetailMembers" class="list-group mb-4"></div>
        <h6>Riwayat Tindak Lanjut</h6><div id="groupKasusDetailFollow" class="list-group mb-4"></div>
        <form id="formKasusKelompokFollow" class="border rounded p-3">
          <input type="hidden" name="id_kelompok">
          <div class="small text-muted mb-3">Tindak lanjut ini akan dibuat pada seluruh Catatan Pelanggaran anggota.</div>
          <div class="row g-3"><div class="col-md-4"><label class="form-label">Tanggal</label><input class="form-control" type="date" name="tanggal" required></div>
          <div class="col-md-8"><label class="form-label">Tindak Lanjut</label><select class="form-select" name="tindak_lanjut" required><option value="">Pilih</option><?php foreach(($initial['tindak_lanjut_options']??[]) as $opt): ?><option value="<?= esc($opt,'attr') ?>"><?= esc($opt) ?></option><?php endforeach; ?></select></div>
          <div class="col-12"><label class="form-label">Keterangan</label><textarea class="form-control" name="keterangan" rows="2"></textarea></div></div>
          <div class="text-end mt-3"><button class="btn btn-primary" type="submit">Terapkan ke Seluruh Anggota</button></div>
        </form>
      </div></div>
    </div></div></div>
  <?php endif; ?>
</div>
<?= $this->endSection() ?>
