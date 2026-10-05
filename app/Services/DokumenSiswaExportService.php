<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

class DokumenSiswaExportService
{
    public const HELPER_URL =
        'https://docs.google.com/spreadsheets/d/'
        . '16CKvPVbkxZk6zW9dTeN35ivk3Qi_bIzIXKQWCFsnywQ/'
        . 'edit?usp=sharing';

    public function metadata(
        array $rows,
        array $accessLogs = []
    ): array {
        $headers = [
            'No',
            'ID',
            'Judul',
            'Target',
            'Tingkat',
            'NISN',
            'Nama Siswa',
            'Kelas Saat Ini',
            'Format',
            'Status',
            'Link Google Drive',
            'Import Batch',
            'Dicatat Oleh',
            'Created At',
        ];

        $data = [];

        foreach ($rows as $i => $row) {
            $data[] = [
                $i + 1,
                (int) ($row['id'] ?? 0),
                $row['judul'] ?? '',
                $row['target_type'] ?? '',
                $row['tingkat'] ?? '',
                $row['nisn'] ?? '',
                $row['nama_siswa'] ?? '',
                $row['nama_kelas_current'] ?? '',
                $row['format_file'] ?? '',
                $row['status'] ?? '',
                $row['link_gdrive'] ?? '',
                $row['id_import_batch'] ?? '',
                $row['nama_pencatat']
                    ?? $row['username_pencatat']
                    ?? '',
                $row['created_at'] ?? '',
            ];
        }

        $accessHeaders = [
            'No',
            'ID Dokumen',
            'Judul',
            'NISN',
            'Nama Siswa',
            'Aksi',
            'Waktu',
            'Username',
        ];
        $accessData = [];

        foreach ($accessLogs as $i => $row) {
            $accessData[] = [
                $i + 1,
                (int) ($row['id_dokumen'] ?? 0),
                $row['judul'] ?? '',
                $row['nisn'] ?? '',
                $row['nama_siswa'] ?? '',
                $row['aksi'] ?? '',
                $row['waktu'] ?? '',
                $row['username'] ?? '',
            ];
        }

        return $this->writeWorkbook(
            'Dokumen Siswa',
            $headers,
            $data,
            'dokumen_siswa_'
                . date('Ymd_His')
                . '.xlsx',
            [
                [
                    'title' => 'Riwayat Akses',
                    'headers' => $accessHeaders,
                    'rows' => $accessData,
                ],
            ]
        );
    }

    public function template(
        array $rows,
        ?array $rosterPeriod = null
    ): array {
        $headers = [
            'NISN',
            'NAMA SISWA',
            'KELAS',
            'LINK GOOGLE DRIVE',
        ];

        $data = array_map(
            static fn (array $row): array => [
                (string) ($row['nisn'] ?? ''),
                (string) ($row['nama_siswa'] ?? ''),
                (string) ($row['nama_kelas'] ?? ''),
                '',
            ],
            $rows
        );

        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('DATA_DOKUMEN');
            $this->fillSheet(
                $sheet,
                $headers,
                $data
            );

            $guide = $spreadsheet->createSheet();
            $guide->setTitle('PETUNJUK');

            $periodLabel = $rosterPeriod !== null
                ? trim(
                    (string) (
                        $rosterPeriod['nama_tahun'] ?? ''
                    )
                    . ' - '
                    . (string) (
                        $rosterPeriod['semester'] ?? ''
                    )
                )
                : 'Tidak ada Tahun Ajaran aktif';

            $guideRows = [
                ['PETUNJUK BULK DOKUMEN SISWA'],
                [''],
                ['Template ini hanya untuk registrasi metadata/link Dokumen Individu.'],
                ['Roster/Kelas saat template dibuat: ' . $periodLabel],
                ['Dokumen hasil import TIDAK terikat Tahun Ajaran/Semester.'],
                [''],
                ['Alur kerja:'],
                ['1. Upload file PDF/JPG/JPEG/PNG ke folder Google Drive.'],
                ['2. Gunakan Spreadsheet Helper untuk membaca isi folder Google Drive.'],
                ['3. Pada helper, masukkan ID folder Google Drive.'],
                ['4. Helper menghasilkan Nama File, ID File, dan URL Penampil.'],
                ['5. Cocokkan file dengan siswa/NISN.'],
                ['6. Salin URL Penampil ke kolom LINK GOOGLE DRIVE pada DATA_DOKUMEN.'],
                ['7. Jangan mengubah NISN hasil template kecuali memang memperbaiki data kerja.'],
                ['8. Simpan sebagai XLSX lalu upload kembali ke SisFour.'],
                [''],
                ['Spreadsheet Helper Google Drive:'],
                ['BUKA SPREADSHEET HELPER'],
                [self::HELPER_URL],
                [''],
                ['Catatan: helper hanya alat bantu operator. SisFour tidak memanggil API/helper tersebut.'],
            ];

            foreach ($guideRows as $r => $row) {
                $guide->setCellValueExplicit(
                    'A' . ($r + 1),
                    (string) ($row[0] ?? ''),
                    DataType::TYPE_STRING
                );
            }

            $guide
                ->getCell('A18')
                ->getHyperlink()
                ->setUrl(self::HELPER_URL)
                ->setTooltip(
                    'Buka Spreadsheet Helper Google Drive'
                );
            $guide
                ->getStyle('A18')
                ->getFont()
                ->setUnderline(true);
            $guide
                ->getStyle('A1')
                ->getFont()
                ->setBold(true)
                ->setSize(14);
            $guide
                ->getStyle('A1:A21')
                ->getAlignment()
                ->setWrapText(true)
                ->setVertical('top');
            $guide
                ->getColumnDimension('A')
                ->setWidth(95);

            $spreadsheet->setActiveSheetIndex(0);

            return $this->saveSpreadsheet(
                $spreadsheet,
                'template_dokumen_siswa_'
                    . date('Ymd_His')
                    . '.xlsx'
            );
        } catch (Throwable $e) {
            return $this->failExport($e);
        }
    }

    private function writeWorkbook(
        string $title,
        array $headers,
        array $rows,
        string $filename,
        array $extraSheets = []
    ): array {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle(
                substr($title, 0, 31)
            );
            $this->fillSheet(
                $sheet,
                $headers,
                $rows
            );

            foreach ($extraSheets as $extra) {
                $extraSheet = $spreadsheet
                    ->createSheet();
                $extraSheet->setTitle(
                    substr(
                        (string) (
                            $extra['title'] ?? 'Sheet'
                        ),
                        0,
                        31
                    )
                );
                $this->fillSheet(
                    $extraSheet,
                    is_array(
                        $extra['headers'] ?? null
                    )
                        ? $extra['headers']
                        : [],
                    is_array(
                        $extra['rows'] ?? null
                    )
                        ? $extra['rows']
                        : []
                );
            }

            $spreadsheet->setActiveSheetIndex(0);

            return $this->saveSpreadsheet(
                $spreadsheet,
                $filename
            );
        } catch (Throwable $e) {
            return $this->failExport($e);
        }
    }

    private function fillSheet(
        Worksheet $sheet,
        array $headers,
        array $rows
    ): void {
        foreach ($headers as $i => $header) {
            $sheet->setCellValueExplicit(
                Coordinate::stringFromColumnIndex(
                    $i + 1
                ) . '1',
                (string) $header,
                DataType::TYPE_STRING
            );
        }

        $rowNo = 2;

        foreach ($rows as $row) {
            foreach ($row as $i => $value) {
                $cell = Coordinate::stringFromColumnIndex(
                    $i + 1
                ) . $rowNo;

                if (is_int($value)
                    || is_float($value)
                ) {
                    $sheet->setCellValue(
                        $cell,
                        $value
                    );
                } else {
                    $sheet->setCellValueExplicit(
                        $cell,
                        (string) ($value ?? ''),
                        DataType::TYPE_STRING
                    );
                }
            }

            $rowNo++;
        }

        if ($headers === []) {
            return;
        }

        $last = Coordinate
            ::stringFromColumnIndex(
                count($headers)
            );

        $sheet
            ->getStyle("A1:{$last}1")
            ->getFont()
            ->setBold(true);
        $sheet
            ->getStyle(
                "A1:{$last}"
                . max(1, $rowNo - 1)
            )
            ->getAlignment()
            ->setVertical('top')
            ->setWrapText(true);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter(
            "A1:{$last}1"
        );
    }

    private function saveSpreadsheet(
        Spreadsheet $spreadsheet,
        string $filename
    ): array {
        $dir = WRITEPATH . 'cache/exports';

        if (! is_dir($dir)
            && ! mkdir($dir, 0775, true)
            && ! is_dir($dir)
        ) {
            throw new \RuntimeException(
                'Folder export tidak dapat dibuat.'
            );
        }

        $path = $dir
            . DIRECTORY_SEPARATOR
            . $filename;

        (new Xlsx($spreadsheet))
            ->save($path);
        $spreadsheet->disconnectWorksheets();

        return [
            'success' => true,
            'path' => $path,
            'filename' => $filename,
        ];
    }

    private function failExport(
        Throwable $e
    ): array {
        return [
            'success' => false,
            'code' => 'EXPORT_FAILED',
            'message' => 'XLSX gagal dibuat.',
            'error' => ENVIRONMENT === 'development'
                ? $e->getMessage()
                : null,
        ];
    }
}
