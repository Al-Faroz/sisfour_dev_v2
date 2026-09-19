<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<?php
$settings = $initial['settings'] ?? [];
$getSetting = static function (string $key, string $default = '') use ($settings): string {
    return (string) ($settings[$key]['setting_value'] ?? $default);
};

$brandingItems = [
    'logo' => [
        'label' => 'Logo Sekolah',
        'key' => 'logo_sekolah',
        'hint' => 'Digunakan pada identitas aplikasi dan halaman login.',
    ],
    'icon' => [
        'label' => 'Icon / Favicon',
        'key' => 'icon_sekolah',
        'hint' => 'Digunakan sebagai favicon browser dan touch icon.',
    ],
];

$ptspApiItems = [
    'layanan' => [
        'label' => 'Statistik Layanan PTSP',
        'icon' => 'bx-file',
        'endpoint' => base_url('api/public/ptsp/statistik/layanan'),
        'description' => 'Total layanan, status, kategori pemohon, dan jenis layanan.',
    ],
    'pengaduan' => [
        'label' => 'Statistik Pengaduan',
        'icon' => 'bx-message-square-error',
        'endpoint' => base_url('api/public/ptsp/statistik/pengaduan'),
        'description' => 'Total pengaduan, status, dan klasifikasi laporan.',
    ],
    'polling' => [
        'label' => 'Statistik Polling Kepuasan',
        'icon' => 'bx-happy',
        'endpoint' => base_url('api/public/ptsp/statistik/polling'),
        'description' => 'Total respon, rata-rata score, kategori, label kepuasan, dan distribusi score.',
    ],
];

$buildPtspApiScript = static function (string $endpoint): string {
    $url = json_encode($endpoint, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    return <<<JS
<script>
(async () => {
  const endpoint = {$url};

  // Default: statistik Tahun Ajaran aktif.
  // Untuk histori, gunakan: endpoint + '?id_tahun=ID_TAHUN'
  const response = await fetch(endpoint, {
    method: 'GET',
    headers: { Accept: 'application/json' }
  });

  if (!response.ok) {
    throw new Error('PTSP API HTTP ' + response.status);
  }

  const payload = await response.json();

  if (payload.status !== 'success') {
    throw new Error('PTSP API gagal.');
  }

  const statistics = payload.data;
  console.log('PTSP statistics:', statistics);

  // Render statistics ke UI portal/WordPress Anda di sini.
})();
</script>
JS;
};
?>

<div id="settingsSistemApp" data-base-url="<?= esc(base_url()) ?>">
    <div class="sisfour-page-header">
        <div class="sisfour-page-header__copy">
            <h4 class="fw-bold mb-1">Setting Sistem</h4>
            <p class="text-muted mb-0">Geofence, identitas sekolah, branding, template Kartu Pelajar, dan maintenance.</p>
        </div>
    </div>

    <div id="sistemAlert" class="alert d-none" role="alert"></div>

    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Identitas &amp; Geofencing</h5></div>
        <form id="formSistem">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="namaSekolah">Nama Sekolah / Madrasah</label>
                        <input id="namaSekolah" name="nama_sekolah" class="form-control" maxlength="200"
                            value="<?= esc($getSetting('nama_sekolah', 'MTsN 4 Jombang')) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="alamatSekolah">Alamat Sekolah</label>
                        <input id="alamatSekolah" name="alamat_sekolah" class="form-control"
                            value="<?= esc($getSetting('alamat_sekolah')) ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="latitudeSekolah">Latitude</label>
                        <input id="latitudeSekolah" name="latitude_sekolah" type="number" step="0.0000001" min="-90" max="90"
                            class="form-control" value="<?= esc($getSetting('latitude_sekolah', '0')) ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="longitudeSekolah">Longitude</label>
                        <input id="longitudeSekolah" name="longitude_sekolah" type="number" step="0.0000001" min="-180" max="180"
                            class="form-control" value="<?= esc($getSetting('longitude_sekolah', '0')) ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="radiusGeofence">Radius Geofence (meter)</label>
                        <input id="radiusGeofence" name="radius_geofencing" type="number" step="0.1" min="0.1"
                            class="form-control" value="<?= esc($getSetting('radius_geofencing', '500')) ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label d-block" for="geofencingAktif">Geofencing</label>
                        <div class="form-check form-switch mt-2">
                            <input id="geofencingAktif" class="form-check-input" type="checkbox" name="geofencing_aktif" value="1"
                                <?= $getSetting('geofencing_aktif', '0') === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="geofencingAktif">Aktif</label>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="maintenanceMessage">Pesan Maintenance</label>
                        <textarea id="maintenanceMessage" name="maintenance_message" class="form-control" rows="2"><?= esc($getSetting('maintenance_message', 'Sistem sedang dalam pemeliharaan...')) ?></textarea>
                    </div>

                    <div class="col-12">
                        <div class="border rounded p-3">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                                <div>
                                    <h6 class="mb-1">PTSP — Cetak Bukti Layanan</h6>
                                    <div class="small text-muted">
                                        Jika aktif, browser langsung membuka dialog cetak setelah Form Layanan PTSP berhasil disimpan.
                                        Template cetak menggunakan lebar kertas thermal 80 mm.
                                    </div>
                                </div>
                                <div class="d-flex flex-column gap-2">
                                    <div class="form-check form-switch">
                                        <input
                                            id="ptspLayananAutoPrint"
                                            class="form-check-input"
                                            type="checkbox"
                                            name="ptsp_layanan_auto_print"
                                            value="1"
                                            <?= $getSetting('ptsp_layanan_auto_print', '0') === '1' ? 'checked' : '' ?>
                                        >
                                        <label class="form-check-label" for="ptspLayananAutoPrint">Auto Print</label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input
                                            id="ptspLayananAutoDownloadPdf"
                                            class="form-check-input"
                                            type="checkbox"
                                            name="ptsp_layanan_auto_download_pdf"
                                            value="1"
                                            <?= $getSetting('ptsp_layanan_auto_download_pdf', '0') === '1' ? 'checked' : '' ?>
                                        >
                                        <label class="form-check-label" for="ptspLayananAutoDownloadPdf">Auto Download PDF 80mm</label>
                                    </div>
                                </div>
                            </div>
                            <div class="form-text mt-2">
                                Default keduanya OFF. Prioritas: Auto Print ON membuka dialog print.
                                Jika Auto Print OFF dan Auto Download PDF ON, bukti PDF thermal 80mm otomatis diunduh.
                                Tombol cetak manual tetap tersedia pada semua mode.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer sisfour-modal-actions">
                <button class="btn btn-primary" type="submit">
                    <i class="bx bx-save me-1"></i> Simpan Setting
                </button>
            </div>
        </form>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                <div>
                    <h5 class="mb-1">API Statistik PTSP</h5>
                    <div class="small text-muted">
                        Endpoint public aggregate-only untuk portal/WordPress. Default membaca Tahun Ajaran aktif dan dapat difilter dengan <code>?id_tahun=ID_TAHUN</code>.
                    </div>
                </div>
                <span class="badge bg-label-success">PUBLIC API</span>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <?php foreach ($ptspApiItems as $apiKey => $api): ?>
                    <?php $apiScript = $buildPtspApiScript($api['endpoint']); ?>
                    <div class="col-12 col-xl-4">
                        <div class="border rounded p-3 h-100 d-flex flex-column">
                            <div class="d-flex align-items-start gap-3 mb-3">
                                <span class="avatar-initial rounded bg-label-primary p-2">
                                    <i class="bx <?= esc($api['icon']) ?> fs-4"></i>
                                </span>
                                <div class="min-w-0 flex-grow-1">
                                    <h6 class="mb-1"><?= esc($api['label']) ?></h6>
                                    <div class="small text-muted"><?= esc($api['description']) ?></div>
                                </div>
                            </div>

                            <div class="d-flex flex-wrap gap-1 mb-2">
                                <span class="badge bg-label-primary">GET</span>
                                <span class="badge bg-label-success">PUBLIC</span>
                                <span class="badge bg-label-info">CORS *</span>
                                <span class="badge bg-label-secondary">AGGREGATE ONLY</span>
                            </div>

                            <label class="form-label small mb-1">Endpoint</label>
                            <div class="input-group input-group-sm mb-3">
                                <input
                                    class="form-control font-monospace"
                                    type="text"
                                    value="<?= esc($api['endpoint'], 'attr') ?>"
                                    readonly
                                    aria-label="<?= esc($api['label'], 'attr') ?>"
                                >
                                <button
                                    class="btn btn-outline-secondary api-copy-endpoint"
                                    type="button"
                                    data-copy-value="<?= esc($api['endpoint'], 'attr') ?>"
                                >
                                    <i class="bx bx-copy me-1"></i> Copy URL
                                </button>
                            </div>

                            <label class="form-label small mb-1">Script API</label>
                            <pre class="bg-dark text-white rounded p-3 small mb-3 flex-grow-1 overflow-auto" style="max-height:260px"><code><?= esc($apiScript) ?></code></pre>
                            <textarea id="ptspApiScript<?= esc(ucfirst($apiKey), 'attr') ?>" class="d-none" aria-hidden="true"><?= esc($apiScript) ?></textarea>

                            <button
                                class="btn btn-primary api-copy-script"
                                type="button"
                                data-source-id="ptspApiScript<?= esc(ucfirst($apiKey), 'attr') ?>"
                            >
                                <i class="bx bx-copy-alt me-1"></i> Copy Script API
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="alert alert-info sisfour-compact-note mt-3 mb-0">
                API ini tidak membutuhkan login/token dan tidak mengirim nama, WhatsApp, isi laporan, lampiran, atau raw record ID.
                Konsumen lintas-origin menggunakan <code>GET</code> tanpa credentials.
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-1">Branding</h5>
            <div class="small text-muted">Preview menunjukkan file yang benar-benar tersedia dan sedang digunakan.</div>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <?php foreach ($brandingItems as $type => $meta): ?>
                    <?php
                    $current = trim($getSetting($meta['key']));
                    $relative = ltrim($current, '/\\');
                    $absolute = $relative !== '' ? FCPATH . $relative : '';
                    $exists = $absolute !== '' && is_file($absolute);
                    $version = $exists ? @filemtime($absolute) : false;
                    $previewUrl = $exists
                        ? base_url($relative) . ($version ? '?v=' . $version : '')
                        : '';
                    ?>
                    <div class="col-12 col-md-6">
                        <form class="branding-form border rounded p-3 h-100" data-type="<?= esc($type) ?>">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div
                                    class="border rounded d-flex align-items-center justify-content-center bg-light"
                                    style="width:72px;height:72px;flex:0 0 72px;overflow:hidden"
                                >
                                    <?php if ($exists): ?>
                                        <img
                                            src="<?= esc($previewUrl, 'attr') ?>"
                                            alt="<?= esc($meta['label'], 'attr') ?>"
                                            style="max-width:100%;max-height:100%;object-fit:contain"
                                        >
                                    <?php else: ?>
                                        <i class="bx <?= $type === 'icon' ? 'bx-image-alt' : 'bx-buildings' ?> fs-2 text-muted"></i>
                                    <?php endif; ?>
                                </div>

                                <div class="min-w-0">
                                    <h6 class="mb-1"><?= esc($meta['label']) ?></h6>
                                    <div class="small text-muted mb-1"><?= esc($meta['hint']) ?></div>
                                    <code class="small text-break">
                                        <?= esc($current !== '' ? $current : 'Belum di-upload') ?>
                                    </code>
                                </div>
                            </div>

                            <?php if ($current !== '' && !$exists): ?>
                                <div class="alert alert-warning sisfour-compact-note small">
                                    Setting menyimpan path ini, tetapi file fisiknya tidak ditemukan. Sistem akan memakai fallback sampai file di-upload ulang.
                                </div>
                            <?php endif; ?>

                            <label class="form-label" for="brandingFile<?= esc(ucfirst($type), 'attr') ?>">
                                Upload <?= esc($meta['label']) ?> Baru
                            </label>
                            <input
                                id="brandingFile<?= esc(ucfirst($type), 'attr') ?>"
                                type="file"
                                name="file"
                                accept="image/png,image/jpeg,image/webp"
                                class="form-control"
                                required
                            >
                            <button class="btn btn-outline-primary mt-3" type="submit">
                                <i class="bx bx-upload me-1"></i> Upload <?= esc($meta['label']) ?>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="form-text mt-3">
                File divalidasi sebagai image dan di-re-encode menjadi PNG. Setelah upload berhasil halaman dimuat ulang agar branding baru langsung digunakan.
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Template Kartu Pelajar</h5></div>
        <div class="card-body">
            <div class="alert alert-info sisfour-compact-note">
                Template akan dinormalisasi ke <strong>1011 × 638 px</strong>.
                Depan menjadi background overlay data siswa; belakang dicetak statis tanpa overlay.
            </div>
            <div class="row g-4">
                <?php foreach (['depan' => 'Template Depan', 'belakang' => 'Template Belakang'] as $side => $label): ?>
                <div class="col-md-6">
                    <div class="border rounded p-3 h-100">
                        <h6><?= esc($label) ?></h6>
                        <?php
                        $key = $side === 'depan' ? 'background_kta_depan' : 'background_kta_belakang';
                        $current = $getSetting($key);
                        ?>
                        <div class="small text-muted mb-2">
                            Current: <code><?= esc($current !== '' ? $current : 'fallback default') ?></code>
                        </div>
                        <form class="kta-form" data-side="<?= esc($side) ?>">
                            <input type="file" name="file" accept="image/png,image/jpeg,image/webp" class="form-control" required>
                            <button class="btn btn-outline-primary mt-3" type="submit">
                                <i class="bx bx-upload me-1"></i> Upload <?= esc($label) ?>
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="card border-warning">
        <div class="card-header"><h5 class="mb-0">Maintenance Mode</h5></div>
        <form id="formMaintenance">
            <div class="card-body">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="maintenance_mode" value="1"
                        id="maintenanceMode"
                        <?= $getSetting('maintenance_mode', '0') === '1' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="maintenanceMode">Aktifkan Maintenance Mode</label>
                </div>
                <input type="hidden" name="maintenance_message" value="<?= esc($getSetting('maintenance_message', 'Sistem sedang dalam pemeliharaan...')) ?>">
                <div class="form-text mt-2">
                    Admin efektif tetap dapat login dan mengakses sistem.
                    Pengguna non-Admin Web/API akan langsung dibatasi dengan HTTP 503 selama Maintenance Mode aktif.
                </div>
            </div>
            <div class="card-footer sisfour-modal-actions">
                <button class="btn btn-warning" type="submit">
                    <i class="bx bx-wrench me-1"></i> Terapkan Maintenance
                </button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>