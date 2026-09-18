<?php

namespace App\Controllers;

use App\Models\SettingSistemModel;
use App\Services\PtspService;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

class PtspPublic extends BaseController
{
    protected PtspService $service;

    public function __construct()
    {
        $this->service = new PtspService();
    }

    public function index()
    {
        return $this->response->setBody(view('ptsp/public', [
            'pageTitle' => 'PTSP',
            'systemSettings' => $this->publicSettings(),
            'options' => $this->service->publicOptions(),
            'extraJs' => ['assets/js/ptsp/public.js'],
            'extraCss' => ['assets/css/ptsp-public.css'],
        ]));
    }

    public function layanan()
    {
        return $this->respond($this->service->submitLayanan($this->request->getPost()));
    }

    public function polling()
    {
        return $this->respond($this->service->submitPolling($this->request->getPost()));
    }

    public function pengaduan()
    {
        return $this->respond($this->service->submitPengaduan(
            $this->request->getPost(),
            $this->request->getFile('lampiran')
        ));
    }

    private function publicSettings(): array
    {
        $settings = [
            'nama_sekolah' => 'MTsN 4 Jombang',
            'alamat_sekolah' => '',
            'logo_sekolah' => '',
            'icon_sekolah' => '',
        ];

        try {
            $rows = (new SettingSistemModel())->allAssoc();
            foreach ($settings as $key => $default) {
                $value = trim((string) ($rows[$key]['setting_value'] ?? ''));
                if ($value !== '') {
                    $settings[$key] = $value;
                }
            }
        } catch (Throwable $e) {
            log_message('warning', 'PTSP public gagal memuat setting: {message}', ['message' => $e->getMessage()]);
        }

        return $settings;
    }

    private function respond(array $result)
    {
        $success = (bool) ($result['success'] ?? false);

        return $this->response
            ->setStatusCode($success ? ResponseInterface::HTTP_OK : ResponseInterface::HTTP_UNPROCESSABLE_ENTITY)
            ->setJSON([
                'status' => $success ? 'success' : 'error',
                'message' => $result['message'] ?? ($success ? 'Berhasil.' : 'Gagal.'),
                'data' => $result,
            ]);
    }
}
