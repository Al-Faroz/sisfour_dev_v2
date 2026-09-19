<?php

namespace App\Controllers;

use App\Services\PtspExportService;
use App\Services\PtspService;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

class PtspLayanan extends BaseController
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
            return $this->respond($this->service->layananPage($userId, $this->request->getGet()));
        }

        return $this->response->setBody($this->renderWithLayout('ptsp/layanan', [
            'title' => 'Layanan PTSP',
            'initial' => $this->service->layananPage($userId, $this->request->getGet()),
            'extraJs' => ['assets/js/ptsp/layanan.js'],
        ]));
    }

    public function create()
    {
        return $this->respond($this->service->submitLayanan($this->payload(), $this->currentActorUserId()));
    }

    public function status($id)
    {
        return $this->respond($this->service->updateLayananStatus(
            $this->currentActorUserId(),
            (int) $id,
            $this->payload()
        ));
    }

    public function delete($id)
    {
        return $this->respond($this->service->deleteLayanan($this->currentActorUserId(), (int) $id));
    }

    public function export()
    {
        $userId = $this->currentActorUserId();
        $data = $this->service->layananExportData($userId, $this->request->getGet());
        if (! $data['success']) {
            return $this->respond($data);
        }
        $file = $this->exportService->layanan($data, $userId);
        return $file['success'] ? $this->downloadAndCleanup($file['path'], $file['filename']) : $this->respond($file);
    }

    private function payload(): array
    {
        $post = $this->request->getPost();
        $raw = $this->request->getRawInput();
        if (str_contains(strtolower($this->request->getHeaderLine('Content-Type')), 'application/json')) {
            try {
                $json = $this->request->getJSON(true);
                if (is_array($json)) {
                    return $json;
                }
            } catch (Throwable $e) {
            }
        }
        return array_replace(is_array($raw) ? $raw : [], is_array($post) ? $post : []);
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
