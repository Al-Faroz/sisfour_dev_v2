<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class Api extends Controller
{
    public function version()
    {
        return $this->response
            ->setStatusCode(200)
            ->setJSON([
                'success' => true,
                'message' => 'Informasi versi server berhasil dimuat.',
                'data' => [
                    'product' => 'SisisFour',
                    'server_version' => '0.5',
                    'api_version' => 'v1',
                    'timezone' => 'Asia/Jakarta',
                ],
            ]);
    }
}
