<?php

namespace App\Controllers;

use App\Services\BkKonselingFormSettingsService;
use CodeIgniter\HTTP\ResponseInterface;

class BKKonselingSettings extends BaseController
{
    protected BkKonselingFormSettingsService $service;

    public function __construct()
    {
        $this->service = new BkKonselingFormSettingsService();
    }

    public function index()
    {
        $userId = $this->currentActorUserId();
        $initial = $this->service->page($userId);

        if ($this->requestWantsJson()) {
            return $this->respond($initial);
        }

        return $this->response->setBody(
            $this->renderWithLayout('bk/konseling_settings', [
                'title' => 'Pengaturan Form Konseling',
                'initial' => $initial,
                'extraJs' => ['assets/js/bk/konseling-settings.js'],
            ])
        );
    }

    public function update()
    {
        return $this->respond(
            $this->service->update(
                $this->currentActorUserId(),
                $this->getPayload()
            )
        );
    }

    public function reset()
    {
        return $this->respond(
            $this->service->reset($this->currentActorUserId())
        );
    }

    private function getPayload(): array
    {
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
