<?php

namespace App\Services;

use App\Models\SettingSistemModel;
use CodeIgniter\Files\File;
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

        return ['success' => true, 'message' => 'Setting Sistem berhasil diperbarui.'];
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

        $dir = FCPATH . 'uploads/settings/branding';

        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            return $this->fail('UPLOAD_FAILED', 'Folder branding tidak dapat dibuat.');
        }

        $filename = $assetType . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.png';
        $path = $dir . DIRECTORY_SEPARATOR . $filename;

        try {
            $image = imagecreatefromstring(file_get_contents($file->getTempName()));

            if ($image === false) {
                return $this->fail('INVALID_IMAGE', 'File gambar tidak valid.');
            }

            imagealphablending($image, false);
            imagesavealpha($image, true);
            imagepng($image, $path, 9);
            imagedestroy($image);
        } catch (Throwable $e) {
            return $this->fail('UPLOAD_FAILED', 'Branding gagal diproses.');
        }

        $relative = 'uploads/settings/branding/' . $filename;
        $this->model->set($map[$assetType], $relative, 'string', $userId);
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

        $dir = FCPATH . 'uploads/settings/kartu';

        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            return $this->fail('UPLOAD_FAILED', 'Folder template kartu tidak dapat dibuat.');
        }

        $filename = 'background_kta_' . $side . '_' . date('Ymd_His')
            . '_' . bin2hex(random_bytes(4)) . '.jpg';
        $path = $dir . DIRECTORY_SEPARATOR . $filename;

        try {
            $source = imagecreatefromstring(file_get_contents($file->getTempName()));

            if ($source === false) {
                return $this->fail('INVALID_IMAGE', 'File gambar tidak valid.');
            }

            $target = imagecreatetruecolor(1011, 638);
            $white = imagecolorallocate($target, 255, 255, 255);
            imagefilledrectangle($target, 0, 0, 1011, 638, $white);

            imagecopyresampled(
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

            imagejpeg($target, $path, 92);
            imagedestroy($source);
            imagedestroy($target);
        } catch (Throwable $e) {
            return $this->fail('UPLOAD_FAILED', 'Template kartu gagal diproses.');
        }

        $relative = 'uploads/settings/kartu/' . $filename;
        $key = $side === 'depan'
            ? 'background_kta_depan'
            : 'background_kta_belakang';

        $this->model->set($key, $relative, 'string', $userId);
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
        if (!$file || !$file->isValid() || $file->hasMoved()) {
            return $this->fail('VALIDATION', 'File gambar wajib dipilih.');
        }

        if ($file->getSize() <= 0 || $file->getSize() > self::MAX_IMAGE_BYTES) {
            return $this->fail('VALIDATION', 'Ukuran gambar maksimal 5 MB.');
        }

        $temp = $file->getTempName();
        $info = @getimagesize($temp);

        if (!$info || !in_array($info['mime'] ?? '', ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return $this->fail('INVALID_IMAGE', 'Hanya JPEG, PNG, atau WEBP valid yang diperbolehkan.');
        }

        if (($info[0] ?? 0) < 100 || ($info[1] ?? 0) < 100) {
            return $this->fail('INVALID_IMAGE', 'Resolusi gambar terlalu kecil.');
        }

        return ['success' => true];
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
            // Cache bukan source of truth; kegagalan clear tidak membatalkan setting.
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

    private function fail(string $code, string $message): array
    {
        return ['success' => false, 'code' => $code, 'message' => $message];
    }
}
