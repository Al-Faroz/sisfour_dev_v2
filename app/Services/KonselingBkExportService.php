<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

class KonselingBkExportService
{
    public function export(array $rows, array $followUps = []): array
    {
        try {
            $spreadsheet = new Spreadsheet();
            $this->writeSheet(
                $spreadsheet->getActiveSheet(),
                'Konseling BK',
                [
                    'No', 'Tahun Ajaran', 'Tanggal', 'Pertemuan Ke', 'Kelas', 'NISN', 'Nama Siswa',
                    'Bentuk Layanan', 'Cara Hadir', 'Bidang', 'Topik', 'Uraian Masalah',
                    'Hasil Pembahasan & Kesepakatan', 'Rencana Berikutnya', 'Tanggal Berikutnya',
                    'Status', 'Dicatat Oleh',
                ],
                array_map(static function (array $row, int $i): array {
                    $tahun = trim((string) ($row['nama_tahun'] ?? ''));
                    $semester = trim((string) ($row['semester'] ?? ''));
                    return [
                        $i + 1,
                        trim($tahun . ($semester !== '' ? ' - ' . $semester : '')),
                        $row['tanggal'] ?? '',
                        (int) ($row['pertemuan_ke'] ?? 1),
                        $row['nama_kelas'] ?? '',
                        $row['nisn'] ?? '',
                        $row['nama_siswa'] ?? '',
                        $row['bentuk_layanan'] ?? '',
                        $row['cara_hadir'] ?? '',
                        $row['bidang'] ?? '',
                        $row['topik'] ?? '',
                        $row['uraian_masalah'] ?? '',
                        $row['hasil_kesepakatan'] ?? '',
                        $row['rencana_berikutnya'] ?? '',
                        $row['tanggal_berikutnya'] ?? '',
                        $row['status'] ?? '',
                        $row['nama_pencatat'] ?? $row['nama_guru_bk'] ?? '',
                    ];
                }, $rows, array_keys($rows))
            );

            $followSheet = new Worksheet($spreadsheet, 'Tindak Lanjut');
            $spreadsheet->addSheet($followSheet);
            $this->writeSheet(
                $followSheet,
                'Tindak Lanjut',
                [
                    'No', 'ID Konseling', 'Tahun Ajaran', 'Tanggal Konseling', 'Tanggal Tindak Lanjut',
                    'Kelas', 'NISN', 'Nama Siswa', 'Perkembangan', 'Hasil/Kesepakatan',
                    'Rencana Berikutnya', 'Tanggal Berikutnya', 'Status', 'Dicatat Oleh',
                ],
                array_map(static function (array $row, int $i): array {
                    $tahun = trim((string) ($row['nama_tahun'] ?? ''));
                    $semester = trim((string) ($row['semester'] ?? ''));
                    return [
                        $i + 1,
                        (int) ($row['id_konseling'] ?? 0),
                        trim($tahun . ($semester !== '' ? ' - ' . $semester : '')),
                        $row['tanggal_konseling'] ?? '',
                        $row['tanggal'] ?? '',
                        $row['nama_kelas'] ?? '',
                        $row['nisn'] ?? '',
                        $row['nama_siswa'] ?? '',
                        $row['perkembangan'] ?? '',
                        $row['hasil_kesepakatan'] ?? '',
                        $row['rencana_berikutnya'] ?? '',
                        $row['tanggal_berikutnya'] ?? '',
                        $row['status'] ?? '',
                        $row['nama_pencatat'] ?? $row['username_pencatat'] ?? '',
                    ];
                }, $followUps, array_keys($followUps))
            );

            $spreadsheet->setActiveSheetIndex(0);
            $dir = WRITEPATH . 'cache/exports';
            if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
                throw new \RuntimeException('Folder export tidak dapat dibuat.');
            }

            $filename = 'konseling_bk_' . date('Ymd_His') . '.xlsx';
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

    private function writeSheet(Worksheet $sheet, string $title, array $headers, array $rows): void
    {
        $sheet->setTitle($title);
        foreach ($headers as $index => $header) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($index + 1) . '1', $header);
        }

        $rowNumber = 2;
        foreach ($rows as $row) {
            foreach ($row as $index => $value) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($index + 1) . $rowNumber, $value);
            }
            $rowNumber++;
        }

        $lastColumn = Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true);
        $sheet->getStyle("A1:{$lastColumn}" . max(1, $rowNumber - 1))->getAlignment()->setVertical('top')->setWrapText(true);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:{$lastColumn}1");
        foreach (range(1, count($headers)) as $column) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setAutoSize($column <= 10);
        }
    }
}
