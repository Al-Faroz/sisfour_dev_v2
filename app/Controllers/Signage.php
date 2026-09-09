<?php

namespace App\Controllers;

use App\Services\SignageService;

class Signage extends BaseController
{
    protected SignageService $service;

    public function __construct()
    {
        $this->service = new SignageService();
    }

    public function index()
    {
        return $this->response->setBody(
            view('signage/index', [
                'displayInfo' => $this->service->getDisplayInfo(),
            ])
        );
    }

    public function data()
    {
        $result = $this->service->getData();

        return $this->response
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->setJSON([
                'status' => 'success',
                'message' => $result['message'] ?? 'Data Digital Signage berhasil dimuat.',
                'data' => $result,
            ]);
    }
}
