<?= $this->extend('main') ?>
<?= $this->section('content') ?>
<div id="kartuApp" data-base-url="<?= esc(base_url()) ?>">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div><h4 class="fw-bold mb-1">Kartu Pelajar</h4><p class="text-muted mb-0">Preview, download, dan penerbitan selalu divalidasi server-side.</p></div>
    </div>

    <?php if (empty($initial['success'])): ?>
        <div class="alert alert-danger"><?= esc($initial['message'] ?? 'Data tidak dapat dibuka.') ?></div>
    <?php else: ?>
        <?php if (!empty($initial['can_manage'])): ?>
        <div class="card mb-4" id="terbitkan">
            <div class="card-header"><h5 class="mb-0">Terbitkan Kartu</h5></div>
            <div class="card-body">
                <form id="formGenerateKartu" class="row g-3 align-items-end">
                    <div class="col-md-9"><label class="form-label">Siswa Aktif</label><select name="id_siswa" class="form-select" required><option value="">Pilih siswa</option><?php foreach (($initial['eligible_students'] ?? []) as $s): ?><option value="<?= (int) $s['id'] ?>"><?= esc($s['nisn'] . ' — ' . $s['nama']) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-3 d-grid"><button class="btn btn-primary" type="submit">Generate / Cek Existing</button></div>
                </form>
                <div class="form-text mt-2">Jika siswa sudah memiliki kartu aktif, sistem tidak membuat identitas kartu baru.</div>
            </div>
        </div>
        <?php endif; ?>

        <div class="card mb-4"><div class="card-body"><div class="row g-3">
            <div class="col-md-6"><input id="kartuSearch" class="form-control" placeholder="Cari nama / NISN / nomor kartu"></div>
            <div class="col-md-3"><select id="kartuStatus" class="form-select"><option value="">Semua status</option><option>Aktif</option><option>Nonaktif</option></select></div>
            <div class="col-md-3 d-grid"><button id="btnKartuCari" class="btn btn-outline-primary">Tampilkan</button></div>
        </div></div></div>

        <div id="kartuAlert" class="alert d-none"></div>
        <div class="card">
            <div class="table-responsive"><table class="table table-hover align-middle">
                <thead><tr><th>Siswa</th><th>Nomor Kartu</th><th>Terbit</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                <tbody id="kartuBody"></tbody>
            </table></div>
            <div class="card-footer d-flex justify-content-between"><small id="kartuInfo" class="text-muted"></small><div class="btn-group"><button id="kartuPrev" class="btn btn-sm btn-outline-secondary">Sebelumnya</button><button id="kartuNext" class="btn btn-sm btn-outline-secondary">Berikutnya</button></div></div>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
