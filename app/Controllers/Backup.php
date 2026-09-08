<?php

namespace App\Controllers;

use App\Services\BackupService;
use CodeIgniter\HTTP\ResponseInterface;

class Backup extends BaseController
{
    protected BackupService $service;

    public function __construct()
    {
        $this->service = new BackupService();
    }

    public function index()
    {
        $result = $this->service->page();

        if ($this->wantsJson()) {
            return $this->respond($result);
        }

        return $this->response->setBody(
            $this->renderWithLayout(
                'backup/index',
                [
                    'title' => 'Backup Database',
                    'initial' => $result,
                    'extraJs' => [
                        'assets/js/backup/index.js',
                    ],
                ]
            )
        );
    }

    public function create()
    {
        return $this->respond(
            $this->service->create(
                (int) session()->get('user_id')
            )
        );
    }

    public function download($filename)
    {
        $result = $this->service->resolveDownload(
            (int) session()->get('user_id'),
            (string) $filename
        );

        if (!$result['success']) {
            return $this->respond($result);
        }

        return $this->response
            ->download(
                $result['path'],
                null
            )
            ->setFileName(
                $result['filename']
            );
    }

    public function delete($filename)
    {
        return $this->respond(
            $this->service->delete(
                (int) session()->get('user_id'),
                (string) $filename
            )
        );
    }

    private function wantsJson(): bool
    {
        return $this->request->getGet('format') === 'json'
            || $this->request->isAJAX();
    }

    private function respond(array $result)
    {
        $success = (bool) ($result['success'] ?? false);

        $status = $success
            ? ResponseInterface::HTTP_OK
            : match ($result['code'] ?? '') {
                'NOT_FOUND' =>
                    ResponseInterface::HTTP_NOT_FOUND,
                'INVALID_FILENAME',
                'INVALID_PATH' =>
                    ResponseInterface::HTTP_BAD_REQUEST,
                default =>
                    ResponseInterface::HTTP_UNPROCESSABLE_ENTITY,
            };

        return $this->response
            ->setStatusCode($status)
            ->setJSON([
                'status' => $success
                    ? 'success'
                    : 'error',
                'message' => $result['message']
                    ?? ($success ? 'Berhasil.' : 'Gagal.'),
                'data' => $result,
            ]);
    }
}
