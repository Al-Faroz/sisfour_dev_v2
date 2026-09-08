<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<?php
$settings = $initial['settings'] ?? [];
$getSetting = static function (string $key, string $default = '') use ($settings): string {
    return (string) ($settings[$key]['setting_value'] ?? $default);
};
?>

<div id="settingsSistemApp" data-base-url="<?= esc(base_url()) ?>">
    <div class="mb-4">
        <h4 class="fw-bold mb-1">Setting Sistem</h4>
        <p class="text-muted mb-0">Geofence, identitas sekolah, branding, template Kartu Pelajar, dan maintenance.</p>
    </div>

    <div id="sistemAlert" class="alert d-none"></div>

    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Identitas & Geofencing</h5></div>
        <form id="formSistem" class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nama Sekolah / Madrasah</label>
                    <input name="nama_sekolah" class="form-control" maxlength="200"
                        value="<?= esc($getSetting('nama_sekolah', 'MTsN 4 Jombang')) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Alamat Sekolah</label>
                    <input name="alamat_sekolah" class="form-control"
                        value="<?= esc($getSetting('alamat_sekolah')) ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Latitude</label>
                    <input name="latitude_sekolah" type="number" step="0.0000001" min="-90" max="90"
                        class="form-control" value="<?= esc($getSetting('latitude_sekolah', '0')) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Longitude</label>
                    <input name="longitude_sekolah" type="number" step="0.0000001" min="-180" max="180"
                        class="form-control" value="<?= esc($getSetting('longitude_sekolah', '0')) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Radius Geofence (meter)</label>
                    <input name="radius_geofencing" type="number" step="0.1" min="0.1"
                        class="form-control" value="<?= esc($getSetting('radius_geofencing', '500')) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label d-block">Geofencing</label>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" name="geofencing_aktif" value="1"
                            <?= $getSetting('geofencing_aktif', '0') === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label">Aktif</label>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label">Pesan Maintenance</label>
                    <textarea name="maintenance_message" class="form-control" rows="2"><?= esc($getSetting('maintenance_message', 'Sistem sedang dalam pemeliharaan...')) ?></textarea>
                </div>
            </div>
            <div class="mt-3">
                <button class="btn btn-primary" type="submit">Simpan Setting</button>
            </div>
        </form>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Branding</h5></div>
        <div class="card-body">
            <div class="row g-4">
                <?php foreach (['logo' => 'Logo Sekolah', 'icon' => 'Icon/Favicon'] as $type => $label): ?>
                <div class="col-md-6">
                    <form class="branding-form" data-type="<?= esc($type) ?>">
                        <label class="form-label"><?= esc($label) ?></label>
                        <input type="file" name="file" accept="image/png,image/jpeg,image/webp" class="form-control" required>
                        <button class="btn btn-outline-primary mt-2" type="submit">Upload <?= esc($label) ?></button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="form-text mt-3">File akan divalidasi sebagai image dan di-re-encode sebelum disimpan.</div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Template Kartu Pelajar</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
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
                            <button class="btn btn-outline-primary mt-2" type="submit">Upload <?= esc($label) ?></button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="card border-warning">
        <div class="card-header"><h5 class="mb-0">Maintenance Mode</h5></div>
        <form id="formMaintenance" class="card-body">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="maintenance_mode" value="1"
                    id="maintenanceMode"
                    <?= $getSetting('maintenance_mode', '0') === '1' ? 'checked' : '' ?>>
                <label class="form-check-label" for="maintenanceMode">
                    Aktifkan Maintenance Mode
                </label>
            </div>
            <input type="hidden" name="maintenance_message" value="<?= esc($getSetting('maintenance_message', 'Sistem sedang dalam pemeliharaan...')) ?>">
            <div class="form-text">Admin tetap dapat masuk. Enforcement untuk role non-Admin dilakukan oleh MaintenanceFilter saat modul Maintenance difinalisasi.</div>
            <button class="btn btn-warning mt-3" type="submit">Terapkan Maintenance</button>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
