<?php

namespace App\Controllers;

use App\Services\KelasIntegrityService;
use App\Services\KelasService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class MasterKelas extends BaseController
{
    protected KelasService $kelasService;

    public function __construct()
    {
        $this->kelasService = new KelasIntegrityService();
    }

    public function index()
    {
        $filter = $this->filters();

        if ($this->request->getGet('export') === '1') {
            return $this->exportFile($filter);
        }

        if ($this->isJsonRequest()) {
            return $this->response->setJSON([
                'status' => 'success',
                'data' => $this->kelasService->getList($filter),
            ]);
        }

        return $this->response->setBody(
            $this->renderWithLayout('master/kelas', [
                'title' => 'Master Kelas',
                'filters' => $filter,
                'tahunOptions' => $this->kelasService->getTahunOptions(),
                'extraJs' => ['assets/js/master/kelas.js'],
            ])
        );
    }

    public function create()
    {
        return $this->respondResult(
            $this->kelasService->create($this->request->getPost()),
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
            $this->kelasService->update((int) $id, $payload)
        );
    }

    public function delete($id)
    {
        return $this->respondResult(
            $this->kelasService->delete((int) $id)
        );
    }

    public function recycle()
    {
        if ($this->isJsonRequest()) {
            return $this->response->setJSON([
                'status' => 'success',
                'data' => $this->kelasService->getList(
                    $this->filters(),
                    true
                ),
            ]);
        }

        return $this->response->setBody(
            $this->renderWithLayout('master/kelas_recycle', [
                'title' => 'Recycle Bin Kelas',
                'extraJs' => ['assets/js/master/kelas-recycle.js'],
            ])
        );
    }

    public function restore($id)
    {
        return $this->respondResult(
            $this->kelasService->restore((int) $id)
        );
    }

    public function forceDelete($id)
    {
        return $this->respondResult(
            $this->kelasService->forceDelete((int) $id)
        );
    }

    protected function filters(): array
    {
        return [
            'tingkat' => trim(
                (string) $this->request->getGet('tingkat')
            ),
            'id_tahun' => (int) $this->request->getGet('id_tahun'),
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

    private function exportFile(array $filter)
    {
        $rows = $this->kelasService->getList($filter);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Kelas');

        $sheet->setCellValue('A1', 'DATA KELAS SISISFOUR');
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->fromArray([[
            'NAMA KELAS',
            'TINGKAT',
            'ROMBEL',
            'TAHUN AJARAN',
            'SEMESTER',
            'STATUS TAHUN',
            'JUMLAH SISWA',
        ]], null, 'A3');
        $sheet->getStyle('A3:G3')->getFont()->setBold(true);

        $row = 4;
        foreach ($rows as $kelas) {
            $sheet->fromArray([[
                $kelas['nama_kelas'] ?? '',
                $kelas['tingkat'] ?? '',
                $kelas['rombel'] ?? '',
                $kelas['nama_tahun'] ?? '',
                $kelas['semester'] ?? '',
                (int) ($kelas['tahun_aktif'] ?? 0) === 1 ? 'Aktif' : 'Nonaktif',
                (int) ($kelas['jumlah_siswa'] ?? 0),
            ]], null, 'A' . $row);
            $row++;
        }

        foreach (range('A', 'G') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return $this->downloadSpreadsheet(
            $spreadsheet,
            'data_kelas_' . date('Ymd_His') . '.xlsx',
            'sisfour_kelas_export_'
        );
    }

    private function downloadSpreadsheet(
        Spreadsheet $spreadsheet,
        string $filename,
        string $prefix
    ) {
        $tempFile = tempnam(sys_get_temp_dir(), $prefix);
        (new Xlsx($spreadsheet))->save($tempFile);

        register_shutdown_function(static function () use ($tempFile): void {
            if (is_file($tempFile)) {
                @unlink($tempFile);
            }
        });

        return $this->response
            ->download($tempFile, null)
            ->setFileName($filename);
    }
}
