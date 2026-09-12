<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<?php
$rekap = $widgets['rekap_presensi_bulan_ini'] ?? [];
$kartu = $widgets['kartu'] ?? null;
$presensiHariIni = $widgets['presensi_hari_ini'] ?? null;
$kelasAktif = $widgets['kelas_aktif'] ?? [];
$tahun = $widgets['tahun_aktif'] ?? [];
$statusHariIni = trim((string) ($presensiHariIni['status'] ?? ''));
$statusClass = [
    'Hadir' => 'success',
    'Sakit' => 'warning',
    'Izin' => 'info',
    'Alpha' => 'danger',
][$statusHariIni] ?? 'secondary';
$tanggalHariIni = trim((string) ($presensiHariIni['tanggal'] ?? ''));
$namaWali = trim((string) ($kelasAktif['nama_wali'] ?? ''));
$noTeleponWali = trim((string) ($kelasAktif['no_telepon_wali'] ?? ''));
$whatsappNumber = trim((string) ($kelasAktif['whatsapp_number'] ?? ''));
?>

<div class="sisfour-dashboard">
  <div class="sf-dashboard-header">
    <div>
      <h4>Dashboard Siswa</h4>
      <p class="text-muted">Ringkasan informasi dan data milik sendiri.</p>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
      <span class="badge bg-label-primary sf-year-badge">
        <?= esc(($tahun['nama_tahun'] ?? 'Tahun belum aktif') . (!empty($tahun['semester']) ? ' · ' . $tahun['semester'] : '')) ?>
      </span>
      <?php if (!empty($widgets['profile_available'])): ?>
        <a href="<?= base_url('profile/siswa') ?>" class="btn btn-sm btn-outline-secondary">
          <i class="bx bx-user me-1"></i>Profil Saya
        </a>
      <?php endif; ?>
    </div>
  </div>

  <div class="sf-student-top-grid">
    <div class="card sf-student-top-card">
      <div class="card-body">
        <div class="sf-student-status">
          <div>
            <div class="text-muted small mb-1">Kehadiran Hari Ini</div>
            <div class="sf-student-status__title">Presensi Sesi Awal</div>
            <?php if ($tanggalHariIni !== ''): ?>
              <div class="small text-muted mt-1"><?= esc(date('d-m-Y', strtotime($tanggalHariIni))) ?></div>
            <?php endif; ?>
          </div>

          <div class="text-end">
            <?php if (!empty($presensiHariIni['tersedia']) && $statusHariIni !== ''): ?>
              <span class="badge bg-label-<?= esc($statusClass) ?> fs-6 px-3 py-2">
                <?= esc($statusHariIni) ?>
              </span>
            <?php else: ?>
              <span class="badge bg-label-secondary">Belum tercatat</span>
            <?php endif; ?>
          </div>
        </div>

        <?php if (empty($presensiHariIni['tersedia'])): ?>
          <div class="small text-muted mt-2">Data Sesi Awal hari ini belum tersedia.</div>
        <?php endif; ?>
      </div>
    </div>

    <div class="card sf-student-top-card">
      <div class="card-body">
        <div class="text-muted small mb-1">Kelas Saya</div>

        <?php if (empty($kelasAktif)): ?>
          <div class="sf-student-class__name">Belum ditempatkan</div>
          <div class="small text-muted mt-1">Data kelas aktif belum tersedia.</div>
        <?php else: ?>
          <div class="sf-student-class__name"><?= esc($kelasAktif['nama_kelas'] ?? '-') ?></div>
          <div class="sf-student-class-meta mt-1">
            <span><i class="bx bx-user-pin me-1"></i>Wali: <?= esc($namaWali !== '' ? $namaWali : 'Belum tersedia') ?></span>
          </div>

          <div class="small text-muted mt-2">No. HP / WhatsApp Wali</div>
          <?php if ($noTeleponWali !== ''): ?>
            <div class="small fw-semibold"><?= esc($noTeleponWali) ?></div>
          <?php else: ?>
            <div class="small text-muted">Belum tersedia</div>
          <?php endif; ?>

          <?php if ($whatsappNumber !== ''): ?>
            <a
              class="sf-phone-link"
              href="https://wa.me/<?= esc($whatsappNumber, 'attr') ?>"
              target="_blank"
              rel="noopener noreferrer"
            >
              <i class="bx bxl-whatsapp"></i>
              Hubungi Wali Kelas
            </a>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="card sf-section-card">
    <div class="card-header d-flex justify-content-between align-items-center gap-2">
      <h5 class="mb-0">Rekap Presensi Bulan Ini</h5>
      <small class="text-muted">Sesi Awal</small>
    </div>
    <div class="card-body">
      <div class="sf-attendance-strip">
        <?php foreach ([
            ['Hadir', $rekap['hadir'] ?? 0, 'success'],
            ['Sakit', $rekap['sakit'] ?? 0, 'warning'],
            ['Izin', $rekap['izin'] ?? 0, 'info'],
            ['Alpha', $rekap['alpha'] ?? 0, 'danger'],
        ] as $item): ?>
          <div class="sf-attendance-item">
            <small><?= esc($item[0]) ?></small>
            <strong class="text-<?= esc($item[2]) ?>"><?= (int) $item[1] ?></strong>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-lg-6">
      <div class="card h-100 sf-section-card mb-0">
        <div class="card-header d-flex justify-content-between align-items-center gap-2">
          <h5 class="mb-0">Sakit / Izin / Alpha Terbaru</h5>
          <a href="<?= base_url('presensi/siswa/rekap') ?>" class="btn btn-sm btn-outline-primary">Lihat Rekap</a>
        </div>
        <div class="table-responsive">
          <table class="table table-sm sf-compact-table mb-0">
            <thead><tr><th>Tanggal</th><th>Status</th></tr></thead>
            <tbody>
              <?php if (empty($widgets['presensi_terbaru'])): ?>
                <tr><td colspan="2" class="text-center text-muted py-4">Tidak ada catatan.</td></tr>
              <?php else: ?>
                <?php foreach ($widgets['presensi_terbaru'] as $row): ?>
                  <?php $color = $row['status'] === 'Alpha' ? 'danger' : ($row['status'] === 'Sakit' ? 'warning' : 'info'); ?>
                  <tr>
                    <td><?= esc($row['tanggal']) ?></td>
                    <td><span class="badge bg-label-<?= esc($color) ?>"><?= esc($row['status']) ?></span></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card h-100 sf-section-card mb-0">
        <div class="card-header d-flex justify-content-between align-items-center gap-2">
          <h5 class="mb-0">Kartu Pelajar</h5>
          <a href="<?= base_url('kartu/daftar') ?>" class="btn btn-sm btn-outline-primary">Buka Kartu</a>
        </div>
        <div class="card-body">
          <?php if (empty($kartu)): ?>
            <p class="text-muted mb-0">Kartu pelajar belum tersedia.</p>
          <?php else: ?>
            <div class="d-flex justify-content-between gap-3 flex-wrap align-items-start">
              <div>
                <span class="badge bg-label-<?= ($kartu['status_aktif'] ?? '') === 'Aktif' ? 'success' : 'secondary' ?>">
                  <?= esc($kartu['status_aktif']) ?>
                </span>
                <strong class="d-block mt-2 mb-1"><?= esc($kartu['nomor_kartu']) ?></strong>
                <div class="small text-muted">Terbit <?= esc($kartu['tanggal_terbit']) ?></div>
              </div>

              <div class="d-flex flex-wrap gap-2">
                <a href="<?= base_url('kartu/preview/' . (int) $kartu['id']) ?>" class="btn btn-sm btn-outline-primary">
                  <i class="bx bx-show me-1"></i>Preview
                </a>
                <a href="<?= base_url('kartu/download/' . (int) $kartu['id']) ?>" class="btn btn-sm btn-primary">
                  <i class="bx bx-download me-1"></i>Unduh PDF
                </a>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-6">
      <div class="card h-100 sf-section-card mb-0">
        <div class="card-header"><h5 class="mb-0">Prestasi Terbaru</h5></div>
        <ul class="list-group list-group-flush sf-compact-list">
          <?php if (empty($widgets['riwayat_prestasi'])): ?>
            <li class="list-group-item text-muted text-center py-4">Belum ada prestasi tercatat.</li>
          <?php else: ?>
            <?php foreach ($widgets['riwayat_prestasi'] as $row): ?>
              <li class="list-group-item">
                <strong><?= esc($row['nama_prestasi']) ?></strong>
                <div class="small text-muted"><?= esc($row['tingkat'] ?? '-') ?> · <?= esc($row['tanggal']) ?></div>
              </li>
            <?php endforeach; ?>
          <?php endif; ?>
        </ul>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card h-100 sf-section-card mb-0">
        <div class="card-header"><h5 class="mb-0">Catatan Kasus Saya</h5></div>
        <ul class="list-group list-group-flush sf-compact-list">
          <?php if (empty($widgets['riwayat_pelanggaran'])): ?>
            <li class="list-group-item text-muted text-center py-4">Tidak ada catatan kasus.</li>
          <?php else: ?>
            <?php foreach ($widgets['riwayat_pelanggaran'] as $row): ?>
              <?php $kategoriColor = ($row['kategori'] ?? '') === 'Berat' ? 'danger' : (($row['kategori'] ?? '') === 'Sedang' ? 'warning' : 'secondary'); ?>
              <li class="list-group-item">
                <strong><?= esc($row['nama_pelanggaran']) ?></strong>
                <span class="badge bg-label-<?= esc($kategoriColor) ?>"><?= esc($row['kategori']) ?></span>
                <div class="small text-muted"><?= esc($row['tanggal']) ?> · <?= (int) $row['poin'] ?> poin</div>
                <?php if (!empty($row['keterangan'])): ?>
                  <div class="small mt-1"><?= esc($row['keterangan']) ?></div>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
