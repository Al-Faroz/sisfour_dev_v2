<?php

namespace App\Controllers;

use App\Services\TahunAjaranService;

/**
 * MasterTahunAjaran
 *
 * Controller Master Tahun Ajaran.
 */
class MasterTahunAjaran extends BaseController
{
    protected TahunAjaranService $tahunService;

    public function __construct()
    {
        $this->tahunService = new TahunAjaranService();
    }

    public function index()
    {
        if ($this->isJsonRequest()) {
            return $this->response->setJSON([
                'status' => 'success',
                'data' => $this->tahunService->getList(),
            ]);
        }

        return $this->response->setBody(
            $this->renderWithLayout('master/tahun_ajaran', [
                'title' => 'Master Tahun Ajaran',
                'extraJs' => [
                    'assets/js/master/tahun-ajaran.js',
                ],
            ])
        );
    }

    public function create()
    {
        return $this->respondResult(
            $this->tahunService->create(
                $this->request->getPost()
            ),
            201
        );
    }

    public function update($id)
    {
        $payload = $this->request->getRawInput();

        if ($payload === []) {
            $json = $this->request->getJSON(true);
            $payload = is_array($json) ? $json : [];
        }

        return $this->respondResult(
            $this->tahunService->update(
                (int) $id,
                $payload
            )
        );
    }

    public function aktifkan($id)
    {
        return $this->respondResult(
            $this->tahunService->aktifkan((int) $id)
        );
    }

    public function delete($id)
    {
        return $this->respondResult(
            $this->tahunService->delete((int) $id)
        );
    }

    public function recycle()
    {
        if ($this->isJsonRequest()) {
            return $this->response->setJSON([
                'status' => 'success',
                'data' => $this->tahunService->getList(true),
            ]);
        }

        return $this->response->setBody(
            $this->renderWithLayout(
                'master/tahun_ajaran_recycle',
                [
                    'title' => 'Recycle Bin Tahun Ajaran',
                    'extraJs' => [
                        'assets/js/master/tahun-ajaran-recycle.js',
                    ],
                ]
            )
        );
    }

    public function restore($id)
    {
        return $this->respondResult(
            $this->tahunService->restore((int) $id)
        );
    }

    public function forceDelete($id)
    {
        return $this->respondResult(
            $this->tahunService->forceDelete((int) $id)
        );
    }

    protected function isJsonRequest(): bool
    {
        $path = trim(
            $this->request->getUri()->getPath(),
            '/'
        );

        return str_ends_with($path, '/json')
            || $this->request->isAJAX()
            || $this->request->getGet('format') === 'json';
    }

    protected function respondResult(
        array $result,
        int $successCode = 200
    ) {
        $success = (bool) ($result['success'] ?? false);

        return $this->response
            ->setStatusCode(
                $success ? $successCode : 422
            )
            ->setJSON([
                'status' => $success ? 'success' : 'error',
                'message' => $result['message']
                    ?? ($success ? 'Berhasil.' : 'Gagal.'),
                'data' => $result,
            ]);
    }
}
