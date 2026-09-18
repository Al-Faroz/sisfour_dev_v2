<?php

namespace App\Controllers;

use App\Services\PtspExportService;
use App\Services\PtspService;
use CodeIgniter\HTTP\ResponseInterface;

class PtspPolling extends BaseController
{
    protected PtspService $service;
    protected PtspExportService $exportService;

    public function __construct()
    {
        $this->service = new PtspService();
        $this->exportService = new PtspExportService();
    }

    public function index()
    {
        $userId = $this->currentActorUserId();
        if ($this->requestWantsJson()) {
            return $this->respond($this->service->pollingPage($userId, $this->request->getGet()));
        }

        return $this->response->setBody($this->renderWithLayout('ptsp/polling', [
            'title' => 'Polling Kepuasan PTSP',
            'initial' => $this->service->pollingPage($userId, $this->request->getGet()),
            'extraJs' => ['assets/js/ptsp/polling.js'],
        ]));
    }

    public function delete($id)
    {
        return $this->respond($this->service->deletePolling($this->currentActorUserId(), (int) $id));
    }

    public function export()
    {
        $userId = $this->currentActorUserId();
        $data = $this->service->pollingExportData($userId, $this->request->getGet());
        if (! $data['success']) {
            return $this->respond($data);
        }
        $file = $this->exportService->polling($data, $userId);
        return $file['success'] ? $this->downloadAndCleanup($file['path'], $file['filename']) : $this->respond($file);
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

    private function respond(array $result)
    {
        $success = (bool) ($result['success'] ?? false);
        return $this->response->setStatusCode(
            $success ? ResponseInterface::HTTP_OK : match ($result['code'] ?? '') {
                'FORBIDDEN' => ResponseInterface::HTTP_FORBIDDEN,
                'NOT_FOUND' => ResponseInterface::HTTP_NOT_FOUND,
                default => ResponseInterface::HTTP_UNPROCESSABLE_ENTITY,
            }
        )->setJSON([
            'status' => $success ? 'success' : 'error',
            'message' => $result['message'] ?? ($success ? 'Berhasil.' : 'Gagal.'),
            'data' => $result,
        ]);
    }
}
