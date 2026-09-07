<?php

namespace App\Controllers;

use App\Services\KelasService;

/**
 * MasterKelas
 *
 * Controller Master Kelas.
 */
class MasterKelas extends BaseController
{
    protected KelasService $kelasService;

    public function __construct()
    {
        $this->kelasService = new KelasService();
    }

    public function index()
    {
        $filter = $this->filters();

        if ($this->isJsonRequest()) {
            return $this->response->setJSON([
                'status' => 'success',
                'data' => $this->kelasService->getList($filter),
            ]);
        }

        return $this->response->setBody(
            $this->renderWithLayout('master/kelas', [
                'title' => 'Master Kelas',
                'filters' => $filter,
                'tahunOptions' => $this->kelasService->getTahunOptions(),
                'extraJs' => ['assets/js/master/kelas.js'],
            ])
        );
    }

    public function create()
    {
        return $this->respondResult(
            $this->kelasService->create($this->request->getPost()),
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
            $this->kelasService->update((int) $id, $payload)
        );
    }

    public function delete($id)
    {
        return $this->respondResult(
            $this->kelasService->delete((int) $id)
        );
    }

    public function recycle()
    {
        if ($this->isJsonRequest()) {
            return $this->response->setJSON([
                'status' => 'success',
                'data' => $this->kelasService->getList(
                    $this->filters(),
                    true
                ),
            ]);
        }

        return $this->response->setBody(
            $this->renderWithLayout('master/kelas_recycle', [
                'title' => 'Recycle Bin Kelas',
                'extraJs' => ['assets/js/master/kelas-recycle.js'],
            ])
        );
    }

    public function restore($id)
    {
        return $this->respondResult(
            $this->kelasService->restore((int) $id)
        );
    }

    public function forceDelete($id)
    {
        return $this->respondResult(
            $this->kelasService->forceDelete((int) $id)
        );
    }

    public function anggota($id)
    {
        return $this->respondResult(
            $this->kelasService->getAnggotaData((int) $id)
        );
    }

    public function addAnggota($id)
    {
        return $this->respondResult(
            $this->kelasService->addAnggota(
                (int) $id,
                (int) $this->request->getPost('id_siswa')
            )
        );
    }

    public function removeAnggota($id, $idSiswa)
    {
        return $this->respondResult(
            $this->kelasService->removeAnggota(
                (int) $id,
                (int) $idSiswa
            )
        );
    }

    public function processData($id)
    {
        return $this->respondResult(
            $this->kelasService->getProcessData((int) $id)
        );
    }

    public function naik($id)
    {
        $selected = $this->request->getPost('id_siswa');
        $selected = is_array($selected) ? $selected : [];

        return $this->respondResult(
            $this->kelasService->naikKelas(
                (int) $id,
                (int) $this->request->getPost('id_kelas_tujuan'),
                (int) $this->request->getPost('id_tahun_baru'),
                $selected
            )
        );
    }

    public function lulus($id)
    {
        $selected = $this->request->getPost('id_siswa');
        $selected = is_array($selected) ? $selected : [];

        return $this->respondResult(
            $this->kelasService->luluskan(
                (int) $id,
                $selected
            )
        );
    }

    protected function filters(): array
    {
        return [
            'tingkat' => trim((string) $this->request->getGet('tingkat')),
            'id_tahun' => (int) $this->request->getGet('id_tahun'),
        ];
    }

    protected function isJsonRequest(): bool
    {
        $path = trim($this->request->getUri()->getPath(), '/');

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
            ->setStatusCode($success ? $successCode : 422)
            ->setJSON([
                'status' => $success ? 'success' : 'error',
                'message' => $result['message']
                    ?? ($success ? 'Berhasil.' : 'Gagal.'),
                'data' => $result,
            ]);
    }
}
