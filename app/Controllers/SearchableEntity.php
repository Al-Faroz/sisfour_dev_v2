<?php

namespace App\Controllers;

use App\Services\SearchableEntityService;
use CodeIgniter\HTTP\ResponseInterface;

class SearchableEntity extends BaseController
{
    protected SearchableEntityService $service;

    public function __construct()
    {
        $this->service = new SearchableEntityService();
    }

    public function siswa()
    {
        $result = $this->service->searchStudents(
            (int) session()->get('user_id'),
            (string) $this->request->getGet('q'),
            (string) $this->request->getGet('context'),
            (int) ($this->request->getGet('limit') ?: 25)
        );

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
            'FORBIDDEN', 'NO_GURU_IDENTITY', 'NO_STUDENT_IDENTITY' => 403,
            'INVALID_CONTEXT' => 422,
            default => 422,
        };
    }
}
