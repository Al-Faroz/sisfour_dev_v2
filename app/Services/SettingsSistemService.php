<?php

namespace App\Services;

use App\Models\SettingSistemModel;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\I18n\Time;
use Throwable;

class SettingsSistemService
{
    private const TZ = 'Asia/Jakarta';
    private const MAX_IMAGE_BYTES = 5_242_880;

    protected SettingSistemModel $model;

    public function __construct()
    {
        $this->model = new SettingSistemModel();
    }

    public function page(): array
    {
        return [
            'success' => true,
            'settings' => $this->model->allAssoc(),
        ];
    }

    public function update(int $userId, array $input): array
    {
        $lat = trim((string) ($input['latitude_sekolah'] ?? ''));
        $lng = trim((string) ($input['longitude_sekolah'] ?? ''));
        $radius = trim((string) ($input['radius_geofencing'] ?? ''));
        $geofence = $this->boolValue($input['geofencing_aktif'] ?? '0');
        $nama = trim((string) ($input['nama_sekolah'] ?? ''));
        $alamat = trim((string) ($input['alamat_sekolah'] ?? ''));
        $message = trim((string) ($input['maintenance_message'] ?? ''));

        if (!is_numeric($lat) || (float) $lat < -90 || (float) $lat > 90) {
            return $this->fail('VALIDATION', 'Latitude harus berada pada -90 sampai 90.');
        }

        if (!is_numeric($lng) || (float) $lng < -180 || (float) $lng > 180) {
            return $this->fail('VALIDATION', 'Longitude harus berada pada -180 sampai 180.');
        }

        if (!is_numeric($radius) || (float) $radius <= 0 || (float) $radius > 100000) {
            return $this->fail('VALIDATION', 'Radius geofencing harus lebih dari 0.');
        }

        if ($nama === '' || mb_strlen($nama) > 200) {
            return $this->fail('VALIDATION', 'Nama sekolah wajib diisi maksimal 200 karakter.');
        }

        if (mb_strlen($alamat) > 1000 || mb_strlen($message) > 1000) {
            return $this->fail('VALIDATION', 'Alamat atau pesan maintenance terlalu panjang.');
        }

        $this->setMany($userId, [
            'latitude_sekolah' => [(string) (float) $lat, 'decimal'],
            'longitude_sekolah' => [(string) (float) $lng, 'decimal'],
            'radius_geofencing' => [(string) (float) $radius, 'decimal'],
            'geofencing_aktif' => [$geofence ? '1' : '0', 'boolean'],
            'nama_sekolah' => [$nama, 'string'],
            'alamat_sekolah' => [$alamat, 'string'],
            'maintenance_message' => [$message, 'string'],
        ]);

        $this->clearCache();
        $this->log($userId, 'UPDATE', 'Memperbarui Setting Sistem.');

        return [
            'success' => true,
            'message' => 'Setting Sistem berhasil diperbarui.',
        ];
    }

    public function maintenance(int $userId, array $input): array
    {
        $enabled = $this->boolValue($input['maintenance_mode'] ?? '0');
        $message = trim((string) ($input['maintenance_message'] ?? ''));

        if (mb_strlen($message) > 1000) {
            return $this->fail('VALIDATION', 'Pesan maintenance terlalu panjang.');
        }

        $this->setMany($userId, [
            'maintenance_mode' => [$enabled ? '1' : '0', 'boolean'],
            'maintenance_message' => [$message, 'string'],
        ]);

        $this->clearCache();
        $this->log(
            $userId,
            'MAINTENANCE',
            'Maintenance mode ' . ($enabled ? 'ON' : 'OFF')
        );

        return [
            'success' => true,
            'message' => 'Maintenance mode berhasil diperbarui.',
            'enabled' => $enabled,
        ];
    }

    public function uploadBranding(
        int $userId,
        string $assetType,
        ?UploadedFile $file
    ): array {
        $map = [
            'logo' => 'logo_sekolah',
            'icon' => 'icon_sekolah',
        ];

        if (!isset($map[$assetType])) {
            return $this->fail('VALIDATION', 'Jenis branding tidak valid.');
        }

        $validated = $this->validateImage($file);

        if (!$validated['success']) {
            return $validated;
        }

        $gd = $this->validateGdSupport();

        if (!$gd['success']) {
            return $gd;
        }

        $dir = FCPATH . 'uploads/settings/branding';

        if (!$this->ensureDirectory($dir)) {
            $this->logTechnicalError(
                'branding',
                'Folder branding tidak dapat dibuat/ditulis.',
                [
                    'target_dir' => $dir,
                    'asset_type' => $assetType,
                ]
            );

            return $this->fail('UPLOAD_FAILED', 'Folder branding tidak dapat digunakan.');
        }

        $filename = $assetType . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.png';
        $path = $dir . DIRECTORY_SEPARATOR . $filename;

        try {
            $binary = $this->readUploadedImage($file);

            if ($binary === null) {
                throw new \RuntimeException('File temporary upload gagal dibaca.');
            }

            $image = @imagecreatefromstring($binary);

            if ($image === false) {
                throw new \RuntimeException('GD gagal mendekode isi gambar.');
            }

            imagealphablending($image, false);
            imagesavealpha($image, true);

            $written = @imagepng($image, $path, 9);
            imagedestroy($image);

            if ($written !== true || !is_file($path)) {
                throw new \RuntimeException('GD gagal menulis file PNG hasil re-encode.');
            }

            if (!is_readable($path) || filesize($path) <= 0) {
                @unlink($path);
                throw new \RuntimeException('File PNG hasil re-encode kosong/tidak dapat dibaca.');
            }
        } catch (Throwable $e) {
            if (isset($image) && is_object($image)) {
                @imagedestroy($image);
            }

            @unlink($path);

            $this->logTechnicalError(
                'branding',
                $e->getMessage(),
                [
                    'asset_type' => $assetType,
                    'target_path' => $path,
                    'upload_name' => $file?->getClientName(),
                    'upload_size' => $file?->getSize(),
                    'upload_mime' => $file?->getMimeType(),
                    'temp_exists' => $file ? is_file($file->getTempName()) : false,
                    'gd_loaded' => extension_loaded('gd'),
                    'imagecreatefromstring' => function_exists('imagecreatefromstring'),
                    'imagepng' => function_exists('imagepng'),
                ],
                $e
            );

            return $this->fail(
                'UPLOAD_FAILED',
                'Branding gagal diproses. Detail teknis telah dicatat pada log aplikasi.'
            );
        }

        $relative = 'uploads/settings/branding/' . $filename;

        try {
            $this->model->set($map[$assetType], $relative, 'string', $userId);
        } catch (Throwable $e) {
            @unlink($path);

            $this->logTechnicalError(
                'branding-setting',
                $e->getMessage(),
                [
                    'setting_key' => $map[$assetType],
                    'relative_path' => $relative,
                ],
                $e
            );

            return $this->fail(
                'SETTING_FAILED',
                'File berhasil diproses tetapi setting branding gagal disimpan.'
            );
        }

        $this->clearCache();
        $this->log($userId, 'UPLOAD', "Upload {$assetType} sekolah.");

        return [
            'success' => true,
            'message' => ucfirst($assetType) . ' berhasil di-upload.',
            'path' => $relative,
        ];
    }

    public function uploadBackgroundKta(
        int $userId,
        string $side,
        ?UploadedFile $file
    ): array {
        if (!in_array($side, ['depan', 'belakang'], true)) {
            return $this->fail('VALIDATION', 'Sisi kartu tidak valid.');
        }

        $validated = $this->validateImage($file);

        if (!$validated['success']) {
            return $validated;
        }

        $gd = $this->validateGdSupport();

        if (!$gd['success']) {
            return $gd;
        }

        $dir = FCPATH . 'uploads/settings/kartu';

        if (!$this->ensureDirectory($dir)) {
            $this->logTechnicalError(
                'kta',
                'Folder template kartu tidak dapat dibuat/ditulis.',
                [
                    'target_dir' => $dir,
                    'side' => $side,
                ]
            );

            return $this->fail('UPLOAD_FAILED', 'Folder template kartu tidak dapat digunakan.');
        }

        $filename = 'background_kta_' . $side . '_' . date('Ymd_His')
            . '_' . bin2hex(random_bytes(4)) . '.jpg';
        $path = $dir . DIRECTORY_SEPARATOR . $filename;

        try {
            $binary = $this->readUploadedImage($file);

            if ($binary === null) {
                throw new \RuntimeException('File temporary upload gagal dibaca.');
            }

            $source = @imagecreatefromstring($binary);

            if ($source === false) {
                throw new \RuntimeException('GD gagal mendekode isi gambar KTA.');
            }

            $target = imagecreatetruecolor(1011, 638);

            if ($target === false) {
                imagedestroy($source);
                throw new \RuntimeException('GD gagal membuat canvas KTA 1011x638.');
            }

            $white = imagecolorallocate($target, 255, 255, 255);
            imagefilledrectangle($target, 0, 0, 1011, 638, $white);

            $resampled = imagecopyresampled(
                $target,
                $source,
                0,
                0,
                0,
                0,
                1011,
                638,
                imagesx($source),
                imagesy($source)
            );

            if ($resampled !== true) {
                imagedestroy($source);
                imagedestroy($target);
                throw new \RuntimeException('GD gagal resize template KTA.');
            }

            $written = @imagejpeg($target, $path, 92);

            imagedestroy($source);
            imagedestroy($target);

            if ($written !== true || !is_file($path)) {
                throw new \RuntimeException('GD gagal menulis JPEG template KTA.');
            }

            $size = @getimagesize($path);

            if (!$size || (int) ($size[0] ?? 0) !== 1011 || (int) ($size[1] ?? 0) !== 638) {
                @unlink($path);
                throw new \RuntimeException('Hasil normalisasi KTA tidak berukuran 1011x638.');
            }
        } catch (Throwable $e) {
            if (isset($source) && is_object($source)) {
                @imagedestroy($source);
            }

            if (isset($target) && is_object($target)) {
                @imagedestroy($target);
            }

            @unlink($path);

            $this->logTechnicalError(
                'kta',
                $e->getMessage(),
                [
                    'side' => $side,
                    'target_path' => $path,
                    'upload_name' => $file?->getClientName(),
                    'upload_size' => $file?->getSize(),
                    'upload_mime' => $file?->getMimeType(),
                    'temp_exists' => $file ? is_file($file->getTempName()) : false,
                    'gd_loaded' => extension_loaded('gd'),
                ],
                $e
            );

            return $this->fail(
                'UPLOAD_FAILED',
                'Template kartu gagal diproses. Detail teknis telah dicatat pada log aplikasi.'
            );
        }

        $relative = 'uploads/settings/kartu/' . $filename;
        $key = $side === 'depan' ? 'background_kta_depan' : 'background_kta_belakang';

        try {
            $this->model->set($key, $relative, 'string', $userId);
        } catch (Throwable $e) {
            @unlink($path);

            $this->logTechnicalError(
                'kta-setting',
                $e->getMessage(),
                [
                    'setting_key' => $key,
                    'relative_path' => $relative,
                ],
                $e
            );

            return $this->fail(
                'SETTING_FAILED',
                'Template berhasil diproses tetapi setting KTA gagal disimpan.'
            );
        }

        $this->clearCache();
        $this->log($userId, 'UPLOAD', "Upload template KTA {$side} 1011x638.");

        return [
            'success' => true,
            'message' => 'Template kartu ' . $side . ' berhasil di-upload dan dinormalisasi ke 1011×638 px.',
            'path' => $relative,
        ];
    }

    private function validateImage(?UploadedFile $file): array
    {
        if (!$file) {
            return $this->fail('VALIDATION', 'File gambar wajib dipilih.');
        }

        if (!$file->isValid()) {
            $this->logTechnicalError(
                'upload-validation',
                'UploadedFile tidak valid.',
                [
                    'upload_error' => $file->getError(),
                    'upload_error_string' => $file->getErrorString(),
                    'upload_name' => $file->getClientName(),
                ]
            );

            return $this->fail(
                'VALIDATION',
                'Upload gambar tidak valid: ' . $file->getErrorString()
            );
        }

        if ($file->hasMoved()) {
            return $this->fail('VALIDATION', 'File upload sudah dipindahkan.');
        }

        if ($file->getSize() <= 0 || $file->getSize() > self::MAX_IMAGE_BYTES) {
            return $this->fail('VALIDATION', 'Ukuran gambar maksimal 5 MB.');
        }

        $temp = $file->getTempName();

        if ($temp === '' || !is_file($temp) || !is_readable($temp)) {
            $this->logTechnicalError(
                'upload-validation',
                'Temporary upload tidak tersedia/dapat dibaca.',
                [
                    'temp_path' => $temp,
                    'upload_name' => $file->getClientName(),
                ]
            );

            return $this->fail('INVALID_IMAGE', 'File temporary upload tidak dapat dibaca.');
        }

        $info = @getimagesize($temp);

        if (
            !$info
            || !in_array(
                $info['mime'] ?? '',
                ['image/jpeg', 'image/png', 'image/webp'],
                true
            )
        ) {
            return $this->fail(
                'INVALID_IMAGE',
                'Hanya JPEG, PNG, atau WEBP valid yang diperbolehkan.'
            );
        }

        if ((int) ($info[0] ?? 0) < 100 || (int) ($info[1] ?? 0) < 100) {
            return $this->fail('INVALID_IMAGE', 'Resolusi gambar minimal 100×100 px.');
        }

        return [
            'success' => true,
            'mime' => (string) $info['mime'],
            'width' => (int) $info[0],
            'height' => (int) $info[1],
        ];
    }

    private function validateGdSupport(): array
    {
        $required = [
            'imagecreatefromstring',
            'imagepng',
            'imagejpeg',
            'imagecreatetruecolor',
            'imagecopyresampled',
        ];

        $missing = [];

        foreach ($required as $function) {
            if (!function_exists($function)) {
                $missing[] = $function;
            }
        }

        if (!extension_loaded('gd') || $missing !== []) {
            $this->logTechnicalError(
                'gd',
                'GD extension/function tidak lengkap.',
                [
                    'gd_loaded' => extension_loaded('gd'),
                    'missing_functions' => $missing,
                    'php_sapi' => PHP_SAPI,
                    'php_version' => PHP_VERSION,
                    'php_ini' => php_ini_loaded_file(),
                ]
            );

            return $this->fail(
                'GD_NOT_AVAILABLE',
                'PHP GD pada web server belum aktif/lengkap. Periksa php.ini Apache/XAMPP.'
            );
        }

        return ['success' => true];
    }

    private function readUploadedImage(UploadedFile $file): ?string
    {
        $temp = $file->getTempName();

        if ($temp === '' || !is_file($temp) || !is_readable($temp)) {
            return null;
        }

        $binary = @file_get_contents($temp);

        if ($binary === false || $binary === '') {
            return null;
        }

        return $binary;
    }

    private function ensureDirectory(string $dir): bool
    {
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return false;
        }

        return is_writable($dir);
    }

    private function setMany(int $userId, array $pairs): void
    {
        foreach ($pairs as $key => [$value, $type]) {
            $this->model->set($key, $value, $type, $userId);
        }
    }

    private function boolValue(mixed $value): bool
    {
        return in_array(
            strtolower(trim((string) $value)),
            ['1', 'true', 'on', 'yes'],
            true
        );
    }

    private function clearCache(): void
    {
        try {
            cache()->clean();
        } catch (Throwable $e) {
            log_message(
                'warning',
                'Settings cache clear gagal: {message}',
                ['message' => $e->getMessage()]
            );
        }
    }

    private function log(int $userId, string $aksi, string $keterangan): void
    {
        db_connect()->table('log_activity')->insert([
            'id_user' => $userId,
            'aksi' => $aksi,
            'modul' => 'Settings Sistem',
            'keterangan' => $keterangan,
            'waktu' => Time::now(self::TZ)->format('Y-m-d H:i:s'),
        ]);
    }

    private function logTechnicalError(
        string $stage,
        string $message,
        array $context = [],
        ?Throwable $exception = null
    ): void {
        $safeContext = array_merge(
            [
                'stage' => $stage,
                'message' => $message,
            ],
            $context
        );

        if ($exception !== null) {
            $safeContext['exception_class'] = $exception::class;
            $safeContext['exception_file'] = $exception->getFile();
            $safeContext['exception_line'] = $exception->getLine();
        }

        log_message(
            'error',
            'Settings image processing failed: {payload}',
            [
                'payload' => json_encode(
                    $safeContext,
                    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                ),
            ]
        );
    }

    private function fail(string $code, string $message): array
    {
        return [
            'success' => false,
            'code' => $code,
            'message' => $message,
        ];
    }
}
