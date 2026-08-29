<?php

namespace App\Services;

use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * UploadService
 *
 * Re-encode PNG (hilangkan metadata berbahaya) + crop rasio 3:4, dipakai
 * bersama oleh Foto Guru, Foto Siswa, dan Kartu Pelajar (B7).
 *
 * Referensi: 04_MASTER_DATA §3.1, §3.2, §8; 09_PROFILE §2.3; 07_BK_PRESTASI_KARTU §4.2
 */
class UploadService
{
    protected int $maxSizeBytes = 2 * 1024 * 1024; // 2MB

    /**
     * Validasi file upload: harus PNG (mime + isvalid), max 2MB.
     *
     * @return array{valid: bool, message?: string}
     */
    public function validate(UploadedFile $file): array
    {
        if (!$file->isValid()) {
            return ['valid' => false, 'message' => 'File upload tidak valid: ' . $file->getErrorString()];
        }

        if ($file->getSize() > $this->maxSizeBytes) {
            return ['valid' => false, 'message' => 'Ukuran file maksimal 2MB.'];
        }

        $mime = $file->getMimeType();
        if ($mime !== 'image/png') {
            return ['valid' => false, 'message' => 'Format foto harus PNG.'];
        }

        return ['valid' => true];
    }

    /**
     * Re-encode + crop rasio 3:4 (dipakai untuk foto siswa/guru & kartu pelajar),
     * simpan ke $destDir dengan nama random, return nama file (bukan path penuh).
     *
     * @throws \RuntimeException jika file tidak dapat diproses (GD gagal)
     */
    public function processFotoPortrait(UploadedFile $file, string $destDir, string $prefix): string
    {
        $cek = $this->validate($file);
        if (!$cek['valid']) {
            throw new \RuntimeException($cek['message']);
        }

        $tmpPath = $file->getTempName();
        $source  = @imagecreatefrompng($tmpPath);
        if ($source === false) {
            throw new \RuntimeException('File PNG rusak atau tidak dapat dibaca.');
        }

        $srcW = imagesx($source);
        $srcH = imagesy($source);

        // Crop tengah ke rasio 3:4 (lebar:tinggi)
        $targetRatio = 3 / 4;
        $srcRatio    = $srcW / $srcH;

        if ($srcRatio > $targetRatio) {
            // Sumber terlalu lebar -> crop kiri-kanan
            $cropH = $srcH;
            $cropW = (int) round($srcH * $targetRatio);
        } else {
            // Sumber terlalu tinggi -> crop atas-bawah
            $cropW = $srcW;
            $cropH = (int) round($srcW / $targetRatio);
        }

        $srcX = (int) (($srcW - $cropW) / 2);
        $srcY = (int) (($srcH - $cropH) / 2);

        $cropped = imagecreatetruecolor($cropW, $cropH);
        imagealphablending($cropped, false);
        imagesavealpha($cropped, true);
        imagecopyresampled($cropped, $source, 0, 0, $srcX, $srcY, $cropW, $cropH, $cropW, $cropH);

        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        $filename = $prefix . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.png';
        $fullPath = rtrim($destDir, '/') . '/' . $filename;

        // Re-encode murni PNG -> menghilangkan metadata berbahaya (B7)
        imagepng($cropped, $fullPath);

        imagedestroy($source);
        imagedestroy($cropped);

        return $filename;
    }
}
