<?php

namespace App\Controllers;

use App\Services\JadwalGuruService;
use App\Services\MasterPaginationService;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class JadwalGuru extends BaseController
{
    protected JadwalGuruService $jadwalService;
    protected MasterPaginationService $paginationService;

    public function __construct()
    {
        $this->jadwalService = new JadwalGuruService();
        $this->paginationService = new MasterPaginationService();
    }

    public function index()
    {
        $userId = $this->currentActorUserId();
        $filter = $this->filters();

        if ($this->isJsonRequest()) {
            $paging = $this->paginationService->normalizePaging(
                $this->request->getGet()
            );

            return $this->response->setJSON([
                'status' => 'success',
                'data' => $this->paginationService->pageJadwal(
                    $filter,
                    $userId,
                    $paging['limit'],
                    $paging['offset']
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
        $userId = $this->currentActorUserId();
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
                (int) $this->request->getPost('id_tahun'),
                $this->currentActorUserId()
            )
        );
    }

    public function delete($id)
    {
        return $this->respondResult(
            $this->jadwalService->delete(
                (int) $id,
                $this->currentActorUserId()
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
                'IDENTITAS_GURU',
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
            [
                '3517012345670001',
                '7-B',
                'BIN',
                'Selasa',
                '07:30',
                '08:50',
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
        $userId = $this->currentActorUserId();

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
            'IDENTITAS GURU',
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
                $this->guruIdentifier($jadwal),
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
                $this->guruIdentifier($jadwal),
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

    private function guruIdentifier(array $jadwal): string
    {
        $nip = preg_replace(
            '/\D+/',
            '',
            (string) ($jadwal['nip'] ?? '')
        ) ?? '';

        if ($nip !== '') {
            return $nip;
        }

        return preg_replace(
            '/\D+/',
            '',
            (string) ($jadwal['nik'] ?? '')
        ) ?? '';
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
        $code = (string) ($result['code'] ?? '');

        $errorCode = match ($code) {
            'FORBIDDEN' => 403,
            'NOT_FOUND' => 404,
            'SCHEDULE_CONFLICT' => 409,
            default => 422,
        };

        return $this->response
            ->setStatusCode(
                $success
                    ? $successCode
                    : $errorCode
            )
            ->setJSON([
                'status' => $success ? 'success' : 'error',
                'message' => $result['message']
                    ?? ($success ? 'Berhasil.' : 'Gagal.'),
                'data' => $result,
            ]);
    }
}
