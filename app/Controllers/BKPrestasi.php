<?php

namespace App\Controllers;

use App\Services\BkExportService;
use App\Services\PrestasiService;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

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
        $userId = $this->currentActorUserId();

        if ($this->requestWantsJson()) {
            return $this->respond(
                $this->service->getPage(
                    $userId,
                    $this->request->getGet()
                )
            );
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

    public function delete($id)
    {
        return $this->respond(
            $this->service->delete(
                $this->currentActorUserId(),
                (int) $id
            )
        );
    }

    public function export()
    {
        $userId = $this->currentActorUserId();
        $data = $this->service->getExport(
            $userId,
            $this->request->getGet()
        );

        if (!$data['success']) {
            return $this->respond($data);
        }

        $file = $this->exportService->prestasi($data, $userId);

        if (!$file['success']) {
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
                    : match ($result['code'] ?? '') {
                        'FORBIDDEN',
                        'NO_STUDENT_IDENTITY',
                        'NO_GURU_IDENTITY'
                            => ResponseInterface::HTTP_FORBIDDEN,
                        'NOT_FOUND'
                            => ResponseInterface::HTTP_NOT_FOUND,
                        default
                            => ResponseInterface::HTTP_UNPROCESSABLE_ENTITY,
                    }
            )
            ->setJSON([
                'status' => $success ? 'success' : 'error',
                'message' => $result['message']
                    ?? ($success ? 'Berhasil.' : 'Gagal.'),
                'data' => $result,
            ]);
    }
}
