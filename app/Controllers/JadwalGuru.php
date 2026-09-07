<?php

namespace App\Controllers;

use App\Services\JadwalGuruService;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class JadwalGuru extends BaseController
{
    protected JadwalGuruService $jadwalService;

    public function __construct()
    {
        $this->jadwalService = new JadwalGuruService();
    }

    public function index()
    {
        $userId = (int) session()->get('user_id');
        $filter = $this->filters();

        if ($this->isJsonRequest()) {
            return $this->response->setJSON([
                'status' => 'success',
                'data' => $this->jadwalService->getList(
                    $filter,
                    $userId
                ),
            ]);
        }

        $options = $this->jadwalService->getOptions($userId);

        return $this->response->setBody(
            $this->renderWithLayout('master/jadwal_guru', [
                'title' => 'Master Jadwal Guru',
                'options' => $options,
                'canManage' => $this->jadwalService->canManage($userId),
                'extraJs' => [
                    'assets/js/master/jadwal-guru.js',
                ],
            ])
        );
    }

    public function options()
    {
        $userId = (int) session()->get('user_id');
        $idGuru = (int) $this->request->getGet('id_guru');

        return $this->respondResult(
            $this->jadwalService->getOptions(
                $userId,
                $idGuru > 0 ? $idGuru : null
            )
        );
    }

    public function import()
    {
        $file = $this->request->getFile('file');

        if ($file === null) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'status' => 'error',
                    'message' => 'File Excel wajib dipilih.',
                ]);
        }

        return $this->respondResult(
            $this->jadwalService->importJadwal(
                $file,
                (int) $this->request->getPost('id_tahun')
            )
        );
    }

    public function delete($id)
    {
        return $this->respondResult(
            $this->jadwalService->delete(
                (int) $id,
                (int) session()->get('user_id')
            )
        );
    }

    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Jadwal Guru');

        $sheet->fromArray([
            [
                'NIP_GURU',
                'NAMA_KELAS',
                'KODE_MAPEL',
                'HARI',
                'JAM_MULAI',
                'JAM_SELESAI',
                'SESI',
            ],
            [
                '196808212003122001',
                '7-A',
                'MTK',
                'Senin',
                '07:30',
                '09:00',
                'Sesi Awal',
            ],
        ], null, 'A1');

        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        $sheet->getStyle('A:A')->getNumberFormat()->setFormatCode('@');

        foreach (range('A', 'G') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $tempFile = tempnam(
            sys_get_temp_dir(),
            'sisfour_template_jadwal_'
        );

        (new Xlsx($spreadsheet))->save($tempFile);

        return $this->response
            ->download($tempFile, null)
            ->setFileName('template_import_jadwal_guru.xlsx');
    }

    public function export()
    {
        $userId = (int) session()->get('user_id');

        if (!$this->jadwalService->canManage($userId)) {
            return $this->response
                ->setStatusCode(403)
                ->setBody('Akses ditolak.');
        }

        $data = $this->jadwalService->getList(
            $this->filters(),
            $userId
        );

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Jadwal Guru');

        $sheet->setCellValue('A1', 'DATA JADWAL GURU SISISFOUR');
        $sheet->mergeCells('A1:M1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $sheet->fromArray([[
            'NIP',
            'NAMA GURU',
            'KELAS',
            'KODE MAPEL',
            'MATA PELAJARAN',
            'HARI',
            'JAM MULAI',
            'JAM SELESAI',
            'SESI',
            'STATUS',
            'TAHUN AJARAN',
            'SEMESTER',
            'TAHUN AKTIF',
        ]], null, 'A3');

        $row = 4;

        foreach ($data as $jadwal) {
            $sheet->fromArray([[
                $jadwal['nip'],
                $jadwal['nama_guru'],
                $jadwal['nama_kelas'],
                $jadwal['kode_mapel'],
                $jadwal['nama_mapel'],
                $jadwal['hari'],
                substr((string) $jadwal['jam_mulai'], 0, 5),
                substr((string) $jadwal['jam_selesai'], 0, 5),
                $jadwal['sesi'],
                $jadwal['status_jadwal'],
                $jadwal['nama_tahun'],
                $jadwal['semester'],
                (int) $jadwal['tahun_aktif'] === 1 ? 'Ya' : 'Tidak',
            ]], null, 'A' . $row);

            $sheet->setCellValueExplicit(
                'A' . $row,
                (string) $jadwal['nip'],
                DataType::TYPE_STRING
            );

            $row++;
        }

        $sheet->getStyle('A3:M3')->getFont()->setBold(true);

        foreach (range('A', 'M') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $tempFile = tempnam(
            sys_get_temp_dir(),
            'sisfour_export_jadwal_'
        );

        (new Xlsx($spreadsheet))->save($tempFile);

        return $this->response
            ->download($tempFile, null)
            ->setFileName(
                'jadwal_guru_' . date('Ymd_His') . '.xlsx'
            );
    }

    protected function filters(): array
    {
        return [
            'id_guru' => (int) $this->request->getGet('id_guru'),
            'id_kelas' => (int) $this->request->getGet('id_kelas'),
            'id_tahun' => (int) $this->request->getGet('id_tahun'),
            'hari' => trim((string) $this->request->getGet('hari')),
            'status_jadwal' => trim(
                (string) $this->request->getGet('status_jadwal')
            ),
        ];
    }

    protected function isJsonRequest(): bool
    {
        $path = trim($this->request->getUri()->getPath(), '/');

        return str_ends_with($path, '/json')
            || $this->request->isAJAX()
            || $this->request->getGet('format') === 'json';
    }

    protected function respondResult(
        array $result,
        int $successCode = 200
    ) {
        $success = (bool) ($result['success'] ?? false);

        return $this->response
            ->setStatusCode($success ? $successCode : 422)
            ->setJSON([
                'status' => $success ? 'success' : 'error',
                'message' => $result['message']
                    ?? ($success ? 'Berhasil.' : 'Gagal.'),
                'data' => $result,
            ]);
    }
}
