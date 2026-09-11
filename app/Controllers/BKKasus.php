<?php

namespace App\Controllers;

use App\Services\BkExportService;
use App\Services\BkService;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

class BKKasus extends BaseController
{
    protected BkService $service;
    protected BkExportService $exportService;

    public function __construct()
    {
        $this->service = new BkService();
        $this->exportService = new BkExportService();
    }

    public function index()
    {
        $userId = $this->currentActorUserId();

        if ($this->requestWantsJson()) {
            return $this->respond(
                $this->service->getKasusPage(
                    $userId,
                    $this->request->getGet()
                )
            );
        }

        return $this->response->setBody(
            $this->renderWithLayout('bk/kasus', [
                'title' => 'Catatan Kasus',
                'initial' => $this->service->getKasusPage($userId, []),
                'extraJs' => ['assets/js/bk/kasus.js'],
            ])
        );
    }

    public function detail($id)
    {
        return $this->respond(
            $this->service->getKasusDetail(
                $this->currentActorUserId(),
                (int) $id
            )
        );
    }

    public function top()
    {
        $result = $this->service->getTop20(
            $this->currentActorUserId()
        );

        if ($this->requestWantsJson()) {
            return $this->respond($result);
        }

        return $this->response->setBody(
            $this->renderWithLayout('bk/top', [
                'title' => 'Top 20 Poin Pelanggaran',
                'result' => $result,
            ])
        );
    }

    public function create()
    {
        return $this->respond(
            $this->service->createKasus(
                $this->currentActorUserId(),
                $this->getPayload()
            )
        );
    }

    public function update($id)
    {
        return $this->respond(
            $this->service->updateKasus(
                $this->currentActorUserId(),
                (int) $id,
                $this->getPayload()
            )
        );
    }

    public function delete($id)
    {
        return $this->respond(
            $this->service->deleteKasus(
                $this->currentActorUserId(),
                (int) $id
            )
        );
    }

    public function createTindakLanjut($idKasus)
    {
        return $this->respond(
            $this->service->createTindakLanjut(
                $this->currentActorUserId(),
                (int) $idKasus,
                $this->getPayload()
            )
        );
    }

    public function updateTindakLanjut($id)
    {
        return $this->respond(
            $this->service->updateTindakLanjut(
                $this->currentActorUserId(),
                (int) $id,
                $this->getPayload()
            )
        );
    }

    public function export()
    {
        $userId = $this->currentActorUserId();
        $data = $this->service->getKasusExport(
            $userId,
            $this->request->getGet()
        );

        if (! $data['success']) {
            return $this->respond($data);
        }

        $file = $this->exportService->kasus($data, $userId);

        if (! $file['success']) {
            return $this->respond($file);
        }

        return $this->downloadAndCleanup(
            $file['path'],
            $file['filename']
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

    private function downloadAndCleanup(string $path, string $filename)
    {
        register_shutdown_function(
            static function () use ($path): void {
                if (is_file($path)) {
                    @unlink($path);
                }
            }
        );

        return $this->response
            ->download($path, null)
            ->setFileName($filename);
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
            'FORBIDDEN',
            'NO_STUDENT_IDENTITY',
            'NO_GURU_IDENTITY' => ResponseInterface::HTTP_FORBIDDEN,
            'NOT_FOUND' => ResponseInterface::HTTP_NOT_FOUND,
            default => ResponseInterface::HTTP_UNPROCESSABLE_ENTITY,
        };
    }
}
