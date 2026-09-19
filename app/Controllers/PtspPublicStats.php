<?php

namespace App\Controllers;

use App\Services\PtspService;
use CodeIgniter\HTTP\ResponseInterface;

class PtspPublicStats extends BaseController
{
    protected PtspService $service;

    public function __construct()
    {
        $this->service = new PtspService();
    }

    public function show($type)
    {
        $result = $this->service->publicStatistics((string) $type, $this->request->getGet());
        $success = (bool) ($result['success'] ?? false);

        return $this->response
            ->setStatusCode($success ? ResponseInterface::HTTP_OK : match ($result['code'] ?? '') {
                'NOT_FOUND' => ResponseInterface::HTTP_NOT_FOUND,
                default => ResponseInterface::HTTP_UNPROCESSABLE_ENTITY,
            })
            ->setJSON([
                'status' => $success ? 'success' : 'error',
                'data' => $result,
            ]);
    }

    public function options()
    {
        return $this->response->setStatusCode(ResponseInterface::HTTP_NO_CONTENT);
    }
}
