<?php

namespace App\Controllers;

use App\Services\SettingsUserService;
use CodeIgniter\HTTP\ResponseInterface;

class SettingsUser extends BaseController
{
    protected SettingsUserService $service;

    public function __construct()
    {
        $this->service = new SettingsUserService();
    }

    public function index()
    {
        if ($this->request->getGet('mode') === 'options') {
            return $this->respond($this->service->identityOptions($this->request->getGet()));
        }

        if ($this->wantsJson()) {
            if ($this->request->getGet('id')) {
                return $this->respond($this->service->get((int) $this->request->getGet('id')));
            }
            return $this->respond($this->service->page($this->request->getGet()));
        }

        return $this->response->setBody($this->renderWithLayout('settings/user', [
            'title' => 'Manajemen User',
            'initial' => $this->service->page([]),
            'extraJs' => ['assets/js/settings/user.js'],
        ]));
    }

    public function create()
    {
        return $this->respond($this->service->create((int) session()->get('user_id'), $this->request->getPost()));
    }

    public function update($id)
    {
        return $this->respond($this->service->update(
            (int) session()->get('user_id'),
            (int) $id,
            $this->request->getJSON(true) ?: $this->request->getRawInput()
        ));
    }

    public function reset($id)
    {
        return $this->respond($this->service->resetPassword(
            (int) session()->get('user_id'),
            (int) $id,
            $this->request->getPost()
        ));
    }

    public function delete($id)
    {
        return $this->respond($this->service->delete((int) session()->get('user_id'), (int) $id));
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
                'DUPLICATE', 'IDENTITY_IN_USE', 'LAST_ADMIN', 'MANAGED_ACCOUNT' => ResponseInterface::HTTP_CONFLICT,
                default => ResponseInterface::HTTP_UNPROCESSABLE_ENTITY,
            })
            ->setJSON([
                'status' => $success ? 'success' : 'error',
                'message' => $result['message'] ?? ($success ? 'Berhasil.' : 'Gagal.'),
                'data' => $result,
            ]);
    }
}
