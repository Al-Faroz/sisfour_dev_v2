<?php

namespace App\Controllers;

use App\Services\PtspExportService;
use App\Services\PtspService;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

class PtspPengaduan extends BaseController
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
            return $this->respond($this->service->pengaduanPage($userId, $this->request->getGet()));
        }

        return $this->response->setBody($this->renderWithLayout('ptsp/pengaduan', [
            'title' => 'Pengaduan PTSP',
            'initial' => $this->service->pengaduanPage($userId, $this->request->getGet()),
            'extraJs' => ['assets/js/ptsp/pengaduan.js'],
        ]));
    }

    public function status($id)
    {
        return $this->respond($this->service->updatePengaduanStatus(
            $this->currentActorUserId(),
            (int) $id,
            $this->payload()
        ));
    }

    public function delete($id)
    {
        return $this->respond($this->service->deletePengaduan($this->currentActorUserId(), (int) $id));
    }

    public function attachment($id)
    {
        $result = $this->service->resolveAttachment($this->currentActorUserId(), (int) $id);
        if (! $result['success']) {
            return $this->respond($result);
        }

        return $this->response
            ->download((string) $result['path'], null)
            ->setFileName((string) $result['filename']);
    }

    public function export()
    {
        $userId = $this->currentActorUserId();
        $data = $this->service->pengaduanExportData($userId, $this->request->getGet());
        if (! $data['success']) {
            return $this->respond($data);
        }
        $file = $this->exportService->pengaduan($data, $userId);
        return $file['success'] ? $this->downloadAndCleanup($file['path'], $file['filename']) : $this->respond($file);
    }

    private function payload(): array
    {
        if (str_contains(strtolower($this->request->getHeaderLine('Content-Type')), 'application/json')) {
            try {
                $json = $this->request->getJSON(true);
                if (is_array($json)) {
                    return $json;
                }
            } catch (Throwable $e) {
            }
        }

        return array_replace(
            is_array($this->request->getRawInput()) ? $this->request->getRawInput() : [],
            is_array($this->request->getPost()) ? $this->request->getPost() : []
        );
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
