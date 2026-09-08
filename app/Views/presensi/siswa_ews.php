<?= $this->extend('main') ?>

<?= $this->section('content') ?>

<div id="ewsPresensiSiswaApp" data-base-url="<?= esc(base_url()) ?>">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">EWS Presensi Siswa</h4>
            <p class="text-muted mb-0">Siswa dengan minimal 3 Alpha pada Sesi Awal dalam 14 hari.</p>
        </div>

        <?php if (!empty($tahunAktif)): ?>
            <span class="badge bg-label-primary fs-6">
                <?= esc($tahunAktif['nama_tahun'] ?? '') ?>
                <?= esc($tahunAktif['semester'] ?? '') ?>
            </span>
        <?php endif; ?>
    </div>

    <div class="card mb-4">
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
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-radar me-1"></i> Muat EWS
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 60px;">No.</th>
                        <th>Siswa</th>
                        <th class="text-center">Total Alpha</th>
                    </tr>
                </thead>
                <tbody id="ewsTableBody">
                    <tr><td colspan="3" class="text-center text-muted py-4">Memuat data...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
