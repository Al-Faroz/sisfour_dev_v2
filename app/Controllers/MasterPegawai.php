<?php

namespace App\Controllers;

use App\Services\PegawaiService;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * MasterPegawai
 *
 * Controller Master Pegawai.
 */
class MasterPegawai extends BaseController
{
    protected PegawaiService $pegawaiService;

    public function __construct()
    {
        $this->pegawaiService = new PegawaiService();
    }

    public function index()
    {
        $filter = $this->filters();

        if ($this->isJsonRequest()) {
            return $this->response->setJSON([
                'status' => 'success',
                'data'   => $this->pegawaiService->getList($filter),
            ]);
        }

        return $this->response->setBody(
            $this->renderWithLayout('master/pegawai', [
                'title'     => 'Master Pegawai',
                'canManage' => $this->canManage(),
                'filters'   => $filter,
                'extraJs'   => ['assets/js/master/pegawai.js'],
            ])
        );
    }

    public function recycle()
    {
        if ($this->isJsonRequest()) {
            return $this->response->setJSON([
                'status' => 'success',
                'data'   => $this->pegawaiService->getList(
                    $this->filters(),
                    true
                ),
            ]);
        }

        return $this->response->setBody(
            $this->renderWithLayout('master/pegawai_recycle', [
                'title'   => 'Recycle Bin Pegawai',
                'filters' => $this->filters(),
                'extraJs' => ['assets/js/master/pegawai-recycle.js'],
            ])
        );
    }

    public function create()
    {
        return $this->respondResult(
            $this->pegawaiService->create($this->request->getPost()),
            201
        );
    }

    public function update($id)
    {
        $payload = $this->request->getRawInput();

        if ($payload === []) {
            $json = $this->request->getJSON(true);
            $payload = is_array($json) ? $json : [];
        }

        return $this->respondResult(
            $this->pegawaiService->update((int) $id, $payload)
        );
    }

    public function delete($id)
    {
        return $this->respondResult(
            $this->pegawaiService->delete((int) $id)
        );
    }

    public function restore($id)
    {
        return $this->respondResult(
            $this->pegawaiService->restore((int) $id)
        );
    }

    public function forceDelete($id)
    {
        return $this->respondResult(
            $this->pegawaiService->forceDelete((int) $id)
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
            $this->pegawaiService->importExcel($file)
        );
    }

    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Pegawai');

        $sheet->fromArray([
            [
                'NIP',
                'NAMA LENGKAP',
                'JENIS KELAMIN (L/P)',
                'JABATAN',
            ],
            [
                '198501012011012003',
                'Siti Aminah, S.E.',
                'P',
                'Tenaga Administrasi',
            ],
        ], null, 'A1');

        $sheet->getStyle('A1:D1')->getFont()->setBold(true);
        $sheet->getStyle('A:A')
            ->getNumberFormat()
            ->setFormatCode('@');

        foreach (['A', 'B', 'C', 'D'] as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $tempFile = tempnam(
            sys_get_temp_dir(),
            'sisfour_template_pegawai_'
        );

        (new Xlsx($spreadsheet))->save($tempFile);

        return $this->response
            ->download($tempFile, null)
            ->setFileName('template_import_pegawai.xlsx');
    }

    public function export()
    {
        $data = $this->pegawaiService->getList($this->filters());

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Pegawai');

        $sheet->setCellValue('A1', 'DATA PEGAWAI SISISFOUR');
        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $sheet->fromArray([[
            'NIP',
            'NAMA',
            'JK',
            'TEMPAT LAHIR',
            'TANGGAL LAHIR',
            'ALAMAT',
            'NO. TELEPON',
            'EMAIL',
            'JABATAN',
        ]], null, 'A3');

        $row = 4;

        foreach ($data as $pegawai) {
            $sheet->fromArray([[
                $pegawai['nip'],
                $pegawai['nama'],
                $pegawai['jenis_kelamin'],
                $pegawai['tempat_lahir'],
                $pegawai['tanggal_lahir'],
                $pegawai['alamat'],
                $pegawai['no_telepon'],
                $pegawai['email'],
                $pegawai['jabatan'],
            ]], null, 'A' . $row);

            $sheet->setCellValueExplicit(
                'A' . $row,
                (string) $pegawai['nip'],
                DataType::TYPE_STRING
            );

            $row++;
        }

        $sheet->getStyle('A3:I3')->getFont()->setBold(true);

        foreach (range('A', 'I') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $tempFile = tempnam(
            sys_get_temp_dir(),
            'sisfour_export_pegawai_'
        );

        (new Xlsx($spreadsheet))->save($tempFile);

        return $this->response
            ->download($tempFile, null)
            ->setFileName(
                'data_pegawai_' . date('Ymd_His') . '.xlsx'
            );
    }

    protected function filters(): array
    {
        return [
            'nama'          => trim((string) $this->request->getGet('nama')),
            'nip'           => trim((string) $this->request->getGet('nip')),
            'jenis_kelamin' => trim(
                (string) $this->request->getGet('jenis_kelamin')
            ),
            'jabatan'       => trim(
                (string) $this->request->getGet('jabatan')
            ),
        ];
    }

    protected function canManage(): bool
    {
        $scope = $this->authService->resolveScope(
            'master_pegawai.manage',
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

    protected function respondResult(
        array $result,
        int $successCode = 200
    ) {
        $success = (bool) ($result['success'] ?? false);

        return $this->response
            ->setStatusCode($success ? $successCode : 422)
            ->setJSON([
                'status'  => $success ? 'success' : 'error',
                'message' => $result['message']
                    ?? ($success ? 'Berhasil.' : 'Gagal.'),
                'data'    => $result,
            ]);
    }
}
