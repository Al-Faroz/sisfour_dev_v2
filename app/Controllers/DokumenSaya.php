<?php

namespace App\Controllers;

use App\Services\DokumenSiswaService;

class DokumenSaya extends BaseController
{
    protected DokumenSiswaService $service;

    public function __construct()
    {
        $this->service = new DokumenSiswaService();
    }

    public function index()
    {
        $userId = $this->currentActorUserId();
        $data = $this->service->selfPage(
            $userId,
            $this->request->getGet()
        );

        if ($this->requestWantsJson()) {
            $success = (bool) ($data['success'] ?? false);
            return $this->response
                ->setStatusCode($success ? 200 : 422)
                ->setJSON([
                    'status' => $success ? 'success' : 'error',
                    'message' => $data['message']
                        ?? ($success ? 'Berhasil.' : 'Gagal.'),
                    'data' => $data,
                ]);
        }

        return $this->response->setBody(
            $this->renderWithLayout('dokumen_siswa/self', [
                'title' => 'Dokumen Saya',
                'initial' => $data,
            ])
        );
    }

    public function open($id)
    {
        $result = $this->service->openSelf(
            $this->currentActorUserId(),
            (int) $id
        );

        if (! ($result['success'] ?? false)) {
            return redirect()
                ->to(base_url('dokumen-saya'))
                ->with('error', $result['message'] ?? 'Dokumen tidak dapat dibuka.');
        }

        return redirect()->to((string) $result['link']);
    }
}
