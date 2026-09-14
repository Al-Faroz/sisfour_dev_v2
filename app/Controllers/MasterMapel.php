<?php

namespace App\Controllers;

use App\Services\MataPelajaranService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * MasterMapel
 *
 * Controller Master Mata Pelajaran.
 */
class MasterMapel extends BaseController
{
    protected MataPelajaranService $mapelService;

    public function __construct()
    {
        $this->mapelService = new MataPelajaranService();
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
                'data' => $this->mapelService->getList(
                    $filter
                ),
            ]);
        }

        return $this->response->setBody(
            $this->renderWithLayout(
                'master/mata_pelajaran',
                [
                    'title' => 'Master Mata Pelajaran',
                    'extraJs' => [
                        'assets/js/master/mata-pelajaran.js',
                    ],
                ]
            )
        );
    }

    public function create()
    {
        return $this->respondResult(
            $this->mapelService->create(
                $this->request->getPost()
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
            $this->mapelService->update(
                (int) $id,
                $payload
            )
        );
    }

    public function delete($id)
    {
        return $this->respondResult(
            $this->mapelService->delete((int) $id)
        );
    }

    protected function filters(): array
    {
        return [
            'nama_mapel' => trim(
                (string) $this->request->getGet(
                    'nama_mapel'
                )
            ),
            'kode_mapel' => trim(
                (string) $this->request->getGet(
                    'kode_mapel'
                )
            ),
        ];
    }

    protected function isJsonRequest(): bool
    {
        $path = trim(
            $this->request->getUri()->getPath(),
            '/'
        );

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
            ->setStatusCode(
                $success
                    ? $successCode
                    : 422
            )
            ->setJSON([
                'status' => $success
                    ? 'success'
                    : 'error',
                'message' => $result['message']
                    ?? ($success
                        ? 'Berhasil.'
                        : 'Gagal.'),
                'data' => $result,
            ]);
    }

    private function exportFile(array $filter)
    {
        $rows = $this->mapelService->getList($filter);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Mata Pelajaran');

        $sheet->setCellValue('A1', 'DATA MATA PELAJARAN SISISFOUR');
        $sheet->mergeCells('A1:C1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->fromArray([[
            'KODE MAPEL',
            'NAMA MATA PELAJARAN',
            'DIGUNAKAN JADWAL',
        ]], null, 'A3');
        $sheet->getStyle('A3:C3')->getFont()->setBold(true);

        $row = 4;
        foreach ($rows as $mapel) {
            $sheet->fromArray([[
                $mapel['kode_mapel'] ?? '',
                $mapel['nama_mapel'] ?? '',
                (int) ($mapel['jumlah_jadwal'] ?? 0),
            ]], null, 'A' . $row);
            $row++;
        }

        foreach (range('A', 'C') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return $this->downloadSpreadsheet(
            $spreadsheet,
            'data_mata_pelajaran_' . date('Ymd_His') . '.xlsx',
            'sisfour_mapel_export_'
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
