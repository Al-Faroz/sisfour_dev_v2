<?php

namespace App\Controllers;

use App\Services\KonselingBkExportService;
use App\Services\KonselingBkService;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

class BKKonseling extends BaseController
{
    protected KonselingBkService $service;
    protected KonselingBkExportService $exportService;

    public function __construct()
    {
        $this->service = new KonselingBkService();
        $this->exportService = new KonselingBkExportService();
    }

    public function index()
    {
        $userId = $this->currentActorUserId();

        if ($this->requestWantsJson()) {
            return $this->respond(
                $this->service->getPage($userId, $this->request->getGet())
            );
        }

        return $this->response->setBody(
            $this->renderWithLayout('bk/konseling', [
                'title' => 'Konseling BK',
                'initial' => $this->service->getPage($userId, []),
                'extraJs' => ['assets/js/bk/konseling.js'],
            ])
        );
    }

    public function students($idKelas)
    {
        return $this->respond(
            $this->service->studentsByClass(
                $this->currentActorUserId(),
                (int) $idKelas
            )
        );
    }

    public function detail($id)
    {
        return $this->respond(
            $this->service->getDetail(
                $this->currentActorUserId(),
                (int) $id
            )
        );
    }

    public function create()
    {
        return $this->respond(
            $this->service->create(
                $this->currentActorUserId(),
                $this->getPayload()
            )
        );
    }

    public function update($id)
    {
        return $this->respond(
            $this->service->update(
                $this->currentActorUserId(),
                (int) $id,
                $this->getPayload()
            )
        );
    }

    public function export()
    {
        $data = $this->service->getExport(
            $this->currentActorUserId(),
            $this->request->getGet()
        );

        if (! ($data['success'] ?? false)) {
            return $this->respond($data);
        }

        $file = $this->exportService->export($data['rows'] ?? []);
        if (! ($file['success'] ?? false)) {
            return $this->respond($file);
        }

        $path = (string) $file['path'];
        register_shutdown_function(static function () use ($path): void {
            if (is_file($path)) {
                @unlink($path);
            }
        });

        return $this->response
            ->download($path, null)
            ->setFileName((string) $file['filename']);
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

        $post = $this->request->getPost();
        $raw = $this->request->getRawInput();

        $post = is_array($post) ? $post : [];
        $raw = is_array($raw) ? $raw : [];

        return array_replace($raw, $post);
    }

    private function respond(array $result)
    {
        $success = (bool) ($result['success'] ?? false);

        return $this->response
            ->setStatusCode(
                $success
                    ? ResponseInterface::HTTP_OK
                    : $this->httpCode((string) ($result['code'] ?? ''))
            )
            ->setJSON([
                'status' => $success ? 'success' : 'error',
                'message' => $result['message']
                    ?? ($success ? 'Berhasil.' : 'Gagal.'),
                'data' => $result,
            ]);
    }

    private function httpCode(string $code): int
    {
        return match ($code) {
            'FORBIDDEN' => ResponseInterface::HTTP_FORBIDDEN,
            'NOT_FOUND' => ResponseInterface::HTTP_NOT_FOUND,
            default => ResponseInterface::HTTP_UNPROCESSABLE_ENTITY,
        };
    }
}
