<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;

class KartuRenderService
{
    private const DEFAULT_FRONT =
        'public/assets/kartu/default/background_kta_depan.jpg';
    private const DEFAULT_BACK =
        'public/assets/kartu/default/background_kta_belakang.jpg';

    /**
     * Background kartu bersifat shared asset. Cache per instance mencegah
     * query setting + file_get_contents/base64 berulang dalam satu request.
     *
     * @var array<string, string|null>
     */
    private array $backgroundCache = [];

    public function viewData(
        array $card,
        bool $includeBackgrounds = true
    ): array {
        $payload = $this->buildQrPayload($card);

        // Generate SVG lebih besar dari display 120px agar hasil cetak tetap tajam.
        $qrCode = new QrCode(
            data: $payload,
            size: 360,
            margin: 8
        );
        $qrDataUri = (new SvgWriter())->write($qrCode)->getDataUri();

        $nama = mb_strtoupper(trim((string) ($card['nama'] ?? '')), 'UTF-8');
        $alamat = trim((string) ($card['alamat'] ?? ''));
        $kelas = $card['kelas'] ?? [];

        $data = [
            'card' => $card,
            'qr_payload' => $payload,
            'qr_data_uri' => $qrDataUri,
            'photo_data_uri' => $this->photoDataUri($card['foto'] ?? null),
            'verify_url' => base_url('kartu/verify/' . $card['kode_verifikasi']),
            'nama_display' => $nama,
            'name_font_size' => $this->nameFontSize($nama),
            'nisn_display' => (string) ($card['nisn'] ?? '-'),
            'kelas_display' => (string) ($kelas['nama_kelas'] ?? '-'),
            'jenis_kelamin_display' => $this->genderLabel(
                (string) ($card['jenis_kelamin'] ?? '')
            ),
            'tahun_ajaran_display' => (string) ($kelas['nama_tahun'] ?? '-'),
            'ttl_display' => $this->ttl(
                $card['tempat_lahir'] ?? null,
                $card['tanggal_lahir'] ?? null
            ),
            'alamat_display' => mb_strimwidth(
                $alamat !== '' ? $alamat : '-',
                0,
                110,
                '…',
                'UTF-8'
            ),
        ];

        if ($includeBackgrounds) {
            $data['background_front_data_uri'] =
                $this->backgroundDataUri('front');
            $data['background_back_data_uri'] =
                $this->backgroundDataUri('back');
        }

        return $data;
    }

    public function buildQrPayload(array $card): string
    {
        $nisn = preg_replace('/[^0-9]/', '', (string) ($card['nisn'] ?? ''));
        $nama = rawurlencode(
            mb_strtoupper(trim((string) ($card['nama'] ?? '')), 'UTF-8')
        );
        $verify = strtolower(trim((string) ($card['kode_verifikasi'] ?? '')));

        return 'SISFOUR|V1|NISN='
            . $nisn
            . '|NAMA='
            . $nama
            . '|VERIFY='
            . $verify;
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

    /**
     * Mengambil satu background shared sesuai sisi kartu.
     *
     * Pemanggil cetak massal menggunakan method ini agar data URI background
     * tidak disalin ke setiap item kartu.
     */
    public function backgroundDataUri(string $side): ?string
    {
        $side = strtolower(trim($side));

        return $side === 'back'
            ? $this->resolveBackgroundDataUri(
                'background_kta_belakang',
                self::DEFAULT_BACK
            )
            : $this->resolveBackgroundDataUri(
                'background_kta_depan',
                self::DEFAULT_FRONT
            );
    }

    private function resolveBackgroundDataUri(
        string $settingKey,
        string $fallbackRelative
    ): ?string {
        if (array_key_exists($settingKey, $this->backgroundCache)) {
            return $this->backgroundCache[$settingKey];
        }

        $row = db_connect()
            ->table('setting_sistem')
            ->select('setting_value')
            ->where('setting_key', $settingKey)
            ->get()
            ->getRowArray();

        $relative = trim((string) ($row['setting_value'] ?? ''));
        $path = $this->safePublicPath($relative);

        if ($path === null || !is_file($path)) {
            $path = $this->fallbackPath($fallbackRelative);
        }

        $this->backgroundCache[$settingKey] = $path !== null
            ? $this->fileDataUri($path)
            : null;

        return $this->backgroundCache[$settingKey];
    }

    private function photoDataUri(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $value = str_replace('\\', '/', trim($value));

        if (
            $value === ''
            || str_contains($value, '..')
            || str_contains($value, "\0")
        ) {
            return null;
        }

        $candidates = [
            ROOTPATH . 'uploads/foto_siswa/' . basename($value),
            FCPATH . ltrim($value, '/'),
            ROOTPATH . ltrim($value, '/'),
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $this->fileDataUri($path);
            }
        }

        return null;
    }

    private function safePublicPath(string $relative): ?string
    {
        if ($relative === '') {
            return null;
        }

        $relative = str_replace('\\', '/', $relative);

        if (
            str_contains($relative, '..')
            || str_contains($relative, "\0")
            || preg_match('#^[a-z]+://#i', $relative)
        ) {
            return null;
        }

        $relative = ltrim($relative, '/');
        $candidates = [];

        if (str_starts_with($relative, 'public/')) {
            $withoutPublic = substr($relative, 7);
            $candidates[] = ROOTPATH . $relative;
            $candidates[] = FCPATH . $withoutPublic;
        } else {
            $candidates[] = FCPATH . $relative;
            $candidates[] = ROOTPATH . $relative;
            $candidates[] = ROOTPATH . 'public/' . $relative;
        }

        foreach (array_unique($candidates) as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    private function fallbackPath(string $relative): ?string
    {
        $relative = ltrim(str_replace('\\', '/', $relative), '/');

        if (
            $relative === ''
            || str_contains($relative, '..')
            || str_contains($relative, "\0")
        ) {
            return null;
        }

        $candidates = [
            ROOTPATH . $relative,
        ];

        if (str_starts_with($relative, 'public/')) {
            $candidates[] = FCPATH . substr($relative, 7);
        } else {
            $candidates[] = FCPATH . $relative;
            $candidates[] = ROOTPATH . 'public/' . $relative;
        }

        foreach (array_unique($candidates) as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    private function fileDataUri(string $path): ?string
    {
        if (!is_file($path)) {
            return null;
        }

        $content = file_get_contents($path);

        if ($content === false) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'image/png';

        return 'data:'
            . $mime
            . ';base64,'
            . base64_encode($content);
    }

    private function nameFontSize(string $name): int
    {
        $length = mb_strlen($name, 'UTF-8');

        return $length <= 24
            ? 42
            : ($length <= 32 ? 38 : 34);
    }

    private function genderLabel(string $value): string
    {
        return match (strtoupper($value)) {
            'L' => 'Laki-laki',
            'P' => 'Perempuan',
            default => $value !== '' ? $value : '-',
        };
    }

    private function ttl(?string $place, ?string $date): string
    {
        $place = trim((string) $place);
        $date = trim((string) $date);

        if ($date !== '') {
            $ts = strtotime($date);

            if ($ts !== false) {
                $date = date('d-m-Y', $ts);
            }
        }

        if ($place !== '' && $date !== '') {
            return $place . ', ' . $date;
        }

        return $place !== ''
            ? $place
            : ($date !== '' ? $date : '-');
    }
}
