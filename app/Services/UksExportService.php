<?php

namespace App\Services;

use CodeIgniter\I18n\Time;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

class UksExportService
{
    private const TZ = 'Asia/Jakarta';

    public function ckg(array $data, int $userId): array
    {
        $rows = [];
        $no = 1;

        foreach (($data['rows'] ?? []) as $row) {
            $rows[] = [
                $no++,
                $this->periodLabel($row),
                $row['nisn'] ?? '',
                $row['nama_siswa'] ?? '',
                $row['nama_kelas'] ?? '',
                $row['tanggal'] ?? '',
                $row['berat_badan'] ?? '',
                $row['tinggi_badan'] ?? '',
                $row['status_gizi'] ?? '',
                $row['status_tinggi'] ?? '',
                $row['lingkar_perut'] ?? '',
                $row['tekanan_sistol'] ?? '',
                $row['tekanan_diastol'] ?? '',
                $row['gula_darah'] ?? '',
                $row['kondisi_gigi_mulut'] ?? '',
                $row['visus_kanan'] ?? '',
                $row['visus_kiri'] ?? '',
                $row['buta_warna'] ?? '',
                $row['hasil_pendengaran'] ?? '',
                $row['skrining_talasemia'] ?? '',
                $row['skrining_tuberkulosis'] ?? '',
            ];
        }

        $result = $this->write(
            'Data CKG',
            [
                'No','Tahun Ajaran','NISN','Nama Siswa','Kelas','Tanggal',
                'Berat badan (kg)','Tinggi badan (cm)','Status gizi (BB/U)',
                'Status tinggi (TB/U)','Lingkar perut (cm)',
                'Tekanan darah sistol','Tekanan darah diastol',
                'Gula darah (mg/dL)','Kondisi gigi dan mulut',
                'Visus mata kanan','Visus mata kiri','Buta warna',
                'Hasil tes pendengaran','Skrining talasemia (kelas VII)',
                'Skrining tuberkulosis',
            ],
            $rows,
            'uks_ckg_' . date('Ymd_His') . '.xlsx'
        );

        if ($result['success']) {
            $this->log($userId, 'UKS CKG', 'Export CKG berdasarkan Tahun Ajaran terpilih.');
        }

        return $result;
    }

    public function harian(array $data, int $userId): array
    {
        $rows = [];
        $no = 1;

        foreach (($data['rows'] ?? []) as $row) {
            $actions = array_map(
                static fn (array $item): string => (string) ($item['nama'] ?? ''),
                is_array($row['tindakan'] ?? null) ? $row['tindakan'] : []
            );

            $rows[] = [
                $no++,
                $this->periodLabel($row),
                $row['tanggal'] ?? '',
                $row['jam_masuk'] ?? '',
                $row['nisn'] ?? '',
                $row['nama_siswa'] ?? '',
                $row['nama_kelas'] ?? '',
                $row['keluhan'] ?? '',
                $row['catatan_keluhan'] ?? '',
                $row['suhu_tubuh'] ?? '',
                $row['tekanan_darah'] ?? '',
                implode(', ', array_filter($actions)),
                $row['obat_diberikan'] ?? '',
                $row['jam_keluar'] ?? '',
                $row['hasil'] ?? '',
                $row['orang_tua_dihubungi'] ?? '',
                $row['petugas_nama'] ?? '',
            ];
        }

        $result = $this->write(
            'Catatan Harian UKS',
            [
                'No','Tahun Ajaran','Tanggal','Jam masuk','NISN','Nama Siswa',
                'Kelas','Keluhan','Catatan keluhan','Suhu tubuh °C',
                'Tekanan darah','Tindakan','Obat yang diberikan','Jam keluar',
                'Hasil','Orang tua dihubungi','Petugas',
            ],
            $rows,
            'uks_harian_' . date('Ymd_His') . '.xlsx'
        );

        if ($result['success']) {
            $this->log($userId, 'UKS Harian', 'Export Catatan Harian UKS berdasarkan Tahun Ajaran terpilih.');
        }

        return $result;
    }

    public function ckgTemplate(int $userId): array
    {
        $headers = [
            'NISN',
            'Tanggal',
            'Berat badan (kg)',
            'Tinggi badan (cm)',
            'Status gizi (BB/U)',
            'Status tinggi (TB/U)',
            'Lingkar perut (cm)',
            'Tekanan darah sistol',
            'Tekanan darah diastol',
            'Gula darah (mg/dL)',
            'Kondisi gigi dan mulut',
            'Visus mata kanan',
            'Visus mata kiri',
            'Buta warna',
            'Hasil tes pendengaran',
            'Skrining talasemia (kelas VII)',
            'Skrining tuberkulosis',
        ];

        $result = $this->write(
            'Template CKG',
            $headers,
            [],
            'template_import_uks_ckg.xlsx'
        );

        if ($result['success']) {
            $this->log($userId, 'UKS CKG', 'Download template import CKG.');
        }

        return $result;
    }

    private function periodLabel(array $row): string
    {
        $tahun = trim((string) ($row['nama_tahun'] ?? ''));
        $semester = trim((string) ($row['semester'] ?? ''));

        if ($tahun === '') {
            return '-';
        }

        return $semester !== '' ? $tahun . ' - ' . $semester : $tahun;
    }

    private function write(
        string $title,
        array $headers,
        array $rows,
        string $filename
    ): array {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle(substr($title, 0, 31));
            $this->writeSheet($sheet, $headers, $rows);

            $dir = WRITEPATH . 'cache/exports';
            if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
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
                'message' => 'File XLSX gagal dibuat.',
                'error' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ];
        }
    }

    private function writeSheet(Worksheet $sheet, array $headers, array $rows): void
    {
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
            )->setAutoSize(true);
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
