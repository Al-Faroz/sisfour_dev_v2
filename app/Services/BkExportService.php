<?php

namespace App\Services;

use CodeIgniter\I18n\Time;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

class BkExportService
{
    private const TZ = 'Asia/Jakarta';

    public function kasus(array $data, int $userId): array
    {
        $headers = ['No', 'NISN', 'Nama Siswa', 'Tanggal', 'Pelanggaran', 'Kategori', 'Poin', 'Keterangan'];
        $rows = [];
        $no = 1;

        foreach ($data['rows'] as $row) {
            $rows[] = [
                $no++,
                $row['nisn'],
                $row['nama_siswa'],
                $row['tanggal'],
                $row['nama_pelanggaran'],
                $row['kategori'],
                (int) $row['poin'],
                $row['keterangan'] ?? '',
            ];
        }

        $result = $this->write('Catatan Kasus', $headers, $rows, 'catatan_kasus_' . date('Ymd_His') . '.xlsx');

        if ($result['success']) {
            $this->log($userId, 'BK Kasus', 'Export Catatan Kasus');
        }

        return $result;
    }

    public function prestasi(array $data, int $userId): array
    {
        $headers = ['No', 'NISN', 'Nama Siswa', 'Tanggal', 'Prestasi', 'Tingkat', 'Penyelenggara', 'Keterangan'];
        $rows = [];
        $no = 1;

        foreach ($data['rows'] as $row) {
            $rows[] = [
                $no++,
                $row['nisn'],
                $row['nama_siswa'],
                $row['tanggal'],
                $row['nama_prestasi'],
                $row['tingkat'],
                $row['penyelenggara'] ?? '',
                $row['keterangan'] ?? '',
            ];
        }

        $result = $this->write('Prestasi', $headers, $rows, 'prestasi_' . date('Ymd_His') . '.xlsx');

        if ($result['success']) {
            $this->log($userId, 'Prestasi', 'Export Prestasi');
        }

        return $result;
    }

    private function write(string $title, array $headers, array $rows, string $filename): array
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle(substr($title, 0, 31));

            foreach ($headers as $i => $header) {
                $sheet->setCellValue(
                    Coordinate::stringFromColumnIndex($i + 1) . '1',
                    $header
                );
            }

            $rowNo = 2;

            foreach ($rows as $row) {
                foreach ($row as $i => $value) {
                    $sheet->setCellValue(
                        Coordinate::stringFromColumnIndex($i + 1) . $rowNo,
                        $value
                    );
                }

                $rowNo++;
            }

            $last = Coordinate::stringFromColumnIndex(count($headers));
            $sheet->getStyle("A1:{$last}1")->getFont()->setBold(true);
            $sheet->getStyle("A1:{$last}" . max(1, $rowNo - 1))
                ->getAlignment()
                ->setVertical('top')
                ->setWrapText(true);
            $sheet->freezePane('A2');
            $sheet->setAutoFilter("A1:{$last}1");

            foreach (range(1, count($headers)) as $column) {
                $sheet->getColumnDimension(
                    Coordinate::stringFromColumnIndex($column)
                )->setAutoSize($column !== count($headers));
            }

            $dir = WRITEPATH . 'cache/exports';

            if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
                throw new \RuntimeException('Folder export tidak dapat dibuat.');
            }

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
                'message' => 'Export XLSX gagal dibuat.',
                'error' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ];
        }
    }

    private function log(int $userId, string $modul, string $keterangan): void
    {
        db_connect()->table('log_activity')->insert([
            'id_user' => $userId,
            'aksi' => 'EXPORT',
            'modul' => $modul,
            'keterangan' => $keterangan,
            'waktu' => Time::now(self::TZ)->format('Y-m-d H:i:s'),
        ]);
    }
}
