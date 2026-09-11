<?php

namespace App\Controllers;

use App\Services\ProfileService;
use App\Support\RequestContext;

class ProfileSiswa extends BaseController
{
    protected ProfileService $profileService;

    public function __construct()
    {
        $this->profileService = new ProfileService();
    }

    public function index()
    {
        $result = $this->profileService->getSiswaProfile(
            $this->actorUserId()
        );

        if ($this->wantsJson()) {
            return $this->respondResult($result);
        }

        return $this->response->setBody(
            $this->renderWithLayout('profile/siswa', [
                'title' => 'Profile Siswa',
                'profile' => !empty($result['success'])
                    ? $result['profile']
                    : null,
                'profileError' => empty($result['success'])
                    ? ($result['message'] ?? 'Profile tidak tersedia.')
                    : null,
            ])
        );
    }

    private function actorUserId(): int
    {
        if ($this->isApiRequest()) {
            $apiUser = RequestContext::get($this->request, 'apiUser');

            return is_array($apiUser)
                ? (int) ($apiUser['id'] ?? 0)
                : 0;
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

        $status = $success
            ? 200
            : match ($result['code'] ?? '') {
                'UNAUTHENTICATED' => 401,
                'PROFILE_NOT_FOUND' => 404,
                'IDENTITY_NOT_LINKED' => 422,
                default => 422,
            };

        $payload = [
            'success' => $success,
            'message' => $result['message']
                ?? ($success ? 'Berhasil.' : 'Gagal.'),
        ];

        if ($success) {
            $payload['data'] = $result['profile'];
        } elseif (!empty($result['code'])) {
            $payload['code'] = $result['code'];
        }

        return $this->response
            ->setStatusCode($status)
            ->setJSON($payload);
    }
}
