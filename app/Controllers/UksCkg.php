<?php

namespace App\Controllers;

use App\Services\UksExportService;
use App\Services\UksService;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

class UksCkg extends BaseController
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
                $this->service->ckgPage($userId, $this->request->getGet())
            );
        }

        $initial = $this->service->ckgPage($userId, $this->request->getGet());

        return $this->response->setBody(
            $this->renderWithLayout('uks/ckg', [
                'title' => 'Data CKG',
                'initial' => $initial,
                'extraJs' => ['assets/js/uks/ckg.js'],
            ])
        );
    }

    public function create()
    {
        return $this->respond(
            $this->service->createCkg(
                $this->currentActorUserId(),
                $this->payload()
            )
        );
    }

    public function update($id)
    {
        return $this->respond(
            $this->service->updateCkg(
                $this->currentActorUserId(),
                (int) $id,
                $this->payload()
            )
        );
    }

    public function delete($id)
    {
        return $this->respond(
            $this->service->deleteCkg(
                $this->currentActorUserId(),
                (int) $id
            )
        );
    }

    public function import()
    {
        $file = $this->request->getFile('file');

        if ($file === null) {
            return $this->respond([
                'success' => false,
                'code' => 'VALIDATION',
                'message' => 'File import wajib dipilih.',
            ]);
        }

        return $this->respond(
            $this->service->importCkg(
                $this->currentActorUserId(),
                $file
            )
        );
    }

    public function template()
    {
        $userId = $this->currentActorUserId();
        if ((new \App\Services\AuthService())->resolveScope('uks_ckg.import', $userId) !== 'SEMUA') {
            return $this->respond([
                'success' => false,
                'code' => 'FORBIDDEN',
                'message' => 'Anda tidak memiliki hak download template CKG.',
            ]);
        }

        $file = $this->exportService->ckgTemplate($userId);
        if (! $file['success']) {
            return $this->respond($file);
        }

        return $this->downloadAndCleanup($file['path'], $file['filename']);
    }

    public function export()
    {
        $userId = $this->currentActorUserId();
        $data = $this->service->ckgExportData(
            $userId,
            $this->request->getGet()
        );

        if (! $data['success']) {
            return $this->respond($data);
        }

        $file = $this->exportService->ckg($data, $userId);
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
