<?php

namespace App\Controllers;

use App\Services\ProfileService;
use App\Support\RequestContext;
use Throwable;

class ProfilePegawai extends BaseController
{
    protected ProfileService $profileService;

    public function __construct()
    {
        $this->profileService = new ProfileService();
    }

    public function index()
    {
        $result = $this->profileService->getPegawaiProfile($this->actorUserId());

        if ($this->wantsJson()) {
            return $this->respondResult($result);
        }

        if (empty($result['success'])) {
            return $this->response
                ->setStatusCode(422)
                ->setBody(
                    $this->renderWithLayout('profile/pegawai', [
                        'title' => 'Profile Pegawai',
                        'profile' => null,
                        'profileError' => $result['message'] ?? 'Profile tidak tersedia.',
                        'extraJs' => [],
                    ])
                );
        }

        return $this->response->setBody(
            $this->renderWithLayout('profile/pegawai', [
                'title' => 'Profile Pegawai',
                'profile' => $result['profile'],
                'profileError' => null,
                'extraJs' => ['assets/js/profile/pegawai.js'],
            ])
        );
    }

    public function update()
    {
        $payload = $this->request->getRawInput();

        if ($payload === []) {
            try {
                $json = $this->request->getJSON(true);
            } catch (Throwable $e) {
                $json = null;
            }

            $payload = is_array($json) ? $json : $this->request->getPost();
        }

        return $this->respondResult(
            $this->profileService->updatePegawaiProfile(
                $this->actorUserId(),
                is_array($payload) ? $payload : []
            )
        );
    }

    public function uploadFoto()
    {
        return $this->respondResult(
            $this->profileService->uploadPegawaiFoto(
                $this->actorUserId(),
                $this->request->getFile('foto')
            )
        );
    }

    private function actorUserId(): int
    {
        if ($this->isApiRequest()) {
            $apiUser = RequestContext::get($this->request, 'api_user');
            return is_array($apiUser) ? (int) ($apiUser['id'] ?? 0) : 0;
        }

        return (int) session()->get('user_id');
    }

    private function wantsJson(): bool
    {
        if ($this->isApiRequest()) {
            return true;
        }

        $path = trim($this->request->getUri()->getPath(), '/');

        return str_ends_with($path, '/json')
            || $this->request->isAJAX()
            || $this->request->getGet('format') === 'json';
    }

    private function isApiRequest(): bool
    {
        $path = trim($this->request->getUri()->getPath(), '/');
        return $path === 'api' || str_starts_with($path, 'api/');
    }

    private function respondResult(array $result)
    {
        $success = (bool) ($result['success'] ?? false);
        $status = $success ? 200 : $this->errorStatus($result);

        $payload = [
            'success' => $success,
            'message' => $result['message'] ?? ($success ? 'Berhasil.' : 'Gagal.'),
        ];

        if ($success) {
            $payload['data'] = $result['profile'] ?? ['foto' => $result['foto'] ?? null];
        } elseif (! empty($result['code'])) {
            $payload['code'] = $result['code'];
        }

        return $this->response->setStatusCode($status)->setJSON($payload);
    }

    private function errorStatus(array $result): int
    {
        return match ($result['code'] ?? '') {
            'UNAUTHENTICATED' => 401,
            'PROFILE_NOT_FOUND' => 404,
            'IDENTITY_NOT_LINKED', 'VALIDATION' => 422,
            default => 422,
        };
    }
}
