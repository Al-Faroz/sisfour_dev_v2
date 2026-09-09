<?php

namespace App\Services;

use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * UploadService
 *
 * Foto portrait:
 * - PNG maksimal 2 MB;
 * - re-encode dan crop 3:4.
 *
 * Dokumen personalia:
 * - PDF/PNG/JPG maksimal 5 MB;
 * - disimpan di lokasi non-public yang ditentukan caller;
 * - image dire-encode untuk membuang metadata;
 * - PDF diperiksa signature %PDF-.
 */
class UploadService
{
    protected int $maxSizeBytes = 2 * 1024 * 1024;
    protected int $maxDocumentBytes = 5 * 1024 * 1024;

    /**
     * @return array{valid: bool, message?: string}
     */
    public function validate(UploadedFile $file): array
    {
        if (! $file->isValid()) {
            return ['valid' => false, 'message' => 'File upload tidak valid: ' . $file->getErrorString()];
        }

        if ($file->getSize() > $this->maxSizeBytes) {
            return ['valid' => false, 'message' => 'Ukuran file maksimal 2MB.'];
        }

        if ($file->getMimeType() !== 'image/png') {
            return ['valid' => false, 'message' => 'Format foto harus PNG.'];
        }

        return ['valid' => true];
    }

    public function processFotoPortrait(UploadedFile $file, string $destDir, string $prefix): string
    {
        $cek = $this->validate($file);
        if (! $cek['valid']) {
            throw new \RuntimeException($cek['message'] ?? 'File foto tidak valid.');
        }

        $tmpPath = $file->getTempName();
        $source = @imagecreatefrompng($tmpPath);
        if ($source === false) {
            throw new \RuntimeException('File PNG rusak atau tidak dapat dibaca.');
        }

        $srcW = imagesx($source);
        $srcH = imagesy($source);
        $targetRatio = 3 / 4;
        $srcRatio = $srcW / $srcH;

        if ($srcRatio > $targetRatio) {
            $cropH = $srcH;
            $cropW = (int) round($srcH * $targetRatio);
        } else {
            $cropW = $srcW;
            $cropH = (int) round($srcW / $targetRatio);
        }

        $srcX = (int) (($srcW - $cropW) / 2);
        $srcY = (int) (($srcH - $cropH) / 2);

        $cropped = imagecreatetruecolor($cropW, $cropH);
        imagealphablending($cropped, false);
        imagesavealpha($cropped, true);
        imagecopyresampled(
            $cropped,
            $source,
            0,
            0,
            $srcX,
            $srcY,
            $cropW,
            $cropH,
            $cropW,
            $cropH
        );

        $this->ensureDirectory($destDir);

        $safePrefix = $this->safePrefix($prefix);
        $filename = $safePrefix . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.png';
        $fullPath = rtrim($destDir, '/\\') . DIRECTORY_SEPARATOR . $filename;

        if (! imagepng($cropped, $fullPath)) {
            imagedestroy($source);
            imagedestroy($cropped);
            throw new \RuntimeException('Foto gagal disimpan.');
        }

        imagedestroy($source);
        imagedestroy($cropped);

        return $filename;
    }

    /**
     * Menyimpan dokumen ke folder non-public caller.
     *
     * @return array{filename:string,original_name:string,mime_type:string}
     */
    public function processPersonaliaDocument(
        UploadedFile $file,
        string $destDir,
        string $prefix
    ): array {
        if (! $file->isValid()) {
            throw new \RuntimeException('File dokumen tidak valid: ' . $file->getErrorString());
        }

        if ($file->getSize() <= 0 || $file->getSize() > $this->maxDocumentBytes) {
            throw new \RuntimeException('Ukuran dokumen maksimal 5 MB.');
        }

        $mime = strtolower(trim((string) $file->getMimeType()));
        $allowed = [
            'application/pdf' => 'pdf',
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
        ];

        if (! isset($allowed[$mime])) {
            throw new \RuntimeException('Dokumen harus berformat PDF, PNG, JPG, atau JPEG.');
        }

        $tmpPath = $file->getTempName();
        if (! is_file($tmpPath)) {
            throw new \RuntimeException('File sementara dokumen tidak ditemukan.');
        }

        $this->ensureDirectory($destDir);

        $safePrefix = $this->safePrefix($prefix);
        $extension = $allowed[$mime];
        $filename = $safePrefix . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
        $fullPath = rtrim($destDir, '/\\') . DIRECTORY_SEPARATOR . $filename;

        if ($mime === 'application/pdf') {
            $head = file_get_contents($tmpPath, false, null, 0, 5);
            if ($head !== '%PDF-') {
                throw new \RuntimeException('File PDF tidak valid.');
            }

            $content = file_get_contents($tmpPath);
            if ($content === false || file_put_contents($fullPath, $content, LOCK_EX) === false) {
                throw new \RuntimeException('Dokumen PDF gagal disimpan.');
            }
        } else {
            $content = file_get_contents($tmpPath);
            $image = $content !== false ? @imagecreatefromstring($content) : false;
            if ($image === false) {
                throw new \RuntimeException('File gambar dokumen rusak atau tidak dapat dibaca.');
            }

            $width = imagesx($image);
            $height = imagesy($image);
            if ($width <= 0 || $height <= 0 || ($width * $height) > 25_000_000) {
                imagedestroy($image);
                throw new \RuntimeException('Resolusi gambar dokumen terlalu besar.');
            }

            $saved = $mime === 'image/png'
                ? imagepng($image, $fullPath)
                : imagejpeg($image, $fullPath, 90);
            imagedestroy($image);

            if (! $saved) {
                throw new \RuntimeException('Gambar dokumen gagal disimpan.');
            }
        }

        return [
            'filename' => $filename,
            'original_name' => mb_substr(basename((string) $file->getClientName()), 0, 255),
            'mime_type' => $mime,
        ];
    }

    private function ensureDirectory(string $destDir): void
    {
        if (is_dir($destDir)) {
            return;
        }

        if (! mkdir($destDir, 0755, true) && ! is_dir($destDir)) {
            throw new \RuntimeException('Folder upload tidak dapat dibuat.');
        }
    }

    private function safePrefix(string $prefix): string
    {
        $safe = preg_replace('/[^A-Za-z0-9_-]+/', '_', trim($prefix)) ?? '';
        $safe = trim($safe, '_-');

        return $safe !== '' ? $safe : 'file';
    }
}
