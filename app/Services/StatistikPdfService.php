<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use Throwable;

class StatistikPdfService
{
    private const CLIENT_CHART_KEYS = [
        'chartLevel',
        'chartGender',
        'chartAttendanceSummary',
        'chartAttendanceTrend',
        'chartAttendanceClass',
        'chartEwsSakit',
        'chartEwsIzin',
        'chartEwsAlpha',
        'chartTeachingStatus',
        'chartTeachingTrend',
        'chartDiscipline',
        'chartDisciplineTrend',
        'chartAchievement',
        'chartAchievementTrend',
        'chartCounselingStatus',
        'chartCounselingFields',
        'chartCounselingTrend',
        'chartUks',
        'chartPtspLayanan',
        'chartPtspPengaduan',
        'chartPtspKlasifikasi',
        'chartPtspPolling',
        'chartMobility',
    ];

    private const MAX_IMAGE_BYTES = 2500000;
    private const MAX_TOTAL_IMAGE_BYTES = 24000000;

    public function generate(array $report, array $clientImages = []): array
    {
        try {
            $data = $report['data'] ?? [];
            $charts = $this->charts($data);

            foreach ($this->sanitizeClientImages($clientImages) as $key => $uri) {
                $charts[$key] = $uri;
            }

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

    private function sanitizeClientImages(array $images): array
    {
        $allowed = array_fill_keys(self::CLIENT_CHART_KEYS, true);
        $result = [];
        $totalBytes = 0;

        foreach ($images as $key => $uri) {
            $key = (string) $key;
            if (! isset($allowed[$key]) || ! is_string($uri)) {
                continue;
            }

            $uri = trim($uri);
            if (! preg_match('#^data:image/png;base64,([A-Za-z0-9+/=\r\n]+)$#', $uri, $match)) {
                continue;
            }

            $binary = base64_decode(
                preg_replace('/\s+/', '', $match[1]) ?? '',
                true
            );
            if ($binary === false || $binary === '') {
                continue;
            }

            $bytes = strlen($binary);
            if (
                $bytes > self::MAX_IMAGE_BYTES
                || ($totalBytes + $bytes) > self::MAX_TOTAL_IMAGE_BYTES
            ) {
                continue;
            }

            if (substr($binary, 0, 8) !== "\x89PNG\x0D\x0A\x1A\x0A") {
                continue;
            }

            $totalBytes += $bytes;
            $result[$key] = 'data:image/png;base64,' . base64_encode($binary);
        }

        return $result;
    }

    /**
     * Fallback server-side charts when browser image export is unavailable.
     * The authoritative numbers still come from StatistikService.
     */
    private function charts(array $data): array
    {
        $composition = $data['composition'] ?? [];
        $attendance = $data['attendance']['summary'] ?? [];
        $attendanceClass = $data['attendance']['by_class'] ?? [];
        $ews = $data['ews'] ?? [];
        $teaching = $data['teaching'] ?? [];
        $discipline = $data['discipline'] ?? [];
        $achievement = $data['achievement'] ?? [];
        $counseling = $data['counseling'] ?? [];
        $uks = $data['uks'] ?? [];
        $ptsp = $data['ptsp'] ?? [];
        $mobility = $data['mobility'] ?? [];

        return [
            'chartLevel' => $this->rowsChart(
                $composition['by_level'] ?? [],
                'Siswa per Tingkat'
            ),
            'chartGender' => $this->rowsChart(
                $composition['by_gender'] ?? [],
                'Jenis Kelamin'
            ),
            'chartAttendanceSummary' => $this->barChart(
                ['Hadir', 'Sakit', 'Izin', 'Alpha'],
                [
                    (float) ($attendance['Hadir'] ?? 0),
                    (float) ($attendance['Sakit'] ?? 0),
                    (float) ($attendance['Izin'] ?? 0),
                    (float) ($attendance['Alpha'] ?? 0),
                ],
                'Presensi Siswa'
            ),
            'chartAttendanceClass' => $this->barChart(
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
            'chartEwsSakit' => $this->rankingChart($ews['top_sakit'] ?? [], 'Top Sakit 14 Hari'),
            'chartEwsIzin' => $this->rankingChart($ews['top_izin'] ?? [], 'Top Izin 14 Hari'),
            'chartEwsAlpha' => $this->rankingChart($ews['top_alpha'] ?? [], 'Top Alpha 14 Hari'),
            'chartTeachingStatus' => $this->rowsChart(
                $teaching['status_distribution'] ?? [],
                'Status Presensi Mengajar'
            ),
            'chartDiscipline' => $this->rowsChart(
                $discipline['categories'] ?? [],
                'Kategori Pelanggaran'
            ),
            'chartAchievement' => $this->rowsChart(
                $achievement['levels'] ?? [],
                'Tingkat Prestasi'
            ),
            'chartCounselingStatus' => $this->rowsChart(
                $counseling['status'] ?? [],
                'Status Konseling'
            ),
            'chartCounselingFields' => $this->rowsChart(
                $counseling['fields'] ?? [],
                'Bidang Konseling'
            ),
            'chartUks' => $this->rowsChart(
                $uks['hasil'] ?? [],
                'Hasil Kunjungan UKS'
            ),
            'chartPtspLayanan' => $this->rowsChart(
                $ptsp['layanan_status'] ?? [],
                'Status Layanan PTSP'
            ),
            'chartPtspPengaduan' => $this->rowsChart(
                $ptsp['pengaduan_status'] ?? [],
                'Status Pengaduan'
            ),
            'chartPtspKlasifikasi' => $this->rowsChart(
                $ptsp['pengaduan_klasifikasi'] ?? [],
                'Klasifikasi Pengaduan'
            ),
            'chartPtspPolling' => $this->barChart(
                array_map(
                    static fn ($value): string => 'Skor ' . (string) $value,
                    array_column($ptsp['polling']['scores'] ?? [], 'label')
                ),
                array_column($ptsp['polling']['scores'] ?? [], 'total'),
                'Distribusi Kepuasan PTSP',
                5
            ),
            'chartMobility' => $this->rowsChart(
                $mobility['status'] ?? [],
                'Mobilitas / Status Siswa'
            ),
        ];
    }

    private function rowsChart(array $rows, string $title, int $limit = 10): ?string
    {
        return $this->barChart(
            array_column($rows, 'label'),
            array_column($rows, 'total'),
            $title,
            $limit
        );
    }

    private function rankingChart(array $rows, string $title): ?string
    {
        return $this->barChart(
            array_column($rows, 'nama_siswa'),
            array_column($rows, 'total'),
            $title,
            10
        );
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
        $svg .= '<text x="12" y="22" font-family="DejaVu Sans" font-size="14" font-weight="700" fill="#566a7f">'
            . $this->xml($title) . '</text>';

        foreach ($values as $i => $value) {
            $y = 38 + ($i * $rowHeight);
            $label = $this->short((string) ($labels[$i] ?? '-'), 27);
            $w = max(1, ($value / $max) * $barWidth);
            $svg .= '<text x="12" y="' . ($y + 16)
                . '" font-family="DejaVu Sans" font-size="10" fill="#566a7f">'
                . $this->xml($label) . '</text>';
            $svg .= '<rect x="' . $left . '" y="' . $y
                . '" width="' . $barWidth . '" height="19" rx="3" fill="#eceef1"/>';
            $svg .= '<rect x="' . $left . '" y="' . $y
                . '" width="' . round($w, 2) . '" height="19" rx="3" fill="#696cff"/>';
            $svg .= '<text x="' . ($width - 8) . '" y="' . ($y + 15)
                . '" text-anchor="end" font-family="DejaVu Sans" font-size="10" font-weight="700" fill="#566a7f">'
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
