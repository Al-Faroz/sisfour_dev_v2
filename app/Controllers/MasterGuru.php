<?php

namespace App\Controllers;

use App\Services\GuruService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * MasterGuru
 *
 * Controller Master Guru.
 *
 * Routes:
 * GET    /master/guru
 * GET    /master/guru/json
 * GET    /master/guru/template
 * POST   /master/guru/create
 * POST   /master/guru/import
 * GET    /master/guru/export
 * PUT    /master/guru/update/{id}
 * POST   /master/guru/upload-foto/{id}
 * DELETE /master/guru/delete/{id}
 * GET    /master/guru/recycle
 * GET    /master/guru/recycle/json
 * POST   /master/guru/restore/{id}
 * DELETE /master/guru/force-delete/{id}
 */
class MasterGuru extends BaseController
{
    protected GuruService $guruService;

    public function __construct()
    {
        $this->guruService = new GuruService();
    }

    public function index()
    {
        $filter = $this->filters();
        $isJson = $this->isJsonRequest();

        if ($isJson) {
            return $this->response->setJSON([
                'status' => 'success',
                'data'   => $this->guruService->getList($filter),
            ]);
        }

        return $this->response->setBody(
            $this->renderWithLayout('master/guru', [
                'title'     => 'Master Guru',
                'canManage' => $this->canManage(),
                'filters'   => $filter,
                'extraJs'   => ['assets/js/master/guru.js'],
            ])
        );
    }

    public function recycle()
    {
        if ($this->isJsonRequest()) {
            return $this->response->setJSON([
                'status' => 'success',
                'data'   => $this->guruService->getList($this->filters(), true),
            ]);
        }

        return $this->response->setBody(
            $this->renderWithLayout('master/guru_recycle', [
                'title'   => 'Recycle Bin Guru',
                'filters' => $this->filters(),
                'extraJs' => ['assets/js/master/guru-recycle.js'],
            ])
        );
    }

    public function create()
    {
        $foto = $this->request->getFile('foto');

        $result = $this->guruService->create(
            $this->request->getPost(),
            $foto
        );

        return $this->respondResult($result, 201);
    }

    public function update($id)
    {
        $id = (int) $id;

        $payload = $this->request->getRawInput();

        if ($payload === []) {
            $json = $this->request->getJSON(true);
            $payload = is_array($json) ? $json : [];
        }

        $result = $this->guruService->update($id, $payload);

        return $this->respondResult($result);
    }

    public function uploadFoto($id)
    {
        $foto = $this->request->getFile('foto');

        if ($foto === null) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'File foto wajib dipilih.',
                ]);
        }

        return $this->respondResult(
            $this->guruService->uploadFoto((int) $id, $foto)
        );
    }

    public function delete($id)
    {
        return $this->respondResult(
            $this->guruService->delete((int) $id)
        );
    }

    public function restore($id)
    {
        return $this->respondResult(
            $this->guruService->restore((int) $id)
        );
    }

    public function forceDelete($id)
    {
        return $this->respondResult(
            $this->guruService->forceDelete((int) $id)
        );
    }

    public function import()
    {
        $file = $this->request->getFile('file');

        if ($file === null) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'File Excel wajib dipilih.',
                ]);
        }

        return $this->respondResult(
            $this->guruService->importExcel($file)
        );
    }

    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Guru');

        $sheet->fromArray([
            ['NIP', 'NAMA LENGKAP & GELAR', 'JENIS KELAMIN (L/P)'],
            ['198501012011011001', 'Ahmad Fauzi, S.Pd.I', 'L'],
        ], null, 'A1');

        foreach (['A', 'B', 'C'] as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $sheet->getStyle('A1:C1')->getFont()->setBold(true);
        $sheet->getStyle('A:A')->getNumberFormat()->setFormatCode('@');

        $filename = 'template_import_guru.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'sisfour_guru_');

        (new Xlsx($spreadsheet))->save($tempFile);

        return $this->response
            ->download($tempFile, null)
            ->setFileName($filename);
    }

    public function export()
    {
        $data = $this->guruService->getList($this->filters());

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Guru');

        $sheet->setCellValue('A1', 'DATA GURU SISISFOUR');
        $sheet->mergeCells('A1:J1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $sheet->fromArray([
            [
                'NIP',
                'NAMA',
                'JK',
                'TEMPAT LAHIR',
                'TANGGAL LAHIR',
                'ALAMAT',
                'NO. TELEPON',
                'EMAIL',
                'STATUS KEPEGAWAIAN',
                'FOTO',
            ],
        ], null, 'A3');

        $row = 4;
        foreach ($data as $guru) {
            $sheet->fromArray([[
                $guru['nip'],
                $guru['nama'],
                $guru['jenis_kelamin'],
                $guru['tempat_lahir'],
                $guru['tanggal_lahir'],
                $guru['alamat'],
                $guru['no_telepon'],
                $guru['email'],
                $guru['status_kepegawaian'],
                $guru['foto'],
            ]], null, 'A' . $row);

            $sheet->setCellValueExplicit(
                'A' . $row,
                (string) $guru['nip'],
                \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
            );

            $row++;
        }

        foreach (range('A', 'J') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $sheet->getStyle('A3:J3')->getFont()->setBold(true);

        $filename = 'data_guru_' . date('Ymd_His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'sisfour_export_guru_');

        (new Xlsx($spreadsheet))->save($tempFile);

        return $this->response
            ->download($tempFile, null)
            ->setFileName($filename);
    }

    protected function filters(): array
    {
        return [
            'nama'                => trim((string) $this->request->getGet('nama')),
            'nip'                 => trim((string) $this->request->getGet('nip')),
            'jenis_kelamin'       => trim((string) $this->request->getGet('jenis_kelamin')),
            'status_kepegawaian'  => trim((string) $this->request->getGet('status_kepegawaian')),
        ];
    }

    protected function canManage(): bool
    {
        $scope = $this->authService->resolveScope(
            'master_guru.manage',
            (int) session()->get('user_id')
        );

        return $scope !== 'TIDAK_ADA';
    }

    protected function isJsonRequest(): bool
    {
        $path = trim($this->request->getUri()->getPath(), '/');

        return str_ends_with($path, '/json')
            || $this->request->getGet('format') === 'json'
            || $this->request->isAJAX();
    }

    protected function respondResult(array $result, int $successCode = 200)
    {
        $success = (bool) ($result['success'] ?? false);

        return $this->response
            ->setStatusCode($success ? $successCode : 422)
            ->setJSON([
                'status'  => $success ? 'success' : 'error',
                'message' => $result['message'] ?? ($success ? 'Berhasil.' : 'Gagal.'),
                'data'    => $result,
            ]);
    }
}
