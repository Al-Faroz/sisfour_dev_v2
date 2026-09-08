<?php

namespace App\Controllers;

use App\Services\BkService;
use CodeIgniter\HTTP\ResponseInterface;

class BKPelanggaran extends BaseController
{
    protected BkService $service;

    public function __construct()
    {
        $this->service = new BkService();
    }

    public function index()
    {
        if ($this->wantsJson()) {
            return $this->response->setJSON([
                'status' => 'success',
                'data' => ['rows' => $this->service->listPelanggaran()],
            ]);
        }

        return $this->response->setBody(
            $this->renderWithLayout('bk/pelanggaran', [
                'title' => 'Master Pelanggaran',
                'rows' => $this->service->listPelanggaran(),
                'extraJs' => ['assets/js/bk/pelanggaran.js'],
            ])
        );
    }

    public function create()
    {
        return $this->respond(
            $this->service->createPelanggaran(
                (int) session()->get('user_id'),
                $this->request->getPost()
            )
        );
    }

    public function update($id)
    {
        return $this->respond(
            $this->service->updatePelanggaran(
                (int) session()->get('user_id'),
                (int) $id,
                $this->request->getJSON(true) ?: $this->request->getRawInput()
            )
        );
    }

    public function delete($id)
    {
        return $this->respond(
            $this->service->deletePelanggaran(
                (int) session()->get('user_id'),
                (int) $id
            )
        );
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
            ->setStatusCode($success ? 200 : match ($result['code'] ?? '') {
                'FORBIDDEN' => ResponseInterface::HTTP_FORBIDDEN,
                'NOT_FOUND' => ResponseInterface::HTTP_NOT_FOUND,
                'IN_USE' => ResponseInterface::HTTP_CONFLICT,
                default => ResponseInterface::HTTP_UNPROCESSABLE_ENTITY,
            })
            ->setJSON([
                'status' => $success ? 'success' : 'error',
                'message' => $result['message'] ?? ($success ? 'Berhasil.' : 'Gagal.'),
                'data' => $result,
            ]);
    }
}
