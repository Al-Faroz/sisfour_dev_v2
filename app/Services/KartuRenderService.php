<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;

class KartuRenderService
{
    public function viewData(array $card): array
    {
        $verifyUrl = base_url('kartu/verify/' . $card['kode_verifikasi']);
        $qrCode = new QrCode(
            data: $verifyUrl,
            size: 220,
            margin: 8
        );

        $qrDataUri = (new SvgWriter())->write($qrCode)->getDataUri();

        return [
            'card' => $card,
            'qr_data_uri' => $qrDataUri,
            'photo_data_uri' => $this->photoDataUri($card['foto'] ?? null),
            'verify_url' => $verifyUrl,
        ];
    }

    public function pdf(array $viewData): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $html = view('kartu/cetak', $viewData);
        $dompdf->loadHtml($html);

        // 1011 x 638 px pada 96 DPI -> 758.25 x 478.5 pt.
        $dompdf->setPaper([0, 0, 758.25, 478.5], 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    private function photoDataUri(?string $relativePath): ?string
    {
        if (!$relativePath) {
            return null;
        }

        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
        $path = FCPATH . $relativePath;

        if (!is_file($path)) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'image/jpeg';
        $content = file_get_contents($path);

        if ($content === false) {
            return null;
        }

        return 'data:' . $mime . ';base64,' . base64_encode($content);
    }
}
