<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use Throwable;

class StatistikPdfService
{
    public function generate(array $report): array
    {
        try {
            $data = $report['data'] ?? [];
            $charts = $this->charts($data);

            $options = new Options();
            $options->set('isRemoteEnabled', false);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('defaultFont', 'DejaVu Sans');

            $dompdf = new Dompdf($options);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->loadHtml(
                view('statistik/pdf', [
                    'report' => $report,
                    'charts' => $charts,
                ]),
                'UTF-8'
            );
            $dompdf->render();
            $binary = $dompdf->output();

            if ($binary === '') {
                return $this->fail('PDF_EMPTY', 'PDF Statistik kosong.');
            }

            return [
                'success' => true,
                'filename' => 'statistik-' . date('Ymd-His') . '.pdf',
                'binary' => $binary,
            ];
        } catch (Throwable $e) {
            log_message(
                'error',
                'Statistik PDF failed: {message}',
                ['message' => $e->getMessage()]
            );

            return $this->fail(
                'PDF_FAILED',
                ENVIRONMENT === 'development'
                    ? $e->getMessage()
                    : 'PDF Statistik gagal dibentuk.'
            );
        }
    }

    private function charts(array $data): array
    {
        $attendance = $data['attendance']['summary'] ?? [];
        $attendanceClass = $data['attendance']['by_class'] ?? [];
        $ews = $data['ews'] ?? [];
        $discipline = $data['discipline']['categories'] ?? [];
        $achievement = $data['achievement']['levels'] ?? [];
        $uks = $data['uks']['hasil'] ?? [];
        $ptspService = $data['ptsp']['layanan_status'] ?? [];
        $polling = $data['ptsp']['polling']['scores'] ?? [];

        return [
            'attendance' => $this->barChart(
                ['Hadir', 'Sakit', 'Izin', 'Alpha'],
                [
                    (float) ($attendance['Hadir'] ?? 0),
                    (float) ($attendance['Sakit'] ?? 0),
                    (float) ($attendance['Izin'] ?? 0),
                    (float) ($attendance['Alpha'] ?? 0),
                ],
                'Presensi Siswa'
            ),
            'attendance_class' => $this->barChart(
                array_column($attendanceClass, 'label'),
                array_map(
                    static fn (array $row): float =>
                        (float) ($row['persen_hadir'] ?? 0),
                    $attendanceClass
                ),
                'Persentase Hadir per Kelas',
                12,
                '%'
            ),
            'ews_alpha' => $this->barChart(
                array_column($ews['top_alpha'] ?? [], 'nama_siswa'),
                array_column($ews['top_alpha'] ?? [], 'total'),
                'Top Alpha 14 Hari',
                10
            ),
            'discipline' => $this->barChart(
                array_column($discipline, 'label'),
                array_column($discipline, 'total'),
                'Kategori Pelanggaran',
                10
            ),
            'achievement' => $this->barChart(
                array_column($achievement, 'label'),
                array_column($achievement, 'total'),
                'Tingkat Prestasi',
                10
            ),
            'uks' => $this->barChart(
                array_column($uks, 'label'),
                array_column($uks, 'total'),
                'Hasil Kunjungan UKS',
                10
            ),
            'ptsp_service' => $this->barChart(
                array_column($ptspService, 'label'),
                array_column($ptspService, 'total'),
                'Status Layanan PTSP',
                10
            ),
            'polling' => $this->barChart(
                array_map(
                    static fn ($value): string => 'Skor ' . (string) $value,
                    array_column($polling, 'label')
                ),
                array_column($polling, 'total'),
                'Distribusi Kepuasan PTSP',
                5
            ),
        ];
    }

    private function barChart(
        array $labels,
        array $values,
        string $title,
        int $limit = 10,
        string $suffix = ''
    ): ?string {
        $labels = array_slice(array_values($labels), 0, $limit);
        $values = array_slice(array_values($values), 0, $limit);

        if ($labels === [] || $values === []) {
            return null;
        }

        $count = min(count($labels), count($values));
        $labels = array_slice($labels, 0, $count);
        $values = array_map('floatval', array_slice($values, 0, $count));
        $max = max(1.0, ...$values);
        $width = 720;
        $left = 180;
        $right = 54;
        $rowHeight = 31;
        $height = 48 + ($count * $rowHeight);
        $barWidth = $width - $left - $right;
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width
            . '" height="' . $height . '" viewBox="0 0 ' . $width . ' ' . $height . '">';
        $svg .= '<rect width="100%" height="100%" fill="#ffffff"/>';
        $svg .= '<text x="12" y="22" font-family="DejaVu Sans" font-size="14" font-weight="700" fill="#263238">'
            . $this->xml($title) . '</text>';

        foreach ($values as $i => $value) {
            $y = 38 + ($i * $rowHeight);
            $label = $this->short((string) ($labels[$i] ?? '-'), 27);
            $w = max(1, ($value / $max) * $barWidth);
            $svg .= '<text x="12" y="' . ($y + 16)
                . '" font-family="DejaVu Sans" font-size="10" fill="#37474f">'
                . $this->xml($label) . '</text>';
            $svg .= '<rect x="' . $left . '" y="' . $y
                . '" width="' . $barWidth . '" height="19" rx="3" fill="#eceff1"/>';
            $svg .= '<rect x="' . $left . '" y="' . $y
                . '" width="' . round($w, 2) . '" height="19" rx="3" fill="#2e7d32"/>';
            $svg .= '<text x="' . ($width - 8) . '" y="' . ($y + 15)
                . '" text-anchor="end" font-family="DejaVu Sans" font-size="10" font-weight="700" fill="#263238">'
                . $this->xml($this->number($value) . $suffix) . '</text>';
        }

        $svg .= '</svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    private function short(string $value, int $max): string
    {
        $value = trim($value);
        if (mb_strlen($value, 'UTF-8') <= $max) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, $max - 1, 'UTF-8')) . '…';
    }

    private function number(float $value): string
    {
        return floor($value) === $value
            ? number_format($value, 0, ',', '.')
            : number_format($value, 1, ',', '.');
    }

    private function xml(string $value): string
    {
        return htmlspecialchars(
            $value,
            ENT_QUOTES | ENT_XML1 | ENT_SUBSTITUTE,
            'UTF-8'
        );
    }

    private function fail(string $code, string $message): array
    {
        return ['success' => false, 'code' => $code, 'message' => $message];
    }
}
