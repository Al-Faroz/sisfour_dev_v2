<?php

namespace App\Services;

use CodeIgniter\I18n\Time;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

class KonselingBkExportService
{
    private const TZ = 'Asia/Jakarta';

    public function export(
        array $rows,
        array $followUps = [],
        array $groupRows = [],
        array $groupMembers = [],
        array $groupFollowUps = [],
        array $context = []
    ): array {
        $mode = (($context['mode'] ?? '') === 'lengkap') ? 'lengkap' : 'ringkas';
        $scope = (($context['scope'] ?? '') === 'year') ? 'year' : 'filtered';

        $membersByGroup = [];
        $classesByGroup = [];
        $studentKeys = [];
        foreach ($groupMembers as $member) {
            $groupId = (int) ($member['id_konseling_kelompok'] ?? 0);
            if ($groupId <= 0) continue;
            $name = trim((string) ($member['nama_siswa'] ?? ''));
            $class = trim((string) ($member['nama_kelas'] ?? ''));
            if ($name !== '') $membersByGroup[$groupId][] = $name;
            if ($class !== '') $classesByGroup[$groupId][] = $class;
            $studentKey = trim((string) ($member['nisn'] ?? $name));
            if ($studentKey !== '') $studentKeys[$studentKey] = true;
        }
        foreach ($membersByGroup as $groupId => $names) {
            $membersByGroup[$groupId] = array_values(array_unique($names));
            $classesByGroup[$groupId] = array_values(array_unique($classesByGroup[$groupId] ?? []));
        }

        $groupMap = [];
        foreach ($groupRows as $group) $groupMap[(int) ($group['id'] ?? 0)] = $group;

        $completed = 0;
        $process = 0;
        foreach ($rows as $row) {
            (($row['status'] ?? '') === 'Selesai') ? $completed++ : $process++;
            $studentKey = trim((string) ($row['nisn'] ?? $row['nama_siswa'] ?? ''));
            if ($studentKey !== '') $studentKeys[$studentKey] = true;
        }
        foreach ($groupRows as $row) {
            (($row['status'] ?? '') === 'Selesai') ? $completed++ : $process++;
        }

        try {
            $spreadsheet = new Spreadsheet();
            $summary = $spreadsheet->getActiveSheet();
            $this->writeSummarySheet(
                $summary, 'Ringkasan', 'Ringkasan Export Konseling BK',
                [
                    'Tahun Ajaran' => $this->periodLabel((array) ($context['tahun_dipilih'] ?? [])),
                    'Cakupan Export' => $scope === 'year' ? 'Seluruh Tahun Ajaran terpilih' : 'Sesuai filter saat ini',
                    'Format Workbook' => $mode === 'lengkap' ? 'Lengkap / Audit' : 'Ringkas',
                    'Klasifikasi' => 'RAHASIA — hanya untuk pengguna berwenang',
                    'Dibuat Pada' => Time::now(self::TZ)->format('Y-m-d H:i:s'),
                ],
                [
                    'Konseling Individu' => count($rows),
                    'Konseling Kelompok' => count($groupRows),
                    'Siswa Unik' => count($studentKeys),
                    'Status Proses' => $process,
                    'Status Selesai' => $completed,
                    'Total Tindak Lanjut' => count($followUps) + count($groupFollowUps),
                ],
                $this->filterSummary((array) ($context['filter'] ?? []))
            );

            if ($mode === 'ringkas') {
                $rekapRows = [];
                foreach ($rows as $row) {
                    $rekapRows[] = [
                        0, $row['tanggal'] ?? '', 'INDIVIDU', $row['nama_siswa'] ?? '',
                        $row['nama_kelas'] ?? '', $row['bentuk_layanan'] ?? '',
                        $row['bidang'] ?? '', $row['topik'] ?? '', $row['status'] ?? '',
                        $row['rencana_berikutnya'] ?? '', $row['tanggal_berikutnya'] ?? '',
                    ];
                }
                foreach ($groupRows as $row) {
                    $groupId = (int) ($row['id'] ?? 0);
                    $names = implode('; ', $membersByGroup[$groupId] ?? []);
                    $classes = implode('; ', $classesByGroup[$groupId] ?? []);
                    $rekapRows[] = [
                        0, $row['tanggal'] ?? '', 'KELOMPOK', $names !== '' ? $names : 'Kelompok',
                        $classes, $row['bentuk_layanan'] ?? '', $row['bidang'] ?? '',
                        $row['topik'] ?? '', $row['status'] ?? '',
                        $row['rencana_berikutnya'] ?? '', $row['tanggal_berikutnya'] ?? '',
                    ];
                }
                usort($rekapRows, static fn(array $a, array $b): int => [$a[1], $a[2], $a[3]] <=> [$b[1], $b[2], $b[3]]);
                foreach ($rekapRows as $i => &$row) $row[0] = $i + 1;
                unset($row);

                $followRows = [];
                foreach ($followUps as $row) {
                    $followRows[] = [
                        0, $row['tanggal'] ?? '', 'INDIVIDU', $row['nama_siswa'] ?? '',
                        $row['nama_kelas'] ?? '', $row['perkembangan'] ?? '',
                        $row['status'] ?? '', $row['rencana_berikutnya'] ?? '',
                        $row['tanggal_berikutnya'] ?? '',
                    ];
                }
                foreach ($groupFollowUps as $row) {
                    $groupId = (int) ($row['id_konseling_kelompok'] ?? 0);
                    $followRows[] = [
                        0, $row['tanggal'] ?? '', 'KELOMPOK',
                        implode('; ', $membersByGroup[$groupId] ?? []),
                        implode('; ', $classesByGroup[$groupId] ?? []),
                        $row['perkembangan'] ?? '', $row['status'] ?? '',
                        $row['rencana_berikutnya'] ?? '', $row['tanggal_berikutnya'] ?? '',
                    ];
                }
                usort($followRows, static fn(array $a, array $b): int => [$a[1], $a[2], $a[3]] <=> [$b[1], $b[2], $b[3]]);
                foreach ($followRows as $i => &$row) $row[0] = $i + 1;
                unset($row);

                $dataSheet = $spreadsheet->createSheet();
                $this->writeDataSheet($dataSheet, 'Rekap Konseling', [
                    'No', 'Tanggal', 'Jenis Konseling', 'Siswa / Kelompok', 'Kelas',
                    'Bentuk Layanan', 'Bidang', 'Topik', 'Status',
                    'Rencana Berikutnya', 'Tanggal Berikutnya',
                ], $rekapRows);

                $followSheet = $spreadsheet->createSheet();
                $this->writeDataSheet($followSheet, 'Tindak Lanjut', [
                    'No', 'Tanggal', 'Jenis Konseling', 'Siswa / Kelompok', 'Kelas',
                    'Perkembangan', 'Status', 'Rencana Berikutnya', 'Tanggal Berikutnya',
                ], $followRows);
            } else {
                $dataRows = [];
                $detailRows = [];
                $sequence = 1;
                foreach ($rows as $row) {
                    $period = $this->periodLabel($row);
                    $dataRows[] = [
                        $sequence++, $row['tanggal'] ?? '', $period, 'INDIVIDU',
                        $row['nama_siswa'] ?? '', $row['nama_kelas'] ?? '', 1,
                        (int) ($row['pertemuan_ke'] ?? 1), $row['bentuk_layanan'] ?? '',
                        $row['cara_hadir'] ?? '', $row['bidang'] ?? '', $row['topik'] ?? '',
                        $row['status'] ?? '', $row['rencana_berikutnya'] ?? '',
                        $row['tanggal_berikutnya'] ?? '',
                        $row['nama_pencatat'] ?? $row['nama_guru_bk'] ?? '',
                    ];
                    $detailRows[] = [
                        count($detailRows) + 1, 'INDIVIDU', (int) ($row['id'] ?? 0),
                        $row['tanggal'] ?? '', $period, $row['nisn'] ?? '',
                        $row['nama_siswa'] ?? '', $row['nama_kelas'] ?? '',
                        $row['bidang'] ?? '', $row['topik'] ?? '',
                        $row['uraian_masalah'] ?? '', $row['hasil_kesepakatan'] ?? '',
                        $row['status'] ?? '', $row['nama_pencatat'] ?? $row['nama_guru_bk'] ?? '',
                    ];
                }
                foreach ($groupRows as $row) {
                    $groupId = (int) ($row['id'] ?? 0);
                    $period = $this->periodLabel($row);
                    $names = implode('; ', $membersByGroup[$groupId] ?? []);
                    $classes = implode('; ', $classesByGroup[$groupId] ?? []);
                    $dataRows[] = [
                        $sequence++, $row['tanggal'] ?? '', $period, 'KELOMPOK',
                        $names !== '' ? $names : ('Kelompok #' . $groupId), $classes,
                        (int) ($row['jumlah_anggota'] ?? count($membersByGroup[$groupId] ?? [])),
                        (int) ($row['pertemuan_ke'] ?? 1), $row['bentuk_layanan'] ?? '',
                        $row['cara_hadir'] ?? '', $row['bidang'] ?? '', $row['topik'] ?? '',
                        $row['status'] ?? '', $row['rencana_berikutnya'] ?? '',
                        $row['tanggal_berikutnya'] ?? '',
                        $row['nama_pencatat'] ?? $row['username_pencatat'] ?? '',
                    ];
                    $detailRows[] = [
                        count($detailRows) + 1, 'KELOMPOK', $groupId, $row['tanggal'] ?? '',
                        $period, '', $names, $classes, $row['bidang'] ?? '',
                        $row['topik'] ?? '', $row['uraian_masalah'] ?? '',
                        $row['hasil_kesepakatan'] ?? '', $row['status'] ?? '',
                        $row['nama_pencatat'] ?? $row['username_pencatat'] ?? '',
                    ];
                }
                usort($dataRows, static fn(array $a, array $b): int => [$a[1], $a[3], $a[4]] <=> [$b[1], $b[3], $b[4]]);
                foreach ($dataRows as $i => &$row) $row[0] = $i + 1;
                unset($row);

                $followRows = [];
                foreach ($followUps as $row) {
                    $followRows[] = [
                        0, 'INDIVIDU', $row['tanggal_konseling'] ?? '', $row['tanggal'] ?? '',
                        $row['nama_siswa'] ?? '', $row['nama_kelas'] ?? '',
                        $row['perkembangan'] ?? '', $row['hasil_kesepakatan'] ?? '',
                        $row['rencana_berikutnya'] ?? '', $row['tanggal_berikutnya'] ?? '',
                        $row['status'] ?? '', $row['nama_pencatat'] ?? $row['username_pencatat'] ?? '',
                        (int) ($row['id_konseling'] ?? 0),
                    ];
                }
                foreach ($groupFollowUps as $row) {
                    $groupId = (int) ($row['id_konseling_kelompok'] ?? 0);
                    $parent = $groupMap[$groupId] ?? [];
                    $followRows[] = [
                        0, 'KELOMPOK', $parent['tanggal'] ?? '', $row['tanggal'] ?? '',
                        implode('; ', $membersByGroup[$groupId] ?? []),
                        implode('; ', $classesByGroup[$groupId] ?? []),
                        $row['perkembangan'] ?? '', $row['hasil_kesepakatan'] ?? '',
                        $row['rencana_berikutnya'] ?? '', $row['tanggal_berikutnya'] ?? '',
                        $row['status'] ?? '', $row['nama_pencatat'] ?? $row['username_pencatat'] ?? '',
                        $groupId,
                    ];
                }
                usort($followRows, static fn(array $a, array $b): int => [$a[3], $a[1], $a[4]] <=> [$b[3], $b[1], $b[4]]);
                foreach ($followRows as $i => &$row) $row[0] = $i + 1;
                unset($row);

                $memberRows = [];
                foreach ($groupMembers as $row) {
                    $groupId = (int) ($row['id_konseling_kelompok'] ?? 0);
                    $parent = $groupMap[$groupId] ?? [];
                    $memberRows[] = [
                        count($memberRows) + 1, $parent['tanggal'] ?? '',
                        $parent['topik'] ?? '', $row['nisn'] ?? '',
                        $row['nama_siswa'] ?? '', $row['nama_kelas'] ?? '', $groupId,
                    ];
                }

                $dataSheet = $spreadsheet->createSheet();
                $this->writeDataSheet($dataSheet, 'Data Konseling', [
                    'No', 'Tanggal', 'Tahun Ajaran', 'Jenis Konseling', 'Siswa / Anggota',
                    'Kelas', 'Jumlah Anggota', 'Pertemuan Ke', 'Bentuk Layanan',
                    'Cara Hadir', 'Bidang', 'Topik', 'Status', 'Rencana Berikutnya',
                    'Tanggal Berikutnya', 'Dicatat Oleh',
                ], $dataRows);

                $detailSheet = $spreadsheet->createSheet();
                $this->writeDataSheet($detailSheet, 'Detail Konseling', [
                    'No', 'Jenis Konseling', 'ID Referensi', 'Tanggal', 'Tahun Ajaran',
                    'NISN', 'Siswa / Anggota', 'Kelas', 'Bidang', 'Topik',
                    'Uraian Masalah', 'Hasil / Kesepakatan', 'Status', 'Dicatat Oleh',
                ], $detailRows);

                $followSheet = $spreadsheet->createSheet();
                $this->writeDataSheet($followSheet, 'Riwayat Tindak Lanjut', [
                    'No', 'Jenis Konseling', 'Tanggal Konseling', 'Tanggal Tindak Lanjut',
                    'Siswa / Anggota', 'Kelas', 'Perkembangan', 'Hasil / Kesepakatan',
                    'Rencana Berikutnya', 'Tanggal Berikutnya', 'Status', 'Dicatat Oleh',
                    'ID Referensi',
                ], $followRows);

                $memberSheet = $spreadsheet->createSheet();
                $this->writeDataSheet($memberSheet, 'Anggota Kelompok', [
                    'No', 'Tanggal Konseling', 'Topik', 'NISN', 'Nama Siswa',
                    'Kelas', 'ID Kelompok',
                ], $memberRows);
            }

            $spreadsheet->setActiveSheetIndex(0);
            $dir = WRITEPATH . 'cache/exports';
            if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
                throw new \RuntimeException('Folder export tidak dapat dibuat.');
            }
            $filename = 'konseling_bk_' . $mode . '_' . date('Ymd_His') . '.xlsx';
            $path = $dir . DIRECTORY_SEPARATOR . $filename;
            (new Xlsx($spreadsheet))->save($path);
            $spreadsheet->disconnectWorksheets();
            return ['success' => true, 'path' => $path, 'filename' => $filename];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'code' => 'EXPORT_FAILED',
                'message' => 'Export Konseling BK gagal dibuat.',
                'error' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ];
        }
    }

    private function periodLabel(array $row): string
    {
        $tahun = trim((string) ($row['nama_tahun'] ?? ''));
        $semester = trim((string) ($row['semester'] ?? ''));
        if ($tahun === '') return '-';
        return $semester !== '' ? $tahun . ' - ' . $semester : $tahun;
    }

    private function filterSummary(array $filter): string
    {
        $parts = [];
        if (! empty($filter['search'])) $parts[] = 'Pencarian: ' . $filter['search'];
        if (! empty($filter['id_kelas'])) $parts[] = 'Filter kelas aktif';
        if (! empty($filter['status'])) $parts[] = 'Status: ' . $filter['status'];
        if (! empty($filter['bidang'])) $parts[] = 'Bidang: ' . $filter['bidang'];
        if (! empty($filter['tanggal_mulai'])) $parts[] = 'Dari: ' . $filter['tanggal_mulai'];
        if (! empty($filter['tanggal_selesai'])) $parts[] = 'Sampai: ' . $filter['tanggal_selesai'];
        return $parts === [] ? 'Tidak ada filter tambahan.' : implode(' | ', $parts);
    }

    private function writeSummarySheet(
        Worksheet $sheet,
        string $title,
        string $heading,
        array $meta,
        array $metrics,
        string $filterText
    ): void {
        $sheet->setTitle($title);
        $sheet->mergeCells('A1:F1');
        $sheet->setCellValueExplicit('A1', $heading, DataType::TYPE_STRING);
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 14],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '696CFF']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $row = 3;
        foreach ($meta as $label => $value) {
            $sheet->setCellValueExplicit('A' . $row, (string) $label, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('B' . $row, (string) $value, DataType::TYPE_STRING);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;
        }

        $sheet->mergeCells('D3:F3');
        $sheet->setCellValueExplicit('D3', 'Filter Digunakan', DataType::TYPE_STRING);
        $sheet->getStyle('D3:F3')->getFont()->setBold(true);
        $sheet->mergeCells('D4:F7');
        $sheet->setCellValueExplicit('D4', $filterText, DataType::TYPE_STRING);
        $sheet->getStyle('D4:F7')->getAlignment()->setVertical('top')->setWrapText(true);

        $metricRow = max($row + 1, 10);
        $sheet->mergeCells('A' . $metricRow . ':B' . $metricRow);
        $sheet->setCellValueExplicit('A' . $metricRow, 'Ringkasan Angka', DataType::TYPE_STRING);
        $sheet->getStyle('A' . $metricRow . ':B' . $metricRow)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '566A7F']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F2F4F7']],
        ]);
        $metricRow++;
        foreach ($metrics as $label => $value) {
            $sheet->setCellValueExplicit('A' . $metricRow, (string) $label, DataType::TYPE_STRING);
            $sheet->setCellValue('B' . $metricRow, (int) $value);
            $metricRow++;
        }

        $sheet->getColumnDimension('A')->setWidth(24);
        $sheet->getColumnDimension('B')->setWidth(32);
        $sheet->getColumnDimension('C')->setWidth(4);
        $sheet->getColumnDimension('D')->setWidth(24);
        $sheet->getColumnDimension('E')->setWidth(24);
        $sheet->getColumnDimension('F')->setWidth(24);
    }

    private function writeDataSheet(Worksheet $sheet, string $title, array $headers, array $rows): void
    {
        $sheet->setTitle(substr($title, 0, 31));
        foreach ($headers as $index => $header) {
            $sheet->setCellValueExplicit(
                Coordinate::stringFromColumnIndex($index + 1) . '1',
                (string) $header,
                DataType::TYPE_STRING
            );
        }

        $rowNumber = 2;
        foreach ($rows as $row) {
            foreach ($row as $index => $value) {
                $cell = Coordinate::stringFromColumnIndex($index + 1) . $rowNumber;
                if (is_int($value) || is_float($value)) {
                    $sheet->setCellValue($cell, $value);
                } else {
                    $sheet->setCellValueExplicit($cell, (string) ($value ?? ''), DataType::TYPE_STRING);
                }
            }
            $rowNumber++;
        }

        $lastColumn = Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '566A7F']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);
        if ($rowNumber > 2) {
            $sheet->getStyle("A2:{$lastColumn}" . ($rowNumber - 1))
                ->getAlignment()
                ->setVertical(Alignment::VERTICAL_TOP)
                ->setWrapText(true);
        }
        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:{$lastColumn}1");
        foreach ($headers as $index => $header) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($index + 1))
                ->setWidth($this->columnWidth((string) $header));
        }
    }

    private function columnWidth(string $header): float
    {
        $h = mb_strtolower($header);
        if ($h === 'no') return 6;
        if (str_contains($h, 'tanggal')) return 16;
        if (str_contains($h, 'tahun ajaran')) return 22;
        if ($h === 'nisn') return 16;
        if (str_contains($h, 'siswa / anggota') || str_contains($h, 'siswa / kelompok') || $h === 'nama siswa') return 38;
        if ($h === 'kelas') return 18;
        if (str_contains($h, 'uraian') || str_contains($h, 'perkembangan') || str_contains($h, 'hasil / kesepakatan')) return 42;
        if (str_contains($h, 'bentuk layanan') || str_contains($h, 'cara hadir')) return 24;
        if (str_contains($h, 'rencana')) return 28;
        if (str_contains($h, 'topik')) return 30;
        if (str_contains($h, 'dicatat oleh')) return 26;
        if (str_starts_with($h, 'id ')) return 14;
        return 18;
    }
}
