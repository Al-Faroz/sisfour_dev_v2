<?php

namespace App\Controllers;

use App\Services\PegawaiService;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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
                'data' => $this->pegawaiService->getList($filter),
            ]);
        }

        return $this->response->setBody(
            $this->renderWithLayout('master/pegawai', [
                'title' => 'Master Pegawai',
                'canManage' => $this->canManage(),
                'filters' => $filter,
                'statusOptions' => PegawaiService::STATUS_KEPEGAWAIAN,
                'extraJs' => ['assets/js/master/pegawai.js'],
            ])
        );
    }

    public function recycle()
    {
        if ($this->isJsonRequest()) {
            return $this->response->setJSON([
                'status' => 'success',
                'data' => $this->pegawaiService->getList($this->filters(), true),
            ]);
        }

        return $this->response->setBody(
            $this->renderWithLayout('master/pegawai_recycle', [
                'title' => 'Recycle Bin Pegawai',
                'filters' => $this->filters(),
                'extraJs' => ['assets/js/master/pegawai-recycle.js'],
            ])
        );
    }

    public function create()
    {
        return $this->respondResult(
            $this->pegawaiService->create(
                $this->request->getPost(),
                $this->request->getFile('foto')
            ),
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

    public function uploadFoto($id)
    {
        $foto = $this->request->getFile('foto');

        if ($foto === null) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 'error',
                'message' => 'File foto wajib dipilih.',
            ]);
        }

        return $this->respondResult(
            $this->pegawaiService->uploadFoto((int) $id, $foto)
        );
    }

    public function delete($id)
    {
        return $this->respondResult($this->pegawaiService->delete((int) $id));
    }

    public function restore($id)
    {
        return $this->respondResult($this->pegawaiService->restore((int) $id));
    }

    public function forceDelete($id)
    {
        return $this->respondResult($this->pegawaiService->forceDelete((int) $id));
    }

    public function import()
    {
        $file = $this->request->getFile('file');

        if ($file === null) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 'error',
                'message' => 'File Excel wajib dipilih.',
            ]);
        }

        return $this->respondResult($this->pegawaiService->importExcel($file));
    }

    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Pegawai');

        $sheet->fromArray([
            ['NIK', 'NIP', 'NAMA LENGKAP & GELAR', 'JENIS KELAMIN (L/P)', 'STATUS KEPEGAWAIAN'],
            ['3517012345670002', '198501012011012003', 'Siti Aminah, S.E.', 'P', 'PNS'],
            ['3517094803900003', '', 'Contoh Pegawai Non-ASN', 'P', 'PTT'],
        ], null, 'A1');

        $sheet->getStyle('A1:E1')->getFont()->setBold(true);
        foreach (['A', 'B'] as $column) {
            $sheet->getStyle($column . ':' . $column)->getNumberFormat()->setFormatCode('@');
        }
        foreach (range('A', 'E') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return $this->downloadSpreadsheet($spreadsheet, 'template_import_pegawai.xlsx', 'sisfour_pegawai_template_');
    }

    public function export()
    {
        $data = $this->pegawaiService->getList($this->filters());
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Pegawai');

        $headers = [
            'NIK', 'NIP', 'NAMA LENGKAP & GELAR', 'JK', 'TEMPAT LAHIR',
            'TANGGAL LAHIR', 'AGAMA', 'ALAMAT', 'NO. TELEPON', 'EMAIL',
            'STATUS KEPEGAWAIAN', 'NUPTK', 'USERNAME LOGIN',
        ];

        $sheet->setCellValue('A1', 'DATA PEGAWAI SISISFOUR');
        $sheet->mergeCells('A1:M1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->fromArray([$headers], null, 'A3');
        $sheet->getStyle('A3:M3')->getFont()->setBold(true);

        $row = 4;
        foreach ($data as $pegawai) {
            $sheet->fromArray([[
                $pegawai['nik'],
                $pegawai['nip'],
                $pegawai['nama'],
                $pegawai['jenis_kelamin'],
                $pegawai['tempat_lahir'],
                $pegawai['tanggal_lahir'],
                $pegawai['agama'],
                $pegawai['alamat'],
                $pegawai['no_telepon'],
                $pegawai['email'],
                $pegawai['status_kepegawaian'],
                $pegawai['nuptk'],
                $pegawai['username'],
            ]], null, 'A' . $row);

            foreach (['A', 'B', 'L', 'M'] as $column) {
                $sheet->setCellValueExplicit(
                    $column . $row,
                    (string) ($sheet->getCell($column . $row)->getValue() ?? ''),
                    DataType::TYPE_STRING
                );
            }
            $row++;
        }

        foreach (range('A', 'M') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return $this->downloadSpreadsheet(
            $spreadsheet,
            'data_pegawai_' . date('Ymd_His') . '.xlsx',
            'sisfour_pegawai_export_'
        );
    }

    protected function filters(): array
    {
        return [
            'nama' => trim((string) $this->request->getGet('nama')),
            'nik' => trim((string) $this->request->getGet('nik')),
            'nip' => trim((string) $this->request->getGet('nip')),
            'jenis_kelamin' => trim((string) $this->request->getGet('jenis_kelamin')),
            'status_kepegawaian' => trim((string) $this->request->getGet('status_kepegawaian')),
        ];
    }

    protected function canManage(): bool
    {
        return $this->authService->resolveScope(
            'master_pegawai.manage',
            (int) session()->get('user_id')
        ) === 'SEMUA';
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
                'status' => $success ? 'success' : 'error',
                'message' => $result['message'] ?? ($success ? 'Berhasil.' : 'Gagal.'),
                'data' => $result,
            ]);
    }

    private function downloadSpreadsheet(Spreadsheet $spreadsheet, string $filename, string $prefix)
    {
        $tempFile = tempnam(sys_get_temp_dir(), $prefix);
        (new Xlsx($spreadsheet))->save($tempFile);

        register_shutdown_function(static function () use ($tempFile): void {
            if (is_file($tempFile)) {
                @unlink($tempFile);
            }
        });

        return $this->response->download($tempFile, null)->setFileName($filename);
    }
}
