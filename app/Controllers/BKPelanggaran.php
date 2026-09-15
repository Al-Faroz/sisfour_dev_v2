<?php

namespace App\Controllers;

use App\Services\BkService;
use CodeIgniter\HTTP\ResponseInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

class BKPelanggaran extends BaseController
{
    protected BkService $service;

    public function __construct()
    {
        $this->service = new BkService();
    }

    public function index()
    {
        if ($this->request->getGet('export') === '1') {
            return $this->exportFile();
        }

        if ($this->wantsJson()) {
            return $this->response->setJSON([
                'status' => 'success',
                'data' => ['rows' => $this->service->listPelanggaran()],
            ]);
        }

        return $this->response->setBody(
            $this->renderWithLayout('bk/pelanggaran', [
                'title' => 'Master Pelanggaran',
                'rows' => $this->service->listPelanggaran(),
                'extraJs' => ['assets/js/bk/pelanggaran.js'],
            ])
        );
    }

    public function create()
    {
        return $this->respond(
            $this->service->createPelanggaran(
                (int) session()->get('user_id'),
                $this->getPayload()
            )
        );
    }

    public function update($id)
    {
        return $this->respond(
            $this->service->updatePelanggaran(
                (int) session()->get('user_id'),
                (int) $id,
                $this->getPayload()
            )
        );
    }

    public function delete($id)
    {
        return $this->respond(
            $this->service->deletePelanggaran(
                (int) session()->get('user_id'),
                (int) $id
            )
        );
    }

    private function getPayload(): array
    {
        $contentType = strtolower(
            trim($this->request->getHeaderLine('Content-Type'))
        );

        if (str_contains($contentType, 'application/json')) {
            try {
                $json = $this->request->getJSON(true);
            } catch (Throwable $e) {
                $json = null;
            }

            if (is_array($json)) {
                return $json;
            }
        }

        $raw = $this->request->getRawInput();

        if (is_array($raw) && $raw !== []) {
            return $raw;
        }

        $post = $this->request->getPost();

        return is_array($post) ? $post : [];
    }

    private function wantsJson(): bool
    {
        $path = rtrim($this->request->getUri()->getPath(), '/');

        return $this->request->getGet('format') === 'json'
            || $this->request->isAJAX()
            || str_ends_with($path, '/json');
    }

    private function respond(array $result)
    {
        $success = (bool) ($result['success'] ?? false);

        return $this->response
            ->setStatusCode($success ? 200 : match ($result['code'] ?? '') {
                'FORBIDDEN' => ResponseInterface::HTTP_FORBIDDEN,
                'NOT_FOUND' => ResponseInterface::HTTP_NOT_FOUND,
                'IN_USE' => ResponseInterface::HTTP_CONFLICT,
                default => ResponseInterface::HTTP_UNPROCESSABLE_ENTITY,
            })
            ->setJSON([
                'status' => $success ? 'success' : 'error',
                'message' => $result['message'] ?? ($success ? 'Berhasil.' : 'Gagal.'),
                'data' => $result,
            ]);
    }

    private function exportFile()
    {
        $rows = $this->service->listPelanggaran();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Master Pelanggaran');

        $sheet->setCellValue('A1', 'MASTER PELANGGARAN SISISFOUR');
        $sheet->mergeCells('A1:C1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->fromArray([[
            'NAMA PELANGGARAN',
            'KATEGORI',
            'POIN',
        ]], null, 'A3');
        $sheet->getStyle('A3:C3')->getFont()->setBold(true);

        $row = 4;
        foreach ($rows as $pelanggaran) {
            $sheet->fromArray([[
                $pelanggaran['nama_pelanggaran'] ?? '',
                $pelanggaran['kategori'] ?? '',
                (int) ($pelanggaran['poin'] ?? 0),
            ]], null, 'A' . $row);
            $row++;
        }

        foreach (range('A', 'C') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'sisfour_pelanggaran_export_');
        (new Xlsx($spreadsheet))->save($tempFile);

        register_shutdown_function(static function () use ($tempFile): void {
            if (is_file($tempFile)) {
                @unlink($tempFile);
            }
        });

        return $this->response
            ->download($tempFile, null)
            ->setFileName('master_pelanggaran_' . date('Ymd_His') . '.xlsx');
    }
}
