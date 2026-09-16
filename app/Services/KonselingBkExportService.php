<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

class KonselingBkExportService
{
    public function export(array $rows): array
    {
        $headers = [
            'No',
            'Tanggal',
            'Pertemuan Ke',
            'Kelas',
            'NISN',
            'Nama Siswa',
            'Bentuk Layanan',
            'Cara Hadir',
            'Bidang',
            'Topik',
            'Uraian Masalah',
            'Hasil Pembahasan & Kesepakatan',
            'Rencana Berikutnya',
            'Tanggal Berikutnya',
            'Status',
            'Dicatat Oleh',
        ];

        $data = [];
        $no = 1;

        foreach ($rows as $row) {
            $data[] = [
                $no++,
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
        }

        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Konseling BK');

            foreach ($headers as $index => $header) {
                $cell = Coordinate::stringFromColumnIndex($index + 1) . '1';
                $sheet->setCellValue($cell, $header);
            }

            $rowNumber = 2;
            foreach ($data as $row) {
                foreach ($row as $index => $value) {
                    $cell = Coordinate::stringFromColumnIndex($index + 1) . $rowNumber;
                    $sheet->setCellValue($cell, $value);
                }
                $rowNumber++;
            }

            $lastColumn = Coordinate::stringFromColumnIndex(count($headers));
            $sheet->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true);
            $sheet->getStyle("A1:{$lastColumn}" . max(1, $rowNumber - 1))
                ->getAlignment()
                ->setVertical('top')
                ->setWrapText(true);
            $sheet->freezePane('A2');
            $sheet->setAutoFilter("A1:{$lastColumn}1");

            foreach (range(1, count($headers)) as $column) {
                $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))
                    ->setAutoSize($column <= 10);
            }

            $dir = WRITEPATH . 'cache/exports';
            if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
                throw new \RuntimeException('Folder export tidak dapat dibuat.');
            }

            $filename = 'konseling_bk_' . date('Ymd_His') . '.xlsx';
            $path = $dir . DIRECTORY_SEPARATOR . $filename;
            (new Xlsx($spreadsheet))->save($path);
            $spreadsheet->disconnectWorksheets();

            return [
                'success' => true,
                'path' => $path,
                'filename' => $filename,
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'code' => 'EXPORT_FAILED',
                'message' => 'Export Konseling BK gagal dibuat.',
                'error' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ];
        }
    }
}
