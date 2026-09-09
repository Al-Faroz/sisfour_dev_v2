<?php

namespace App\Controllers;

use App\Services\BkExportService;
use App\Services\BkService;
use CodeIgniter\HTTP\ResponseInterface;

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
        $userId = (int) session()->get('user_id');

        if ($this->wantsJson()) {
            return $this->respond(
                $this->service->getKasusPage($userId, $this->request->getGet())
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
                (int) session()->get('user_id'),
                (int) $id
            )
        );
    }

    public function top()
    {
        $result = $this->service->getTop20((int) session()->get('user_id'));

        if ($this->wantsJson()) {
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
                (int) session()->get('user_id'),
                $this->request->getPost()
            )
        );
    }

    public function update($id)
    {
        return $this->respond(
            $this->service->updateKasus(
                (int) session()->get('user_id'),
                (int) $id,
                $this->request->getJSON(true) ?: $this->request->getRawInput()
            )
        );
    }

    public function delete($id)
    {
        return $this->respond(
            $this->service->deleteKasus(
                (int) session()->get('user_id'),
                (int) $id
            )
        );
    }

    public function createTindakLanjut($idKasus)
    {
        return $this->respond(
            $this->service->createTindakLanjut(
                (int) session()->get('user_id'),
                (int) $idKasus,
                $this->request->getPost()
            )
        );
    }

    public function updateTindakLanjut($id)
    {
        return $this->respond(
            $this->service->updateTindakLanjut(
                (int) session()->get('user_id'),
                (int) $id,
                $this->request->getJSON(true) ?: $this->request->getRawInput()
            )
        );
    }

    public function export()
    {
        $userId = (int) session()->get('user_id');
        $data = $this->service->getKasusExport($userId, $this->request->getGet());

        if (! $data['success']) {
            return $this->respond($data);
        }

        $file = $this->exportService->kasus($data, $userId);

        if (! $file['success']) {
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

        return $this->response
            ->download($path, null)
            ->setFileName($filename);
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
            ->setStatusCode(
                $success
                    ? ResponseInterface::HTTP_OK
                    : $this->httpCode((string) ($result['code'] ?? ''))
            )
            ->setJSON([
                'status' => $success ? 'success' : 'error',
                'message' => $result['message'] ?? ($success ? 'Berhasil.' : 'Gagal.'),
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
