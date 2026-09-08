<?php

namespace App\Services;

use CodeIgniter\I18n\Time;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

/**
 * ExportService
 *
 * XLSX minimum sesuai docs/06_LAPORAN.
 */
class ExportService
{
    private const TZ = 'Asia/Jakarta';

    public function exportPresensiBulanan(array $data, int $userId): array
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Presensi Bulanan');

            $days = (int) $data['period']['days'];
            $kelas = (string) $data['kelas']['nama_kelas'];
            $tahun = (string) $data['tahun']['nama_tahun'];
            $semester = (string) $data['tahun']['semester'];
            $bulan = (string) $data['bulan'];

            $setting = $this->getSchoolSettings();
            $lastCol = 8 + $days;
            $lastColLetter = Coordinate::stringFromColumnIndex($lastCol);

            $sheet->setCellValue('A1', $setting['nama_sekolah']);
            $sheet->mergeCells("A1:{$lastColLetter}1");
            $sheet->setCellValue('A2', "Rekap Presensi Bulanan - Kelas {$kelas}");
            $sheet->mergeCells("A2:{$lastColLetter}2");
            $sheet->setCellValue('A3', "Tahun Ajaran {$tahun} | Semester {$semester} | Bulan {$bulan}");
            $sheet->mergeCells("A3:{$lastColLetter}3");
            $sheet->setCellValue('A4', 'Generate: ' . Time::now(self::TZ)->format('Y-m-d H:i:s'));
            $sheet->mergeCells("A4:{$lastColLetter}4");

            $headers = ['No', 'NISN', 'Nama Siswa', 'Kelas', 'H', 'S', 'I', 'A'];

            for ($d = 1; $d <= $days; $d++) {
                $headers[] = str_pad((string) $d, 2, '0', STR_PAD_LEFT);
            }

            foreach ($headers as $i => $header) {
                $this->setCell($sheet, $i + 1, 6, $header);
            }

            $rowNumber = 7;
            $no = 1;

            foreach ($data['rows'] as $row) {
                $values = [
                    $no++,
                    (string) $row['nisn'],
                    (string) $row['nama'],
                    $kelas,
                    (int) $row['H'],
                    (int) $row['S'],
                    (int) $row['I'],
                    (int) $row['A'],
                ];

                for ($d = 1; $d <= $days; $d++) {
                    $aw = $row['days'][$d]['AW'] ?? '-';
                    $ak = $row['days'][$d]['AK'] ?? '-';
                    $values[] = "AW: {$aw}\nAK: {$ak}";
                }

                foreach ($values as $i => $value) {
                    $this->setCell($sheet, $i + 1, $rowNumber, $value);
                }

                $rowNumber++;
            }

            $sheet->getStyle("A1:{$lastColLetter}6")->getFont()->setBold(true);
            $sheet->getStyle("A6:{$lastColLetter}" . max(6, $rowNumber - 1))
                ->getAlignment()
                ->setVertical('center')
                ->setWrapText(true);
            $sheet->freezePane('A7');
            $sheet->setAutoFilter("A6:{$lastColLetter}6");

            foreach (range(1, $lastCol) as $col) {
                $sheet->getColumnDimension(
                    Coordinate::stringFromColumnIndex($col)
                )->setAutoSize($col <= 8);
            }

            $filename = sprintf(
                'presensi_bulanan_%s_%s_%s.xlsx',
                $this->slug($kelas),
                str_replace('/', '-', $tahun),
                str_replace('-', '', $bulan)
            );

            $path = $this->writeTemp($spreadsheet, $filename);

            $this->logExport(
                $userId,
                'Laporan Presensi',
                "Export bulanan kelas {$kelas} {$bulan}"
            );

            return [
                'success' => true,
                'filename' => $filename,
                'path' => $path,
            ];
        } catch (Throwable $e) {
            return $this->fail($e);
        }
    }

    public function exportPresensiSemester(array $data, int $userId): array
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Presensi Semester');

            $kelas = (string) $data['kelas']['nama_kelas'];
            $tahun = (string) $data['tahun']['nama_tahun'];
            $semester = (string) $data['tahun']['semester'];
            $months = $data['months'];

            $monthNames = [
                1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
                5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
                9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
            ];

            $headers = ['No', 'NISN', 'Nama Siswa', 'Kelas', 'Total H', 'Total S', 'Total I', 'Total A'];

            foreach ($months as $month) {
                foreach (['H', 'S', 'I', 'A'] as $status) {
                    $headers[] = $monthNames[$month] . ' ' . $status;
                }
            }

            $lastCol = count($headers);
            $lastColLetter = Coordinate::stringFromColumnIndex($lastCol);
            $setting = $this->getSchoolSettings();

            $sheet->setCellValue('A1', $setting['nama_sekolah']);
            $sheet->mergeCells("A1:{$lastColLetter}1");
            $sheet->setCellValue('A2', "Rekap Presensi Semester - Kelas {$kelas}");
            $sheet->mergeCells("A2:{$lastColLetter}2");
            $sheet->setCellValue('A3', "Tahun Ajaran {$tahun} | Semester {$semester}");
            $sheet->mergeCells("A3:{$lastColLetter}3");
            $sheet->setCellValue('A4', 'Generate: ' . Time::now(self::TZ)->format('Y-m-d H:i:s'));
            $sheet->mergeCells("A4:{$lastColLetter}4");

            foreach ($headers as $i => $header) {
                $this->setCell($sheet, $i + 1, 6, $header);
            }

            $rowNumber = 7;
            $no = 1;

            foreach ($data['rows'] as $row) {
                $values = [
                    $no++,
                    (string) $row['nisn'],
                    (string) $row['nama'],
                    $kelas,
                    (int) $row['H'],
                    (int) $row['S'],
                    (int) $row['I'],
                    (int) $row['A'],
                ];

                foreach ($months as $month) {
                    foreach (['H', 'S', 'I', 'A'] as $status) {
                        $values[] = (int) ($row['months'][$month][$status] ?? 0);
                    }
                }

                foreach ($values as $i => $value) {
                    $this->setCell($sheet, $i + 1, $rowNumber, $value);
                }

                $rowNumber++;
            }

            $sheet->getStyle("A1:{$lastColLetter}6")->getFont()->setBold(true);
            $sheet->freezePane('A7');
            $sheet->setAutoFilter("A6:{$lastColLetter}6");

            foreach (range(1, $lastCol) as $col) {
                $sheet->getColumnDimension(
                    Coordinate::stringFromColumnIndex($col)
                )->setAutoSize(true);
            }

            $filename = sprintf(
                'presensi_semester_%s_%s_%s.xlsx',
                $this->slug($kelas),
                str_replace('/', '-', $tahun),
                strtolower($semester)
            );

            $path = $this->writeTemp($spreadsheet, $filename);

            $this->logExport(
                $userId,
                'Laporan Presensi',
                "Export semester kelas {$kelas} {$tahun} {$semester}"
            );

            return [
                'success' => true,
                'filename' => $filename,
                'path' => $path,
            ];
        } catch (Throwable $e) {
            return $this->fail($e);
        }
    }

    public function exportJurnal(array $data, int $userId): array
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Laporan Jurnal');

            $headers = [
                'No', 'Tanggal', 'Hari', 'Jam', 'NIP', 'Guru', 'Kelas',
                'Kode Mapel', 'Mata Pelajaran', 'Sesi', 'Status',
                'Materi', 'Tahun Ajaran', 'Semester',
            ];

            foreach ($headers as $i => $header) {
                $this->setCell($sheet, $i + 1, 1, $header);
            }

            $rowNumber = 2;
            $no = 1;

            foreach ($data['rows'] as $row) {
                $values = [
                    $no++,
                    $row['tanggal'],
                    $row['hari'],
                    $row['jam_mulai'] . ' - ' . $row['jam_selesai'],
                    $row['nip'] ?? '-',
                    $row['nama_guru_snapshot'],
                    $row['nama_kelas'] ?? '-',
                    $row['kode_mapel'] ?? '-',
                    $row['nama_mapel'] ?? '-',
                    $row['sesi'],
                    $row['status'],
                    $row['materi'],
                    $row['nama_tahun'],
                    $row['semester'],
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
                )->setAutoSize($col !== 12);
            }

            $sheet->getColumnDimension('L')->setWidth(50);

            $filter = $data['filter'];
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
            return $this->fail($e);
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

    private function getSchoolSettings(): array
    {
        $rows = db_connect()
            ->table('setting_sistem')
            ->select('setting_key, setting_value')
            ->whereIn('setting_key', ['nama_sekolah', 'alamat_sekolah'])
            ->get()
            ->getResultArray();

        $map = [];

        foreach ($rows as $row) {
            $map[$row['setting_key']] = $row['setting_value'];
        }

        return [
            'nama_sekolah' => $map['nama_sekolah'] ?? 'SisisFour',
            'alamat_sekolah' => $map['alamat_sekolah'] ?? '',
        ];
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

    private function fail(Throwable $e): array
    {
        return [
            'success' => false,
            'code' => 'EXPORT_FAILED',
            'message' => 'File XLSX gagal dibuat.',
            'error' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
        ];
    }
}
