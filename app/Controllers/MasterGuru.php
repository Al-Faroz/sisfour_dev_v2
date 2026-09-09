<?php

namespace App\Controllers;

use App\Services\GuruService;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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

        if ($this->isJsonRequest()) {
            return $this->response->setJSON([
                'status' => 'success',
                'data' => $this->guruService->getList($filter),
            ]);
        }

        return $this->response->setBody(
            $this->renderWithLayout('master/guru', [
                'title' => 'Master Guru',
                'canManage' => $this->canManage(),
                'filters' => $filter,
                'statusOptions' => GuruService::STATUS_KEPEGAWAIAN,
                'extraJs' => ['assets/js/master/guru.js'],
            ])
        );
    }

    public function recycle()
    {
        if ($this->isJsonRequest()) {
            return $this->response->setJSON([
                'status' => 'success',
                'data' => $this->guruService->getList($this->filters(), true),
            ]);
        }

        return $this->response->setBody(
            $this->renderWithLayout('master/guru_recycle', [
                'title' => 'Recycle Bin Guru',
                'filters' => $this->filters(),
                'extraJs' => ['assets/js/master/guru-recycle.js'],
            ])
        );
    }

    public function create()
    {
        return $this->respondResult(
            $this->guruService->create(
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
            $this->guruService->update((int) $id, $payload)
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
            $this->guruService->uploadFoto((int) $id, $foto)
        );
    }

    public function delete($id)
    {
        return $this->respondResult($this->guruService->delete((int) $id));
    }

    public function restore($id)
    {
        return $this->respondResult($this->guruService->restore((int) $id));
    }

    public function forceDelete($id)
    {
        return $this->respondResult($this->guruService->forceDelete((int) $id));
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

        return $this->respondResult($this->guruService->importExcel($file));
    }

    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Guru');

        $sheet->fromArray([
            ['NIK', 'NIP', 'NAMA LENGKAP & GELAR', 'JENIS KELAMIN (L/P)', 'STATUS KEPEGAWAIAN'],
            ['3517012345670001', '198005152005011004', 'Dr. Ahmad Fauzi, S.Pd., M.Kom.', 'L', 'PNS'],
            ['3517094803900002', '', 'Zufa Al Husna', 'P', 'GTT'],
        ], null, 'A1');

        $sheet->getStyle('A1:E1')->getFont()->setBold(true);
        foreach (['A', 'B'] as $column) {
            $sheet->getStyle($column . ':' . $column)->getNumberFormat()->setFormatCode('@');
        }
        foreach (range('A', 'E') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return $this->downloadSpreadsheet($spreadsheet, 'template_import_guru.xlsx', 'sisfour_guru_template_');
    }

    public function export()
    {
        $data = $this->guruService->getList($this->filters());
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Guru');

        $headers = [
            'NIK', 'NIP', 'NAMA LENGKAP & GELAR', 'JK', 'TEMPAT LAHIR',
            'TANGGAL LAHIR', 'AGAMA', 'ALAMAT', 'NO. TELEPON', 'EMAIL',
            'STATUS KEPEGAWAIAN', 'NUPTK', 'USERNAME LOGIN',
        ];

        $sheet->setCellValue('A1', 'DATA GURU SISISFOUR');
        $sheet->mergeCells('A1:M1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->fromArray([$headers], null, 'A3');
        $sheet->getStyle('A3:M3')->getFont()->setBold(true);

        $row = 4;
        foreach ($data as $guru) {
            $sheet->fromArray([[
                $guru['nik'],
                $guru['nip'],
                $guru['nama'],
                $guru['jenis_kelamin'],
                $guru['tempat_lahir'],
                $guru['tanggal_lahir'],
                $guru['agama'],
                $guru['alamat'],
                $guru['no_telepon'],
                $guru['email'],
                $guru['status_kepegawaian'],
                $guru['nuptk'],
                $guru['username'],
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
            'data_guru_' . date('Ymd_His') . '.xlsx',
            'sisfour_guru_export_'
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
            'master_guru.manage',
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
