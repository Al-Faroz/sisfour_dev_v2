<?php

namespace App\Controllers;

use App\Services\SettingsMenuService;
use CodeIgniter\HTTP\ResponseInterface;

class SettingsMenu extends BaseController
{
    protected SettingsMenuService $service;

    public function __construct()
    {
        $this->service = new SettingsMenuService();
    }

    public function index()
    {
        $result = $this->service->page();

        if ($this->wantsJson()) {
            return $this->respond($result);
        }

        return $this->response->setBody(
            $this->renderWithLayout('settings/menu', [
                'title' => 'Menu & Role',
                'initial' => $result,
                'extraJs' => ['assets/js/settings/menu.js'],
            ])
        );
    }

    public function update($id)
    {
        return $this->respond(
            $this->service->update(
                (int) session()->get('user_id'),
                (int) $id,
                $this->request->getJSON(true) ?: $this->request->getRawInput()
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
                        'NOT_FOUND' => ResponseInterface::HTTP_NOT_FOUND,
                        'INCONSISTENT' => ResponseInterface::HTTP_CONFLICT,
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
