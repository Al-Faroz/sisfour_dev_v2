<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

class DokumenSiswaExportService
{
    public function metadata(array $rows): array
    {
        $headers = [
            'No','ID','Judul','Target','Tahun Ajaran','Semester','Tingkat',
            'NISN','Nama Siswa','Kelas','Format','Status','Link Google Drive',
            'Dicatat Oleh','Created At'
        ];

        $data = [];
        foreach ($rows as $i => $row) {
            $data[] = [
                $i + 1,
                (int) ($row['id'] ?? 0),
                $row['judul'] ?? '',
                $row['target_type'] ?? '',
                $row['nama_tahun'] ?? '',
                $row['semester'] ?? '',
                $row['tingkat'] ?? '',
                $row['nisn'] ?? '',
                $row['nama_siswa'] ?? '',
                $row['nama_kelas'] ?? '',
                $row['format_file'] ?? '',
                $row['status'] ?? '',
                $row['link_gdrive'] ?? '',
                $row['nama_pencatat'] ?? $row['username_pencatat'] ?? '',
                $row['created_at'] ?? '',
            ];
        }

        return $this->write('Dokumen Siswa', $headers, $data, 'dokumen_siswa_' . date('Ymd_His') . '.xlsx');
    }

    public function template(array $rows, array $period): array
    {
        $headers = ['NISN','NAMA SISWA','KELAS','LINK GOOGLE DRIVE'];
        $data = array_map(static fn (array $row): array => [
            (string) ($row['nisn'] ?? ''),
            (string) ($row['nama_siswa'] ?? ''),
            (string) ($row['nama_kelas'] ?? ''),
            '',
        ], $rows);

        $label = preg_replace('/[^0-9A-Za-z]+/', '_', trim(
            (string) ($period['nama_tahun'] ?? '') . '_' .
            (string) ($period['semester'] ?? '')
        )) ?: 'periode';

        return $this->write(
            'DATA_DOKUMEN',
            $headers,
            $data,
            'template_dokumen_siswa_' . trim($label, '_') . '.xlsx'
        );
    }

    private function write(string $title, array $headers, array $rows, string $filename): array
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle(substr($title, 0, 31));

            foreach ($headers as $i => $header) {
                $sheet->setCellValueExplicit(
                    Coordinate::stringFromColumnIndex($i + 1) . '1',
                    $header,
                    DataType::TYPE_STRING
                );
            }

            $rowNo = 2;
            foreach ($rows as $row) {
                foreach ($row as $i => $value) {
                    $cell = Coordinate::stringFromColumnIndex($i + 1) . $rowNo;
                    if (is_int($value) || is_float($value)) {
                        $sheet->setCellValue($cell, $value);
                    } else {
                        $sheet->setCellValueExplicit($cell, (string) ($value ?? ''), DataType::TYPE_STRING);
                    }
                }
                $rowNo++;
            }

            $last = Coordinate::stringFromColumnIndex(count($headers));
            $sheet->getStyle("A1:{$last}1")->getFont()->setBold(true);
            $sheet->getStyle("A1:{$last}" . max(1, $rowNo - 1))->getAlignment()->setVertical('top')->setWrapText(true);
            $sheet->freezePane('A2');
            $sheet->setAutoFilter("A1:{$last}1");

            $dir = WRITEPATH . 'cache/exports';
            if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
                throw new \RuntimeException('Folder export tidak dapat dibuat.');
            }
            $path = $dir . DIRECTORY_SEPARATOR . $filename;
            (new Xlsx($spreadsheet))->save($path);
            $spreadsheet->disconnectWorksheets();

            return ['success' => true, 'path' => $path, 'filename' => $filename];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'code' => 'EXPORT_FAILED',
                'message' => 'XLSX gagal dibuat.',
                'error' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ];
        }
    }
}
