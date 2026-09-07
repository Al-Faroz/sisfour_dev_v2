<?php

namespace App\Controllers;

use App\Services\MataPelajaranService;

/**
 * MasterMapel
 *
 * Controller Master Mata Pelajaran.
 */
class MasterMapel extends BaseController
{
    protected MataPelajaranService $mapelService;

    public function __construct()
    {
        $this->mapelService = new MataPelajaranService();
    }

    public function index()
    {
        $filter = $this->filters();

        if ($this->isJsonRequest()) {
            return $this->response->setJSON([
                'status' => 'success',
                'data' => $this->mapelService->getList(
                    $filter
                ),
            ]);
        }

        return $this->response->setBody(
            $this->renderWithLayout(
                'master/mata_pelajaran',
                [
                    'title' => 'Master Mata Pelajaran',
                    'extraJs' => [
                        'assets/js/master/mata-pelajaran.js',
                    ],
                ]
            )
        );
    }

    public function create()
    {
        return $this->respondResult(
            $this->mapelService->create(
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
            $this->mapelService->update(
                (int) $id,
                $payload
            )
        );
    }

    public function delete($id)
    {
        return $this->respondResult(
            $this->mapelService->delete((int) $id)
        );
    }

    protected function filters(): array
    {
        return [
            'nama_mapel' => trim(
                (string) $this->request->getGet(
                    'nama_mapel'
                )
            ),
            'kode_mapel' => trim(
                (string) $this->request->getGet(
                    'kode_mapel'
                )
            ),
        ];
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
                $success
                    ? $successCode
                    : 422
            )
            ->setJSON([
                'status' => $success
                    ? 'success'
                    : 'error',
                'message' => $result['message']
                    ?? ($success
                        ? 'Berhasil.'
                        : 'Gagal.'),
                'data' => $result,
            ]);
    }
}
