<?= $this->extend('main') ?>

<?= $this->section('content') ?>

<div id="ewsPresensiSiswaApp" data-base-url="<?= esc(base_url()) ?>">
    <div class="sisfour-page-header">
        <div class="sisfour-page-header__copy">
            <h4 class="fw-bold mb-1">EWS Presensi Siswa</h4>
            <p class="text-muted mb-0">Siswa dengan minimal 3 Alpha pada Sesi Awal dalam 14 hari.</p>
        </div>

        <?php if (!empty($tahunAktif)): ?>
            <div class="sisfour-page-actions">
                <span class="badge bg-label-primary fs-6">
                    <?= esc($tahunAktif['nama_tahun'] ?? '') ?>
                    <?= esc($tahunAktif['semester'] ?? '') ?>
                </span>
            </div>
        <?php endif; ?>
    </div>

    <div class="card sisfour-filter-card mb-4">
        <div class="card-body">
            <form id="formFilterEws" class="row g-3 align-items-end">
                <div class="col-6 col-md-3">
                    <label class="form-label" for="ewsTanggalMulai">Dari</label>
                    <input type="date" class="form-control" id="ewsTanggalMulai" value="<?= esc($tanggalMulai ?? '') ?>">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label" for="ewsTanggalSelesai">Sampai</label>
                    <input type="date" class="form-control" id="ewsTanggalSelesai" value="<?= esc($tanggalSelesai ?? '') ?>">
                </div>
                <div class="col-12 col-md-3 d-grid">
                    <button type="submit" class="btn btn-primary sisfour-primary-action">
                        <i class="bx bx-radar me-1"></i> Muat EWS
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card sisfour-table-card">
        <div class="card-header">
            <h5 class="mb-0">Daftar Siswa EWS</h5>
        </div>
        <div id="ewsMobileList" class="d-md-none list-group list-group-flush">
            <div class="list-group-item sisfour-mobile-state text-muted">Memuat data...</div>
        </div>
        <div class="d-none d-md-block table-responsive">
            <table class="table table-hover align-middle mb-0" id="tableEwsPresensi">
                <thead>
                    <tr>
                        <th style="width: 60px;">No.</th>
                        <th>Siswa</th>
                        <th class="text-center">Total Alpha</th>
                    </tr>
                </thead>
                <tbody id="ewsTableBody">
                    <tr class="sisfour-loading-row"><td colspan="3" class="text-muted">Memuat data...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>