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
            'extraCss' => ['assets/css/ptsp-public.css'],
        ]));
    }

    public function layananForm()
    {
        return $this->formPage('layanan');
    }

    public function pengaduanForm()
    {
        return $this->formPage('pengaduan');
    }

    public function pollingForm()
    {
        return $this->formPage('polling');
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

    private function formPage(string $type)
    {
        if (! in_array($type, ['layanan', 'pengaduan', 'polling'], true)) {
            return $this->response->setStatusCode(404);
        }

        return $this->response->setBody(view('ptsp/public_form', [
            'pageTitle' => match ($type) {
                'layanan' => 'Layanan PTSP',
                'pengaduan' => 'Pengaduan PTSP',
                default => 'Polling Kepuasan PTSP',
            },
            'formType' => $type,
            'systemSettings' => $this->publicSettings(),
            'options' => $this->service->publicOptions(),
            'extraCss' => ['assets/css/ptsp-public.css'],
        ]));
    }

    private function publicSettings(): array
    {
        $settings = [
            'nama_sekolah' => 'MTsN 4 Jombang',
            'alamat_sekolah' => '',
            'logo_sekolah' => '',
            'icon_sekolah' => '',
            'ptsp_layanan_auto_print' => '0',
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
