<?php

namespace App\Services;

use CodeIgniter\I18n\Time;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

/**
 * Export XLSX Jurnal G3.2.
 *
 * Export tetap parent-level: satu row = satu Jurnal. Child siswa tetap
 * diringkas sebagai count S/I/A, sekaligus menyertakan daftar nama siswa
 * per status tanpa menggandakan row parent.
 */
class JurnalExportService
{
    private const TZ = 'Asia/Jakarta';

    public function exportJurnal(array $data, int $userId): array
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Laporan Jurnal');

            $headers = [
                'No',
                'Tanggal',
                'Hari',
                'Jam',
                'NIP',
                'Guru',
                'Kelas',
                'Kode Mapel',
                'Mata Pelajaran',
                'Sesi',
                'Status Guru',
                'Materi',
                'Catatan',
                'Sakit Siswa',
                'Izin Siswa',
                'Alpha Siswa',
                'Total S/I/A',
                'Nama Siswa Sakit',
                'Nama Siswa Izin',
                'Nama Siswa Alpha',
                'Tahun Ajaran',
                'Semester',
            ];

            foreach ($headers as $i => $header) {
                $this->setCell($sheet, $i + 1, 1, $header);
            }

            $rowNumber = 2;
            $no = 1;

            foreach (($data['rows'] ?? []) as $row) {
                $summary = is_array($row['siswa_exception_summary'] ?? null)
                    ? $row['siswa_exception_summary']
                    : [];
                $details = is_array($row['siswa_exception_details'] ?? null)
                    ? $row['siswa_exception_details']
                    : [];
                $kelas = trim((string) ($row['nama_kelas'] ?? '-'));

                $values = [
                    $no++,
                    $row['tanggal'] ?? '',
                    $row['hari'] ?? '',
                    ($row['jam_mulai'] ?? '') . ' - ' . ($row['jam_selesai'] ?? ''),
                    $row['nip'] ?? '-',
                    $row['nama_guru_snapshot'] ?? '-',
                    $row['nama_kelas'] ?? '-',
                    $row['kode_mapel'] ?? '-',
                    $row['nama_mapel'] ?? '-',
                    $row['sesi'] ?? '-',
                    $row['status'] ?? '-',
                    $row['materi'] ?? '',
                    $row['catatan'] ?? '',
                    (int) ($summary['Sakit'] ?? 0),
                    (int) ($summary['Izin'] ?? 0),
                    (int) ($summary['Alpha'] ?? 0),
                    (int) ($summary['total'] ?? 0),
                    $this->studentNamesWithClass(
                        $details['Sakit'] ?? [],
                        $kelas
                    ),
                    $this->studentNamesWithClass(
                        $details['Izin'] ?? [],
                        $kelas
                    ),
                    $this->studentNamesWithClass(
                        $details['Alpha'] ?? [],
                        $kelas
                    ),
                    $row['nama_tahun'] ?? '-',
                    $row['semester'] ?? '-',
                ];

                foreach ($values as $i => $value) {
                    $this->setCell($sheet, $i + 1, $rowNumber, $value);
                }

                $rowNumber++;
            }

            $lastColLetter = Coordinate::stringFromColumnIndex(count($headers));
            $sheet->getStyle("A1:{$lastColLetter}1")->getFont()->setBold(true);
            $sheet->getStyle("A1:{$lastColLetter}" . max(1, $rowNumber - 1))
                ->getAlignment()
                ->setVertical('top')
                ->setWrapText(true);
            $sheet->freezePane('A2');
            $sheet->setAutoFilter("A1:{$lastColLetter}1");

            foreach (range(1, count($headers)) as $col) {
                $sheet->getColumnDimension(
                    Coordinate::stringFromColumnIndex($col)
                )->setAutoSize(! in_array(
                    $col,
                    [12, 13, 18, 19, 20],
                    true
                ));
            }

            $sheet->getColumnDimension('L')->setWidth(45);
            $sheet->getColumnDimension('M')->setWidth(40);
            $sheet->getColumnDimension('R')->setWidth(42);
            $sheet->getColumnDimension('S')->setWidth(42);
            $sheet->getColumnDimension('T')->setWidth(42);

            $filter = is_array($data['filter'] ?? null) ? $data['filter'] : [];
            $periode = ($filter['tanggal_mulai'] ?? 'awal')
                . '_'
                . ($filter['tanggal_selesai'] ?? 'akhir');

            $filename = 'laporan_jurnal_' . $this->slug($periode) . '.xlsx';
            $path = $this->writeTemp($spreadsheet, $filename);

            $this->logExport(
                $userId,
                'Laporan Jurnal',
                'Export Jurnal periode ' . str_replace('_', ' s.d. ', $periode)
            );

            return [
                'success' => true,
                'filename' => $filename,
                'path' => $path,
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'code' => 'EXPORT_FAILED',
                'message' => 'File XLSX Jurnal gagal dibuat.',
                'error' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ];
        }
    }

    private function setCell(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        int $column,
        int $row,
        mixed $value
    ): void {
        $coordinate = Coordinate::stringFromColumnIndex($column) . $row;
        $sheet->setCellValue($coordinate, $value);
    }

    private function studentNamesWithClass(
        mixed $names,
        string $kelas
    ): string {
        if (! is_array($names) || $names === []) {
            return '-';
        }

        $formatted = [];

        foreach ($names as $name) {
            $name = trim((string) $name);

            if ($name === '') {
                continue;
            }

            $formatted[] = $name . ' (' . ($kelas !== '' ? $kelas : '-') . ')';
        }

        return $formatted !== []
            ? implode(PHP_EOL, $formatted)
            : '-';
    }

    private function writeTemp(Spreadsheet $spreadsheet, string $filename): string
    {
        $dir = WRITEPATH . 'cache/exports';

        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('Folder export tidak dapat dibuat.');
        }

        $path = $dir . DIRECTORY_SEPARATOR . $filename;
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return $path;
    }

    private function logExport(
        int $userId,
        string $modul,
        string $keterangan
    ): void {
        db_connect()
            ->table('log_activity')
            ->insert([
                'id_user' => $userId,
                'aksi' => 'EXPORT',
                'modul' => $modul,
                'keterangan' => $keterangan,
                'waktu' => Time::now(self::TZ)->format('Y-m-d H:i:s'),
            ]);
    }

    private function slug(string $value): string
    {
        $value = preg_replace('/[^A-Za-z0-9_-]+/', '_', $value) ?? 'export';

        return trim($value, '_') ?: 'export';
    }
}
