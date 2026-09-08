<?php

namespace App\Controllers;

use App\Services\BkExportService;
use App\Services\PrestasiService;
use CodeIgniter\HTTP\ResponseInterface;

class BKPrestasi extends BaseController
{
    protected PrestasiService $service;
    protected BkExportService $exportService;

    public function __construct()
    {
        $this->service = new PrestasiService();
        $this->exportService = new BkExportService();
    }

    public function index()
    {
        $userId = (int) session()->get('user_id');

        if ($this->wantsJson()) {
            return $this->respond($this->service->getPage($userId, $this->request->getGet()));
        }

        $initial = $this->service->getPage($userId, []);

        return $this->response->setBody(
            $this->renderWithLayout('bk/prestasi', [
                'title' => 'Prestasi Siswa',
                'initial' => $initial,
                'extraJs' => ['assets/js/bk/prestasi.js'],
            ])
        );
    }

    public function create()
    {
        return $this->respond(
            $this->service->create(
                (int) session()->get('user_id'),
                $this->request->getPost()
            )
        );
    }

    public function update($id)
    {
        return $this->respond(
            $this->service->update(
                (int) session()->get('user_id'),
                (int) $id,
                $this->request->getJSON(true) ?: $this->request->getRawInput()
            )
        );
    }

    public function delete($id)
    {
        return $this->respond(
            $this->service->delete(
                (int) session()->get('user_id'),
                (int) $id
            )
        );
    }

    public function export()
    {
        $userId = (int) session()->get('user_id');
        $data = $this->service->getExport($userId, $this->request->getGet());

        if (!$data['success']) {
            return $this->respond($data);
        }

        $file = $this->exportService->prestasi($data, $userId);

        if (!$file['success']) {
            return $this->respond($file);
        }

        return $this->downloadAndCleanup($file['path'], $file['filename']);
    }

    private function downloadAndCleanup(string $path, string $filename)
    {
        register_shutdown_function(static function () use ($path): void {
            if (is_file($path)) {
                @unlink($path);
            }
        });

        return $this->response->download($path, null)->setFileName($filename);
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
                'FORBIDDEN', 'NO_STUDENT_IDENTITY', 'NO_GURU_IDENTITY' => ResponseInterface::HTTP_FORBIDDEN,
                'NOT_FOUND' => ResponseInterface::HTTP_NOT_FOUND,
                default => ResponseInterface::HTTP_UNPROCESSABLE_ENTITY,
            })
            ->setJSON([
                'status' => $success ? 'success' : 'error',
                'message' => $result['message'] ?? ($success ? 'Berhasil.' : 'Gagal.'),
                'data' => $result,
            ]);
    }
}
