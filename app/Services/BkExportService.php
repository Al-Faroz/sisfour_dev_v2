<?php

namespace App\Services;

use CodeIgniter\I18n\Time;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

class BkExportService
{
    private const TZ = 'Asia/Jakarta';

    public function kasus(array $data, int $userId): array
    {
        $caseRows = is_array($data['rows'] ?? null) ? $data['rows'] : [];
        $studentIds = array_values(array_unique(array_filter(array_map(
            static fn (array $row): int => (int) ($row['id_siswa'] ?? 0),
            $caseRows
        ))));
        $classMap = $this->activeClassMap($studentIds);
        $followUps = $this->followUpRows($caseRows);
        $followUpCount = [];

        foreach ($followUps as $row) {
            $idKasus = (int) ($row['id_kasus'] ?? 0);
            $followUpCount[$idKasus] = ($followUpCount[$idKasus] ?? 0) + 1;
        }

        $headers = [
            'No',
            'ID Catatan',
            'NISN',
            'Nama Siswa',
            'Kelas',
            'Tanggal',
            'Pelanggaran',
            'Kategori',
            'Keterangan',
            'Jumlah Tindak Lanjut',
        ];
        $rows = [];
        $caseMap = [];
        $no = 1;

        foreach ($caseRows as $row) {
            $idKasus = (int) ($row['id'] ?? 0);
            $idSiswa = (int) ($row['id_siswa'] ?? 0);
            $kelas = $classMap[$idSiswa] ?? '-';
            $caseMap[$idKasus] = $row + ['nama_kelas' => $kelas];

            $rows[] = [
                $no++,
                $idKasus,
                $row['nisn'] ?? '',
                $row['nama_siswa'] ?? '',
                $kelas,
                $row['tanggal'] ?? '',
                $row['nama_pelanggaran'] ?? '',
                $row['kategori'] ?? '',
                $row['keterangan'] ?? '',
                $followUpCount[$idKasus] ?? 0,
            ];
        }

        $followUpHeaders = [
            'No',
            'ID Catatan',
            'NISN',
            'Nama Siswa',
            'Kelas',
            'Tanggal Pelanggaran',
            'Pelanggaran',
            'Kategori',
            'Tanggal Tindak Lanjut',
            'Tindak Lanjut',
            'Keterangan Tindak Lanjut',
            'Dicatat Oleh',
        ];
        $followUpExportRows = [];
        $noFollowUp = 1;

        foreach ($followUps as $followUp) {
            $idKasus = (int) ($followUp['id_kasus'] ?? 0);
            $case = $caseMap[$idKasus] ?? [];
            $followUpExportRows[] = [
                $noFollowUp++,
                $idKasus,
                $case['nisn'] ?? '',
                $case['nama_siswa'] ?? '',
                $case['nama_kelas'] ?? '-',
                $case['tanggal'] ?? '',
                $case['nama_pelanggaran'] ?? '',
                $case['kategori'] ?? '',
                $followUp['tanggal'] ?? '',
                $followUp['tindak_lanjut'] ?? '',
                $followUp['keterangan'] ?? '',
                $followUp['nama_input'] ?? $followUp['username_input'] ?? '',
            ];
        }

        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Pelanggaran');
            $this->writeSheet($sheet, $headers, $rows);

            $followUpSheet = $spreadsheet->createSheet();
            $followUpSheet->setTitle('Tindak Lanjut');
            $this->writeSheet($followUpSheet, $followUpHeaders, $followUpExportRows);
            $spreadsheet->setActiveSheetIndex(0);

            $result = $this->saveSpreadsheet(
                $spreadsheet,
                'catatan_pelanggaran_' . date('Ymd_His') . '.xlsx'
            );
        } catch (Throwable $e) {
            $result = [
                'success' => false,
                'code' => 'EXPORT_FAILED',
                'message' => 'Export XLSX gagal dibuat.',
                'error' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ];
        }

        if ($result['success']) {
            $this->log($userId, 'BK Pelanggaran', 'Export Catatan Pelanggaran + Tindak Lanjut');
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

    private function activeClassMap(array $studentIds): array
    {
        if ($studentIds === []) {
            return [];
        }

        $db = db_connect();
        $tahun = $db->table('tahun_ajaran')
            ->select('id')
            ->where('status_aktif', 1)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();
        $idTahun = (int) ($tahun['id'] ?? 0);

        if ($idTahun <= 0) {
            return [];
        }

        $rows = $db->table('anggota_kelas ak')
            ->select('ak.id_siswa, k.nama_kelas')
            ->join('kelas k', 'k.id = ak.id_kelas')
            ->where('ak.id_tahun', $idTahun)
            ->whereIn('ak.id_siswa', $studentIds)
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['id_siswa']] = (string) ($row['nama_kelas'] ?? '-');
        }

        return $map;
    }

    private function followUpRows(array $caseRows): array
    {
        $caseIds = array_values(array_unique(array_filter(array_map(
            static fn (array $row): int => (int) ($row['id'] ?? 0),
            $caseRows
        ))));

        if ($caseIds === []) {
            return [];
        }

        $result = [];
        $db = db_connect();

        foreach (array_chunk($caseIds, 1000) as $chunk) {
            $rows = $db->table('tindak_lanjut_kasus tl')
                ->select([
                    'tl.id',
                    'tl.id_kasus',
                    'tl.tanggal',
                    'tl.tindak_lanjut',
                    'tl.keterangan',
                    'tl.id_user_input',
                    'u.username AS username_input',
                ])
                ->select(
                    'COALESCE(p.nama, g.nama, s.nama, u.username) AS nama_input',
                    false
                )
                ->join('users u', 'u.id = tl.id_user_input', 'left')
                ->join('pegawai p', 'p.id = u.id_pegawai', 'left')
                ->join('guru g', 'g.id = u.id_guru', 'left')
                ->join('siswa s', 's.id = u.id_siswa', 'left')
                ->whereIn('tl.id_kasus', $chunk)
                ->orderBy('tl.id_kasus', 'ASC')
                ->orderBy('tl.tanggal', 'ASC')
                ->orderBy('tl.id', 'ASC')
                ->get()
                ->getResultArray();

            array_push($result, ...$rows);
        }

        return $result;
    }

    private function write(string $title, array $headers, array $rows, string $filename): array
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle(substr($title, 0, 31));
            $this->writeSheet($sheet, $headers, $rows);

            return $this->saveSpreadsheet($spreadsheet, $filename);
        } catch (Throwable $e) {
            return [
                'success' => false,
                'code' => 'EXPORT_FAILED',
                'message' => 'Export XLSX gagal dibuat.',
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
            )->setAutoSize($column !== count($headers));
        }
    }

    private function saveSpreadsheet(Spreadsheet $spreadsheet, string $filename): array
    {
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
