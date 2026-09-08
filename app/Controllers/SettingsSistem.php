<?php

namespace App\Controllers;

use App\Services\SettingsSistemService;
use CodeIgniter\HTTP\ResponseInterface;

class SettingsSistem extends BaseController
{
    protected SettingsSistemService $service;

    public function __construct()
    {
        $this->service = new SettingsSistemService();
    }

    public function index()
    {
        $result = $this->service->page();

        if ($this->wantsJson()) {
            return $this->respond($result);
        }

        return $this->response->setBody(
            $this->renderWithLayout('settings/sistem', [
                'title' => 'Setting Sistem',
                'initial' => $result,
                'extraJs' => ['assets/js/settings/sistem.js'],
            ])
        );
    }

    public function update()
    {
        return $this->respond(
            $this->service->update(
                (int) session()->get('user_id'),
                $this->request->getJSON(true) ?: $this->request->getRawInput()
            )
        );
    }

    public function maintenance()
    {
        return $this->respond(
            $this->service->maintenance(
                (int) session()->get('user_id'),
                $this->request->getPost()
            )
        );
    }

    public function uploadBranding()
    {
        return $this->respond(
            $this->service->uploadBranding(
                (int) session()->get('user_id'),
                (string) $this->request->getPost('asset_type'),
                $this->request->getFile('file')
            )
        );
    }

    public function uploadBackgroundKta()
    {
        return $this->respond(
            $this->service->uploadBackgroundKta(
                (int) session()->get('user_id'),
                (string) $this->request->getPost('side'),
                $this->request->getFile('file')
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
            ->setStatusCode(
                $success
                    ? 200
                    : match ($result['code'] ?? '') {
                        'FORBIDDEN' => ResponseInterface::HTTP_FORBIDDEN,
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
