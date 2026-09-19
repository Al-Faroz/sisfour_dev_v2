<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use RuntimeException;

class KartuRenderService
{
    private const DEFAULT_FRONT =
        'public/assets/kartu/default/background_kta_depan.jpg';
    private const DEFAULT_BACK =
        'public/assets/kartu/default/background_kta_belakang.jpg';

    private const CARD_WIDTH = 1011;
    private const CARD_HEIGHT = 638;

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

    public function frontJpeg(
        array $card,
        int $quality = 92
    ): string {
        if (
            !function_exists('imagecreatetruecolor')
            || !function_exists('imagecreatefromstring')
            || !function_exists('imagejpeg')
            || !function_exists('imagettftext')
        ) {
            throw new RuntimeException(
                'GD + FreeType diperlukan untuk export JPG Kartu Pelajar.'
            );
        }

        $regularFont = $this->fontPath(false);
        $boldFont = $this->fontPath(true);

        if ($regularFont === null || $boldFont === null) {
            throw new RuntimeException(
                'Font renderer JPG Kartu Pelajar tidak tersedia.'
            );
        }

        $data = $this->viewData(
            $card,
            false
        );

        $background = $this->imageFromDataUri(
            $this->backgroundDataUri('front')
        );

        if ($background === null) {
            throw new RuntimeException(
                'Background depan Kartu Pelajar tidak tersedia.'
            );
        }

        $canvas = imagecreatetruecolor(
            self::CARD_WIDTH,
            self::CARD_HEIGHT
        );

        if ($canvas === false) {
            imagedestroy($background);

            throw new RuntimeException(
                'Canvas JPG Kartu Pelajar gagal dibuat.'
            );
        }

        try {
            imagecopyresampled(
                $canvas,
                $background,
                0,
                0,
                0,
                0,
                self::CARD_WIDTH,
                self::CARD_HEIGHT,
                imagesx($background),
                imagesy($background)
            );

            imagedestroy($background);

            $white = imagecolorallocate(
                $canvas,
                255,
                255,
                255
            );

            $muted = imagecolorallocate(
                $canvas,
                210,
                210,
                210
            );

            if (!empty($data['photo_data_uri'])) {
                $photo = $this->imageFromDataUri(
                    $data['photo_data_uri']
                );

                if ($photo !== null) {
                    for ($border = 0; $border < 3; $border++) {
                        imagerectangle(
                            $canvas,
                            760 + $border,
                            73 + $border,
                            975 - $border,
                            358 - $border,
                            $white
                        );
                    }

                    $this->copyCover(
                        $canvas,
                        $photo,
                        763,
                        76,
                        210,
                        280
                    );

                    imagedestroy($photo);
                }
            }

            imagefilledrectangle(
                $canvas,
                810,
                375,
                937,
                502,
                $white
            );

            $qrCode = new QrCode(
                data: (string) $data['qr_payload'],
                size: 360,
                margin: 8
            );

            $qrBinary = (new PngWriter())
                ->write($qrCode)
                ->getString();

            $qr = imagecreatefromstring(
                $qrBinary
            );

            if ($qr === false) {
                throw new RuntimeException(
                    'QR Kartu Pelajar gagal diraster.'
                );
            }

            imagecopyresampled(
                $canvas,
                $qr,
                814,
                379,
                0,
                0,
                120,
                120,
                imagesx($qr),
                imagesy($qr)
            );

            imagedestroy($qr);

            $this->drawCenteredText(
                $canvas,
                (string) ($card['nomor_kartu'] ?? ''),
                $regularFont,
                9,
                790,
                505,
                160,
                $white
            );

            $this->drawWrappedText(
                $canvas,
                (string) $data['nama_display'],
                $boldFont,
                (int) $data['name_font_size'],
                40,
                175,
                570,
                98,
                1.15,
                $white,
                2
            );

            $this->drawTextTop(
                $canvas,
                'NISN',
                $regularFont,
                11,
                40,
                340,
                $muted
            );
            $this->drawTextTop(
                $canvas,
                (string) $data['nisn_display'],
                $boldFont,
                19,
                40,
                354,
                $white
            );

            $this->drawTextTop(
                $canvas,
                'Kelas',
                $regularFont,
                11,
                270,
                340,
                $muted
            );
            $this->drawTextTop(
                $canvas,
                (string) $data['kelas_display'],
                $boldFont,
                19,
                270,
                354,
                $white
            );

            $this->drawTextTop(
                $canvas,
                'Jenis Kelamin',
                $regularFont,
                11,
                40,
                390,
                $muted
            );
            $this->drawTextTop(
                $canvas,
                (string) $data['jenis_kelamin_display'],
                $boldFont,
                19,
                40,
                404,
                $white
            );

            $this->drawTextTop(
                $canvas,
                'Tahun Ajaran',
                $regularFont,
                11,
                270,
                390,
                $muted
            );
            $this->drawTextTop(
                $canvas,
                (string) $data['tahun_ajaran_display'],
                $boldFont,
                19,
                270,
                404,
                $white
            );

            $this->drawTextTop(
                $canvas,
                (string) $data['ttl_display'],
                $regularFont,
                17,
                40,
                460,
                $white
            );

            $this->drawWrappedText(
                $canvas,
                (string) $data['alamat_display'],
                $regularFont,
                17,
                40,
                490,
                600,
                44,
                1.12,
                $white,
                2
            );

            $quality = max(
                70,
                min(95, $quality)
            );

            ob_start();

            $written = imagejpeg(
                $canvas,
                null,
                $quality
            );

            $binary = ob_get_clean();

            if (
                !$written
                || !is_string($binary)
                || $binary === ''
            ) {
                throw new RuntimeException(
                    'JPG Kartu Pelajar gagal dibentuk.'
                );
            }

            return $binary;
        } finally {
            if (isset($background) && is_object($background)) {
                @imagedestroy($background);
            }

            imagedestroy($canvas);
        }
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

    private function imageFromDataUri(
        ?string $dataUri
    ) {
        if (
            $dataUri === null
            || !preg_match(
                '#^data:image/[a-z0-9.+-]+;base64,#i',
                $dataUri
            )
        ) {
            return null;
        }

        $comma = strpos(
            $dataUri,
            ','
        );

        if ($comma === false) {
            return null;
        }

        $binary = base64_decode(
            substr($dataUri, $comma + 1),
            true
        );

        if ($binary === false || $binary === '') {
            return null;
        }

        $image = @imagecreatefromstring(
            $binary
        );

        return $image === false
            ? null
            : $image;
    }

    private function copyCover(
        $destination,
        $source,
        int $x,
        int $y,
        int $width,
        int $height
    ): void {
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);

        if ($sourceWidth <= 0 || $sourceHeight <= 0) {
            return;
        }

        $sourceRatio =
            $sourceWidth / $sourceHeight;

        $targetRatio =
            $width / $height;

        if ($sourceRatio > $targetRatio) {
            $cropHeight = $sourceHeight;
            $cropWidth = (int) round(
                $sourceHeight * $targetRatio
            );
            $sourceX = (int) floor(
                ($sourceWidth - $cropWidth) / 2
            );
            $sourceY = 0;
        } else {
            $cropWidth = $sourceWidth;
            $cropHeight = (int) round(
                $sourceWidth / $targetRatio
            );
            $sourceX = 0;
            $sourceY = (int) floor(
                ($sourceHeight - $cropHeight) / 2
            );
        }

        imagecopyresampled(
            $destination,
            $source,
            $x,
            $y,
            $sourceX,
            $sourceY,
            $width,
            $height,
            $cropWidth,
            $cropHeight
        );
    }

    private function fontPath(bool $bold): ?string
    {
        $filename = $bold
            ? 'DejaVuSans-Bold.ttf'
            : 'DejaVuSans.ttf';

        $candidates = [
            ROOTPATH
                . 'vendor/dompdf/dompdf/lib/fonts/'
                . $filename,
            '/usr/share/fonts/truetype/dejavu/'
                . $filename,
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    private function drawTextTop(
        $image,
        string $text,
        string $font,
        float $fontPx,
        int $x,
        int $top,
        int $color
    ): void {
        if ($text === '') {
            return;
        }

        $fontPt = $this->pxToPoint(
            $fontPx
        );

        $bbox = imagettfbbox(
            $fontPt,
            0,
            $font,
            $text
        );

        if ($bbox === false) {
            return;
        }

        $baseline = (int) round(
            $top - $bbox[7]
        );

        imagettftext(
            $image,
            $fontPt,
            0,
            $x,
            $baseline,
            $color,
            $font,
            $text
        );
    }

    private function drawCenteredText(
        $image,
        string $text,
        string $font,
        float $fontPx,
        int $x,
        int $top,
        int $width,
        int $color
    ): void {
        $textWidth = $this->textWidth(
            $text,
            $font,
            $fontPx
        );

        $left = $x + max(
            0,
            (int) floor(
                ($width - $textWidth) / 2
            )
        );

        $this->drawTextTop(
            $image,
            $text,
            $font,
            $fontPx,
            $left,
            $top,
            $color
        );
    }

    private function drawWrappedText(
        $image,
        string $text,
        string $font,
        float $fontPx,
        int $x,
        int $top,
        int $width,
        int $height,
        float $lineHeight,
        int $color,
        int $maxLines
    ): void {
        $lines = $this->wrapText(
            $text,
            $font,
            $fontPx,
            $width
        );

        $lineStep = max(
            1,
            (int) round(
                $fontPx * $lineHeight
            )
        );

        $allowedLines = min(
            $maxLines,
            max(
                1,
                (int) floor(
                    $height / $lineStep
                )
            )
        );

        foreach (
            array_slice(
                $lines,
                0,
                $allowedLines
            )
            as $index => $line
        ) {
            $this->drawTextTop(
                $image,
                $line,
                $font,
                $fontPx,
                $x,
                $top + ($index * $lineStep),
                $color
            );
        }
    }

    private function wrapText(
        string $text,
        string $font,
        float $fontPx,
        int $maxWidth
    ): array {
        $words = preg_split(
            '/\\s+/u',
            trim($text)
        ) ?: [];

        if ($words === []) {
            return [''];
        }

        $lines = [];
        $line = '';

        foreach ($words as $word) {
            $candidate = $line === ''
                ? $word
                : $line . ' ' . $word;

            if (
                $line !== ''
                && $this->textWidth(
                    $candidate,
                    $font,
                    $fontPx
                ) > $maxWidth
            ) {
                $lines[] = $line;
                $line = $word;
                continue;
            }

            $line = $candidate;
        }

        if ($line !== '') {
            $lines[] = $line;
        }

        return $lines;
    }

    private function textWidth(
        string $text,
        string $font,
        float $fontPx
    ): int {
        $bbox = imagettfbbox(
            $this->pxToPoint($fontPx),
            0,
            $font,
            $text
        );

        if ($bbox === false) {
            return 0;
        }

        return (int) abs(
            $bbox[2] - $bbox[0]
        );
    }

    private function pxToPoint(
        float $px
    ): float {
        return $px * 0.75;
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
