<?php

namespace App\Services;

use CodeIgniter\I18n\Time;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

class BkExportService
{
    private const TZ = 'Asia/Jakarta';
    private const MAX_EXPORT_ROWS = 50000;

    public function kasus(
        array $data,
        int $userId,
        string $mode = 'ringkas',
        string $scope = 'filtered'
    ): array {
        $mode = $mode === 'lengkap' ? 'lengkap' : 'ringkas';
        $scope = $scope === 'year' ? 'year' : 'filtered';
        $caseRows = is_array($data['rows'] ?? null) ? $data['rows'] : [];
        $followUps = $this->followUpRows($caseRows);
        if (count($followUps) > self::MAX_EXPORT_ROWS) {
            return [
                'success' => false,
                'code' => 'EXPORT_TOO_LARGE',
                'message' => 'Tindak lanjut melebihi 50.000 baris. Persempit filter Tahun Ajaran/tanggal.',
            ];
        }

        $groupRows = $this->groupRows($caseRows);
        $groupCount = [];
        foreach ($groupRows as $group) {
            $groupCount[(int) ($group['id'] ?? 0)] = (int) ($group['jumlah_anggota'] ?? 0);
        }

        $followUpCount = [];
        foreach ($followUps as $row) {
            $idKasus = (int) ($row['id_kasus'] ?? 0);
            $followUpCount[$idKasus] = ($followUpCount[$idKasus] ?? 0) + 1;
        }

        $caseMap = [];
        $studentIds = [];
        $individualCount = 0;
        $groupCaseIds = [];

        foreach ($caseRows as $row) {
            $idKasus = (int) ($row['id'] ?? 0);
            $idGroup = (int) ($row['id_kelompok'] ?? 0);
            $kelas = trim((string) ($row['nama_kelas'] ?? '')) ?: '-';

            $caseMap[$idKasus] = $row + [
                'tahun_label' => $this->periodLabel($row),
                'nama_kelas' => $kelas,
            ];

            $idSiswa = (int) ($row['id_siswa'] ?? 0);
            if ($idSiswa > 0) $studentIds[$idSiswa] = true;
            if ($idGroup > 0) $groupCaseIds[$idGroup] = true;
            else $individualCount++;
        }

        try {
            $spreadsheet = new Spreadsheet();
            $summary = $spreadsheet->getActiveSheet();
            $this->writeSummarySheet(
                $summary,
                'Ringkasan',
                'Ringkasan Export Catatan Pelanggaran',
                [
                    'Tahun Ajaran' => $this->periodLabel((array) ($data['tahun_dipilih'] ?? [])),
                    'Cakupan Export' => $scope === 'year' ? 'Seluruh Tahun Ajaran terpilih' : 'Sesuai filter saat ini',
                    'Format Workbook' => $mode === 'lengkap' ? 'Lengkap / Audit' : 'Ringkas',
                    'Dibuat Pada' => Time::now(self::TZ)->format('Y-m-d H:i:s'),
                ],
                [
                    'Total Catatan Siswa' => count($caseRows),
                    'Siswa Unik' => count($studentIds),
                    'Catatan Individu' => $individualCount,
                    'Kejadian Kelompok' => count($groupCaseIds),
                    'Total Tindak Lanjut' => count($followUps),
                ],
                $this->filterSummary((array) ($data['filter'] ?? []))
            );

            if ($mode === 'ringkas') {
                $rekapRows = [];
                foreach ($caseRows as $i => $row) {
                    $idKasus = (int) ($row['id'] ?? 0);
                    $idGroup = (int) ($row['id_kelompok'] ?? 0);
                    $rekapRows[] = [
                        $i + 1,
                        $row['tanggal'] ?? '',
                        $row['nama_siswa'] ?? '',
                        trim((string) ($row['nama_kelas'] ?? '')) ?: '-',
                        $row['nama_pelanggaran'] ?? '',
                        $row['kategori'] ?? '',
                        $idGroup > 0 ? 'KELOMPOK' : 'INDIVIDU',
                        $row['keterangan'] ?? '',
                        $followUpCount[$idKasus] ?? 0,
                    ];
                }

                $followRows = [];
                foreach ($followUps as $i => $followUp) {
                    $idKasus = (int) ($followUp['id_kasus'] ?? 0);
                    $case = $caseMap[$idKasus] ?? [];
                    $followRows[] = [
                        $i + 1,
                        $followUp['tanggal'] ?? '',
                        $case['nama_siswa'] ?? '',
                        $case['nama_kelas'] ?? '-',
                        $case['nama_pelanggaran'] ?? '',
                        $followUp['tindak_lanjut'] ?? '',
                        $followUp['keterangan'] ?? '',
                        $followUp['nama_input'] ?? $followUp['username_input'] ?? '',
                    ];
                }

                $dataSheet = $spreadsheet->createSheet();
                $this->writeDataSheet($dataSheet, 'Rekap Pelanggaran', [
                    'No', 'Tanggal', 'Nama Siswa', 'Kelas', 'Pelanggaran',
                    'Kategori', 'Jenis Kejadian', 'Keterangan', 'Jumlah Tindak Lanjut',
                ], $rekapRows);

                $followSheet = $spreadsheet->createSheet();
                $this->writeDataSheet($followSheet, 'Tindak Lanjut', [
                    'No', 'Tanggal', 'Nama Siswa', 'Kelas', 'Pelanggaran',
                    'Tindak Lanjut', 'Keterangan', 'Dicatat Oleh',
                ], $followRows);
            } else {
                $dataRows = [];
                foreach ($caseRows as $i => $row) {
                    $idKasus = (int) ($row['id'] ?? 0);
                    $idGroup = (int) ($row['id_kelompok'] ?? 0);
                    $kelas = trim((string) ($row['nama_kelas'] ?? '')) ?: '-';
                    $dataRows[] = [
                        $i + 1, $row['tanggal'] ?? '', $this->periodLabel($row),
                        $row['nisn'] ?? '', $row['nama_siswa'] ?? '', $kelas,
                        $idGroup > 0 ? 'KELOMPOK' : 'INDIVIDU',
                        $idGroup > 0 ? ($groupCount[$idGroup] ?? 0) : 1,
                        $row['nama_pelanggaran'] ?? '', $row['kategori'] ?? '',
                        $row['keterangan'] ?? '', $followUpCount[$idKasus] ?? 0,
                        $idKasus, $idGroup ?: '', $row['created_at'] ?? '',
                    ];
                }

                $followRows = [];
                foreach ($followUps as $i => $followUp) {
                    $idKasus = (int) ($followUp['id_kasus'] ?? 0);
                    $case = $caseMap[$idKasus] ?? [];
                    $followRows[] = [
                        $i + 1, $followUp['tanggal'] ?? '', $case['nama_siswa'] ?? '',
                        $case['nama_kelas'] ?? '-', $case['nama_pelanggaran'] ?? '',
                        $followUp['tindak_lanjut'] ?? '', $followUp['keterangan'] ?? '',
                        $followUp['nama_input'] ?? $followUp['username_input'] ?? '',
                        $idKasus, (int) ($case['id_kelompok'] ?? 0) ?: '',
                        $case['tahun_label'] ?? $this->periodLabel($case),
                        $case['nisn'] ?? '', $case['tanggal'] ?? '', $case['kategori'] ?? '',
                    ];
                }

                $groupExportRows = [];
                foreach ($groupRows as $i => $group) {
                    $groupExportRows[] = [
                        $i + 1, $group['tanggal'] ?? '', $this->periodLabel($group),
                        $group['nama_pelanggaran'] ?? '', $group['kategori'] ?? '',
                        (int) ($group['jumlah_anggota'] ?? 0), $group['keterangan'] ?? '',
                        $group['nama_pencatat'] ?? $group['username_pencatat'] ?? '',
                        (int) ($group['id'] ?? 0),
                    ];
                }

                $dataSheet = $spreadsheet->createSheet();
                $this->writeDataSheet($dataSheet, 'Data Pelanggaran', [
                    'No', 'Tanggal', 'Tahun Ajaran', 'NISN', 'Nama Siswa', 'Kelas',
                    'Mode', 'Jumlah Anggota', 'Pelanggaran', 'Kategori', 'Keterangan',
                    'Jumlah Tindak Lanjut', 'ID Catatan', 'ID Kelompok', 'Dibuat Pada',
                ], $dataRows);

                $followSheet = $spreadsheet->createSheet();
                $this->writeDataSheet($followSheet, 'Riwayat Tindak Lanjut', [
                    'No', 'Tanggal Tindak Lanjut', 'Nama Siswa', 'Kelas', 'Pelanggaran',
                    'Tindak Lanjut', 'Keterangan', 'Dicatat Oleh', 'ID Catatan',
                    'ID Kelompok', 'Tahun Ajaran', 'NISN', 'Tanggal Pelanggaran', 'Kategori',
                ], $followRows);

                $groupSheet = $spreadsheet->createSheet();
                $this->writeDataSheet($groupSheet, 'Kejadian Kelompok', [
                    'No', 'Tanggal', 'Tahun Ajaran', 'Pelanggaran', 'Kategori',
                    'Jumlah Anggota', 'Keterangan', 'Dicatat Oleh', 'ID Kelompok',
                ], $groupExportRows);
            }

            $spreadsheet->setActiveSheetIndex(0);
            $result = $this->saveSpreadsheet(
                $spreadsheet,
                'catatan_pelanggaran_' . $mode . '_' . date('Ymd_His') . '.xlsx'
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
            $this->log(
                $userId,
                'BK Pelanggaran',
                'Export Catatan Pelanggaran mode ' . strtoupper($mode)
                . ' dengan cakupan ' . strtoupper($scope)
            );
        }

        return $result;
    }
    public function prestasi(array $data, int $userId): array
    {
        $prestasiRows = is_array($data['rows'] ?? null) ? $data['rows'] : [];

        $headers = [
            'No',
            'Tahun Ajaran',
            'NISN',
            'Nama Siswa',
            'Kelas',
            'Tanggal',
            'Prestasi',
            'Tingkat',
            'Penyelenggara',
            'Keterangan',
        ];
        $rows = [];
        $no = 1;

        foreach ($prestasiRows as $row) {
            $rows[] = [
                $no++,
                $this->periodLabel($row),
                $row['nisn'] ?? '',
                $row['nama_siswa'] ?? '',
                trim((string) ($row['nama_kelas'] ?? '')) ?: '-',
                $row['tanggal'] ?? '',
                $row['nama_prestasi'] ?? '',
                $row['tingkat'] ?? '',
                $row['penyelenggara'] ?? '',
                $row['keterangan'] ?? '',
            ];
        }

        $result = $this->write('Prestasi', $headers, $rows, 'prestasi_' . date('Ymd_His') . '.xlsx');

        if ($result['success']) {
            $this->log($userId, 'Prestasi', 'Export Prestasi berdasarkan Tahun Ajaran terpilih');
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

    private function filterSummary(array $filter): string
    {
        $parts = [];
        if (! empty($filter['search'])) $parts[] = 'Pencarian: ' . $filter['search'];
        if (! empty($filter['kategori'])) $parts[] = 'Kategori: ' . $filter['kategori'];
        if (! empty($filter['tanggal_mulai'])) $parts[] = 'Dari: ' . $filter['tanggal_mulai'];
        if (! empty($filter['tanggal_selesai'])) $parts[] = 'Sampai: ' . $filter['tanggal_selesai'];
        return $parts === [] ? 'Tidak ada filter tambahan.' : implode(' | ', $parts);
    }

    private function groupRows(array $caseRows): array
    {
        $ids = array_values(array_unique(array_filter(array_map(
            static fn (array $row): int => (int) ($row['id_kelompok'] ?? 0),
            $caseRows
        ))));

        if ($ids === []) {
            return [];
        }

        return db_connect()->table('catatan_kasus_kelompok g')
            ->select([
                'g.id', 'g.id_tahun', 'g.tanggal', 'g.keterangan',
                'rp.nama_pelanggaran', 'rp.kategori',
                'ta.nama_tahun', 'ta.semester',
                'u.username AS username_pencatat',
            ])
            ->select('(SELECT COUNT(*) FROM catatan_kasus ck WHERE ck.id_kelompok = g.id) AS jumlah_anggota', false)
            ->select('COALESCE(p.nama, gr.nama, u.username) AS nama_pencatat', false)
            ->join('ref_pelanggaran rp', 'rp.id = g.id_pelanggaran')
            ->join('tahun_ajaran ta', 'ta.id = g.id_tahun')
            ->join('users u', 'u.id = g.created_by', 'left')
            ->join('pegawai p', 'p.id = u.id_pegawai', 'left')
            ->join('guru gr', 'gr.id = u.id_guru', 'left')
            ->whereIn('g.id', $ids)
            ->orderBy('g.tanggal', 'ASC')
            ->orderBy('g.id', 'ASC')
            ->get()
            ->getResultArray();
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
            $this->writeDataSheet($sheet, substr($title, 0, 31), $headers, $rows);

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

    private function writeSummarySheet(
        Worksheet $sheet,
        string $title,
        string $heading,
        array $meta,
        array $metrics,
        string $filterText
    ): void {
        $sheet->setTitle($title);
        $sheet->mergeCells('A1:F1');
        $sheet->setCellValueExplicit('A1', $heading, DataType::TYPE_STRING);
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 14],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '696CFF']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $row = 3;
        foreach ($meta as $label => $value) {
            $sheet->setCellValueExplicit('A' . $row, (string) $label, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('B' . $row, (string) $value, DataType::TYPE_STRING);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;
        }

        $sheet->mergeCells('D3:F3');
        $sheet->setCellValueExplicit('D3', 'Filter Digunakan', DataType::TYPE_STRING);
        $sheet->getStyle('D3:F3')->getFont()->setBold(true);
        $sheet->mergeCells('D4:F6');
        $sheet->setCellValueExplicit('D4', $filterText, DataType::TYPE_STRING);
        $sheet->getStyle('D4:F6')->getAlignment()->setVertical('top')->setWrapText(true);

        $metricRow = max($row + 1, 9);
        $sheet->mergeCells('A' . $metricRow . ':B' . $metricRow);
        $sheet->setCellValueExplicit('A' . $metricRow, 'Ringkasan Angka', DataType::TYPE_STRING);
        $sheet->getStyle('A' . $metricRow . ':B' . $metricRow)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '566A7F']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F2F4F7']],
        ]);
        $metricRow++;
        foreach ($metrics as $label => $value) {
            $sheet->setCellValueExplicit('A' . $metricRow, (string) $label, DataType::TYPE_STRING);
            $sheet->setCellValue('B' . $metricRow, (int) $value);
            $metricRow++;
        }

        $sheet->getColumnDimension('A')->setWidth(24);
        $sheet->getColumnDimension('B')->setWidth(28);
        $sheet->getColumnDimension('C')->setWidth(4);
        $sheet->getColumnDimension('D')->setWidth(24);
        $sheet->getColumnDimension('E')->setWidth(24);
        $sheet->getColumnDimension('F')->setWidth(24);
    }

    private function writeDataSheet(Worksheet $sheet, string $title, array $headers, array $rows): void
    {
        $sheet->setTitle(substr($title, 0, 31));
        foreach ($headers as $i => $header) {
            $sheet->setCellValueExplicit(
                Coordinate::stringFromColumnIndex($i + 1) . '1',
                (string) $header,
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
        $sheet->getStyle("A1:{$last}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '566A7F']],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);
        if ($rowNo > 2) {
            $sheet->getStyle("A2:{$last}" . ($rowNo - 1))
                ->getAlignment()
                ->setVertical(Alignment::VERTICAL_TOP)
                ->setWrapText(true);
        }
        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:{$last}1");

        foreach ($headers as $i => $header) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i + 1))
                ->setWidth($this->columnWidth((string) $header));
        }
    }

    private function columnWidth(string $header): float
    {
        $h = mb_strtolower($header);
        if ($h === 'no') return 6;
        if (str_contains($h, 'tanggal')) return 16;
        if (str_contains($h, 'nisn')) return 16;
        if (str_contains($h, 'nama siswa')) return 30;
        if ($h === 'kelas') return 14;
        if (str_contains($h, 'tahun ajaran')) return 22;
        if (str_contains($h, 'keterangan')) return 38;
        if (str_contains($h, 'pelanggaran')) return 30;
        if (str_contains($h, 'tindak lanjut')) return 28;
        if (str_contains($h, 'dicatat oleh')) return 26;
        if (str_starts_with($h, 'id ')) return 14;
        return 18;
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
