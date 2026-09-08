<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;

class KartuRenderService
{
    private const DEFAULT_FRONT = 'assets/kartu/default/background_kta_depan.jpg';
    private const DEFAULT_BACK = 'assets/kartu/default/background_kta_belakang.png';

    public function viewData(array $card): array
    {
        $payload = $this->buildQrPayload($card);
        // Generate SVG lebih besar dari display 120px agar hasil cetak tetap tajam.
        $qrCode = new QrCode(data: $payload, size: 360, margin: 8);
        $qrDataUri = (new SvgWriter())->write($qrCode)->getDataUri();

        $nama = mb_strtoupper(trim((string) ($card['nama'] ?? '')), 'UTF-8');
        $alamat = trim((string) ($card['alamat'] ?? ''));
        $kelas = $card['kelas'] ?? [];

        return [
            'card' => $card,
            'qr_payload' => $payload,
            'qr_data_uri' => $qrDataUri,
            'photo_data_uri' => $this->photoDataUri($card['foto'] ?? null),
            'background_front_data_uri' => $this->backgroundDataUri('background_kta_depan', self::DEFAULT_FRONT),
            'background_back_data_uri' => $this->backgroundDataUri('background_kta_belakang', self::DEFAULT_BACK),
            'verify_url' => base_url('kartu/verify/' . $card['kode_verifikasi']),
            'nama_display' => $nama,
            'name_font_size' => $this->nameFontSize($nama),
            'nisn_display' => (string) ($card['nisn'] ?? '-'),
            'kelas_display' => (string) ($kelas['nama_kelas'] ?? '-'),
            'jenis_kelamin_display' => $this->genderLabel((string) ($card['jenis_kelamin'] ?? '')),
            'tahun_ajaran_display' => (string) ($kelas['nama_tahun'] ?? '-'),
            'ttl_display' => $this->ttl($card['tempat_lahir'] ?? null, $card['tanggal_lahir'] ?? null),
            'alamat_display' => mb_strimwidth($alamat !== '' ? $alamat : '-', 0, 110, '…', 'UTF-8'),
        ];
    }

    public function buildQrPayload(array $card): string
    {
        $nisn = preg_replace('/[^0-9]/', '', (string) ($card['nisn'] ?? ''));
        $nama = rawurlencode(mb_strtoupper(trim((string) ($card['nama'] ?? '')), 'UTF-8'));
        $verify = strtolower(trim((string) ($card['kode_verifikasi'] ?? '')));

        return 'SISFOUR|V1|NISN=' . $nisn . '|NAMA=' . $nama . '|VERIFY=' . $verify;
    }

    public function pdf(array $viewData): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('kartu/cetak', $viewData));
        // 1011 x 638 px @96 DPI = 758.25 x 478.5 pt.
        $dompdf->setPaper([0, 0, 758.25, 478.5], 'portrait');
        $dompdf->render();
        return $dompdf->output();
    }

    private function backgroundDataUri(string $settingKey, string $fallbackRelative): ?string
    {
        $row = db_connect()->table('setting_sistem')->select('setting_value')->where('setting_key', $settingKey)->get()->getRowArray();
        $relative = trim((string) ($row['setting_value'] ?? ''));
        $path = $this->safePublicPath($relative);
        if ($path === null || !is_file($path)) $path = FCPATH . ltrim($fallbackRelative, '/');
        return $this->fileDataUri($path);
    }

    private function photoDataUri(?string $value): ?string
    {
        if (!$value) return null;
        $value = str_replace('\\', '/', trim($value));
        if ($value === '' || str_contains($value, '..') || str_contains($value, "\0")) return null;

        $candidates = [
            ROOTPATH . 'uploads/foto_siswa/' . basename($value),
            FCPATH . ltrim($value, '/'),
            ROOTPATH . ltrim($value, '/'),
        ];
        foreach ($candidates as $path) {
            if (is_file($path)) return $this->fileDataUri($path);
        }
        return null;
    }

    private function safePublicPath(string $relative): ?string
    {
        if ($relative === '') return null;
        $relative = str_replace('\\', '/', $relative);
        if (str_contains($relative, '..') || str_contains($relative, "\0") || preg_match('#^[a-z]+://#i', $relative)) return null;
        $relative = preg_replace('#^public/#', '', ltrim($relative, '/'));
        return FCPATH . $relative;
    }

    private function fileDataUri(string $path): ?string
    {
        if (!is_file($path)) return null;
        $content = file_get_contents($path); if ($content === false) return null;
        $mime = mime_content_type($path) ?: 'image/png';
        return 'data:' . $mime . ';base64,' . base64_encode($content);
    }

    private function nameFontSize(string $name): int
    {
        $length = mb_strlen($name, 'UTF-8');
        return $length <= 24 ? 42 : ($length <= 32 ? 38 : 34);
    }

    private function genderLabel(string $value): string
    {
        return match (strtoupper($value)) {'L' => 'Laki-laki', 'P' => 'Perempuan', default => $value !== '' ? $value : '-'};
    }

    private function ttl(?string $place, ?string $date): string
    {
        $place = trim((string) $place);
        $date = trim((string) $date);
        if ($date !== '') {
            $ts = strtotime($date);
            if ($ts !== false) $date = date('d-m-Y', $ts);
        }
        if ($place !== '' && $date !== '') return $place . ', ' . $date;
        return $place !== '' ? $place : ($date !== '' ? $date : '-');
    }
}
