<?php

namespace App\Controllers;

use App\Services\UksExportService;
use App\Services\UksService;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

class UksHarian extends BaseController
{
    protected UksService $service;
    protected UksExportService $exportService;

    public function __construct()
    {
        $this->service = new UksService();
        $this->exportService = new UksExportService();
    }

    public function index()
    {
        $userId = $this->currentActorUserId();

        if ($this->requestWantsJson()) {
            return $this->respond(
                $this->service->harianPage($userId, $this->request->getGet())
            );
        }

        $initial = $this->service->harianPage(
            $userId,
            $this->request->getGet()
        );

        return $this->response->setBody(
            $this->renderWithLayout('uks/harian', [
                'title' => 'Catatan Harian UKS',
                'initial' => $initial,
                'extraJs' => ['assets/js/uks/harian.js'],
            ])
        );
    }

    public function create()
    {
        return $this->respond(
            $this->service->createHarian(
                $this->currentActorUserId(),
                $this->payload()
            )
        );
    }

    public function update($id)
    {
        return $this->respond(
            $this->service->updateHarian(
                $this->currentActorUserId(),
                (int) $id,
                $this->payload()
            )
        );
    }

    public function delete($id)
    {
        return $this->respond(
            $this->service->deleteHarian(
                $this->currentActorUserId(),
                (int) $id
            )
        );
    }

    public function export()
    {
        $userId = $this->currentActorUserId();
        $data = $this->service->harianExportData(
            $userId,
            $this->request->getGet()
        );

        if (! $data['success']) {
            return $this->respond($data);
        }

        $file = $this->exportService->harian($data, $userId);
        if (! $file['success']) {
            return $this->respond($file);
        }

        return $this->downloadAndCleanup($file['path'], $file['filename']);
    }

    private function payload(): array
    {
        $contentType = strtolower(trim($this->request->getHeaderLine('Content-Type')));

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

        $post = $this->request->getPost();
        $raw = $this->request->getRawInput();

        return array_replace(
            is_array($raw) ? $raw : [],
            is_array($post) ? $post : []
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

        return $this->response
            ->setStatusCode(
                $success
                    ? ResponseInterface::HTTP_OK
                    : match ($result['code'] ?? '') {
                        'FORBIDDEN', 'NO_STUDENT_IDENTITY', 'NO_GURU_IDENTITY'
                            => ResponseInterface::HTTP_FORBIDDEN,
                        'NOT_FOUND' => ResponseInterface::HTTP_NOT_FOUND,
                        default => ResponseInterface::HTTP_UNPROCESSABLE_ENTITY,
                    }
            )
            ->setJSON([
                'status' => $success ? 'success' : 'error',
                'message' => $result['message'] ?? ($success ? 'Berhasil.' : 'Gagal.'),
                'data' => $result,
            ]);
    }
}
