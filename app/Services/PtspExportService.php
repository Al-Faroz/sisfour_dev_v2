<?php

namespace App\Services;

use CodeIgniter\I18n\Time;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

class PtspExportService
{
    private const TZ = 'Asia/Jakarta';

    public function layanan(array $data, int $userId): array
    {
        $rows = [];
        $no = 1;
        foreach (($data['rows'] ?? []) as $row) {
            $rows[] = [
                $no++,
                $this->periodLabel($row),
                $row['created_at'] ?? '',
                $row['nama_lengkap'] ?? '',
                $row['kategori_pemohon'] ?? '',
                $row['nomor_whatsapp'] ?? '',
                $row['jenis_layanan'] ?? '',
                $row['tujuan_keterangan'] ?? '',
                $row['status'] ?? '',
                $row['petugas_nama'] ?? '',
            ];
        }

        $result = $this->write(
            'Layanan PTSP',
            ['No','Tahun Ajaran','Waktu Submit','Nama Lengkap','Kategori Pemohon','Nomor WhatsApp','Jenis Layanan','Tujuan / Keterangan','Status','Petugas Terakhir'],
            $rows,
            'ptsp_layanan_' . date('Ymd_His') . '.xlsx'
        );

        if ($result['success']) {
            $this->log($userId, 'PTSP Layanan', 'Export Layanan PTSP berdasarkan filter terpilih.');
        }

        return $result;
    }

    public function polling(array $data, int $userId): array
    {
        $rows = [];
        $no = 1;
        foreach (($data['rows'] ?? []) as $row) {
            $rows[] = [
                $no++,
                $this->periodLabel($row),
                $row['created_at'] ?? '',
                $row['nama_lengkap'] ?? '',
                $row['kategori_responden'] ?? '',
                $row['nomor_whatsapp'] ?? '',
                $row['tingkat_kepuasan'] ?? '',
                $row['score'] ?? '',
                $row['masukan_saran'] ?? '',
            ];
        }

        $result = $this->write(
            'Polling Kepuasan',
            ['No','Tahun Ajaran','Waktu Submit','Nama Lengkap','Kategori Responden','Nomor WhatsApp','Tingkat Kepuasan','Score','Masukan & Saran'],
            $rows,
            'ptsp_polling_' . date('Ymd_His') . '.xlsx'
        );

        if ($result['success']) {
            $this->log($userId, 'PTSP Polling', 'Export Polling Kepuasan berdasarkan filter terpilih.');
        }

        return $result;
    }

    public function pengaduan(array $data, int $userId): array
    {
        $rows = [];
        $no = 1;
        foreach (($data['rows'] ?? []) as $row) {
            $rows[] = [
                $no++,
                $this->periodLabel($row),
                $row['created_at'] ?? '',
                implode(', ', $row['klasifikasi'] ?? []),
                $row['judul_laporan'] ?? '',
                $row['isi_laporan'] ?? '',
                $row['tanggal_kejadian'] ?? '',
                $row['status'] ?? '',
                $row['lampiran_nama_asli'] ?? '',
                $row['petugas_nama'] ?? '',
            ];
        }

        $result = $this->write(
            'Pengaduan PTSP',
            ['No','Tahun Ajaran','Waktu Submit','Klasifikasi','Judul Laporan','Isi Laporan','Tanggal Kejadian','Status','Lampiran','Petugas Terakhir'],
            $rows,
            'ptsp_pengaduan_' . date('Ymd_His') . '.xlsx'
        );

        if ($result['success']) {
            $this->log($userId, 'PTSP Pengaduan', 'Export Pengaduan berdasarkan filter terpilih.');
        }

        return $result;
    }

    private function periodLabel(array $row): string
    {
        $tahun = trim((string) ($row['nama_tahun'] ?? ''));
        $semester = trim((string) ($row['semester'] ?? ''));
        return $semester !== '' ? $tahun . ' - ' . $semester : ($tahun !== '' ? $tahun : '-');
    }

    private function write(string $title, array $headers, array $rows, string $filename): array
    {
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

            return ['success' => true, 'path' => $path, 'filename' => $filename];
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
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($i + 1) . '1', $header);
        }

        $rowNo = 2;
        foreach ($rows as $row) {
            foreach ($row as $i => $value) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($i + 1) . $rowNo, $value);
            }
            $rowNo++;
        }

        $last = Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle("A1:{$last}1")->getFont()->setBold(true);
        $sheet->getStyle("A1:{$last}" . max(1, $rowNo - 1))->getAlignment()->setVertical('top')->setWrapText(true);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:{$last}1");
        foreach (range(1, count($headers)) as $column) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setAutoSize(true);
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
