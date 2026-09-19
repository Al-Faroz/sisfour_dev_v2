<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use Throwable;

class PtspReceiptPdfService
{
    private const PAPER_WIDTH_MM = 80.0;
    private const MIN_HEIGHT_MM = 110.0;
    private const MAX_HEIGHT_MM = 260.0;

    public function generate(array $receipt, array $settings = []): array
    {
        try {
            $school = $this->clean((string) ($settings['nama_sekolah'] ?? 'MTsN 4 Jombang'), 200);
            $submittedAt = $this->clean((string) ($receipt['submitted_at'] ?? ''), 40);
            $name = $this->clean((string) ($receipt['nama_lengkap'] ?? ''), 150);
            $category = $this->clean((string) ($receipt['kategori_pemohon'] ?? ''), 80);
            $service = $this->clean((string) ($receipt['jenis_layanan'] ?? ''), 150);
            $description = $this->clean((string) ($receipt['tujuan_keterangan'] ?? ''), 2000);

            if ($submittedAt === '' || $name === '' || $category === '' || $service === '' || $description === '') {
                return $this->fail('PDF_INVALID_RECEIPT', 'Data bukti layanan tidak lengkap.');
            }

            $options = new Options();
            $options->set('isRemoteEnabled', false);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('defaultFont', 'DejaVu Sans');

            $dompdf = new Dompdf($options);
            $dompdf->setPaper([
                0,
                0,
                $this->mmToPt(self::PAPER_WIDTH_MM),
                $this->mmToPt($this->estimateHeightMm($receipt)),
            ]);

            $dompdf->loadHtml($this->html([
                'school' => $school,
                'submitted_at' => $submittedAt,
                'name' => $name,
                'category' => $category,
                'service' => $service,
                'description' => $description,
            ]), 'UTF-8');
            $dompdf->render();

            $binary = $dompdf->output();
            if ($binary === '') {
                return $this->fail('PDF_EMPTY', 'PDF bukti layanan gagal dibentuk.');
            }

            return [
                'success' => true,
                'filename' => 'bukti-layanan-ptsp-' . date('Ymd-His') . '.pdf',
                'mime_type' => 'application/pdf',
                'base64' => base64_encode($binary),
                'paper_width_mm' => 80,
            ];
        } catch (Throwable $e) {
            log_message('error', 'PTSP receipt PDF failed: {message}', ['message' => $e->getMessage()]);

            return $this->fail(
                'PDF_FAILED',
                ENVIRONMENT === 'development'
                    ? $e->getMessage()
                    : 'PDF bukti layanan gagal dibentuk.'
            );
        }
    }

    private function html(array $data): string
    {
        $e = static fn (string $value): string => htmlspecialchars(
            $value,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );

        return '<!doctype html><html><head><meta charset="utf-8"><style>
            @page { margin: 4mm; }
            body { margin:0; padding:0; color:#000; font-family:"DejaVu Sans",sans-serif; font-size:9.5pt; line-height:1.35; }
            .center { text-align:center; }
            .title { font-weight:700; font-size:11pt; margin-bottom:1mm; }
            .school { font-weight:600; margin-bottom:2mm; }
            .rule { border-top:.3mm dashed #000; margin:2.5mm 0; }
            .row { margin-bottom:1.6mm; }
            .label { font-weight:700; display:block; }
            .value { word-wrap:break-word; white-space:normal; }
            .note { font-size:8pt; margin-top:2mm; }
        </style></head><body>
            <div class="center title">BUKTI PENGISIAN LAYANAN PTSP</div>
            <div class="center school">' . $e($data['school']) . '</div>
            <div class="rule"></div>
            <div class="row"><span class="label">Waktu</span><span class="value">' . $e($data['submitted_at']) . '</span></div>
            <div class="row"><span class="label">Nama</span><span class="value">' . $e($data['name']) . '</span></div>
            <div class="row"><span class="label">Kategori</span><span class="value">' . $e($data['category']) . '</span></div>
            <div class="row"><span class="label">Layanan</span><span class="value">' . $e($data['service']) . '</span></div>
            <div class="row"><span class="label">Keterangan</span><span class="value">' . $e($data['description']) . '</span></div>
            <div class="rule"></div>
            <div class="center">Pengajuan berhasil diterima.</div>
            <div class="center note">Bukti ini bukan nomor antrean atau kode tracking.</div>
        </body></html>';
    }

    private function estimateHeightMm(array $receipt): float
    {
        $text = implode(' ', [
            (string) ($receipt['nama_lengkap'] ?? ''),
            (string) ($receipt['kategori_pemohon'] ?? ''),
            (string) ($receipt['jenis_layanan'] ?? ''),
            (string) ($receipt['tujuan_keterangan'] ?? ''),
        ]);

        $length = function_exists('mb_strlen')
            ? mb_strlen($text, 'UTF-8')
            : strlen($text);

        $estimatedLines = max(1, (int) ceil($length / 34));
        $height = 88.0 + ($estimatedLines * 4.6);

        return max(self::MIN_HEIGHT_MM, min(self::MAX_HEIGHT_MM, $height));
    }

    private function mmToPt(float $mm): float
    {
        return $mm * 72 / 25.4;
    }

    private function clean(string $value, int $max): string
    {
        $value = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '');

        return function_exists('mb_substr')
            ? mb_substr($value, 0, $max, 'UTF-8')
            : substr($value, 0, $max);
    }

    private function fail(string $code, string $message): array
    {
        return ['success' => false, 'code' => $code, 'message' => $message];
    }
}
