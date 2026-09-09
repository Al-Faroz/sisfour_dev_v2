<?php

namespace App\Controllers;

use App\Services\ManajemenSiswaService;

class ManajemenSiswa extends BaseController
{
    protected ManajemenSiswaService $service;

    public function __construct()
    {
        $this->service = new ManajemenSiswaService();
    }

    public function kelas()
    {
        $userId = (int) session()->get('user_id');

        if ($this->isJsonRequest()) {
            return $this->response->setJSON([
                'status' => 'success',
                'data' => $this->service->getPlacementList(
                    $userId,
                    [
                        'q' => trim((string) $this->request->getGet('q')),
                        'kelas' => trim((string) $this->request->getGet('kelas')),
                    ]
                ),
            ]);
        }

        return $this->response->setBody(
            $this->renderWithLayout('manajemen_siswa/kelas', [
                'title' => 'Penempatan / Pindah Kelas',
                'kelasOptions' => $this->service->getActiveClassOptions($userId),
                'tahunAktif' => $this->service->getActiveYearInfo($userId),
                'extraJs' => ['assets/js/manajemen_siswa/kelas.js'],
            ])
        );
    }

    public function setKelas($id)
    {
        return $this->respondResult(
            $this->service->setOrMoveClass(
                (int) session()->get('user_id'),
                (int) $id,
                (int) $this->request->getPost('id_kelas_tujuan')
            )
        );
    }

    public function kenaikan()
    {
        $userId = (int) session()->get('user_id');

        return $this->response->setBody(
            $this->renderWithLayout('manajemen_siswa/kenaikan', [
                'title' => 'Kenaikan Kelas',
                'sourceClasses' => $this->service->getKenaikanSourceClasses($userId),
                'tahunAktif' => $this->service->getActiveYearInfo($userId),
                'extraJs' => ['assets/js/manajemen_siswa/kenaikan.js'],
            ])
        );
    }

    public function processData($id)
    {
        return $this->respondResult(
            $this->service->getProcessData(
                (int) session()->get('user_id'),
                (int) $id
            )
        );
    }

    public function naik($id)
    {
        $selected = $this->request->getPost('id_siswa');
        $selected = is_array($selected) ? $selected : [];

        return $this->respondResult(
            $this->service->naikKelas(
                (int) session()->get('user_id'),
                (int) $id,
                (int) $this->request->getPost('id_kelas_tujuan'),
                (int) $this->request->getPost('id_tahun_baru'),
                $selected
            )
        );
    }

    public function mutasi()
    {
        $userId = (int) session()->get('user_id');

        if ($this->isJsonRequest()) {
            return $this->response->setJSON([
                'status' => 'success',
                'data' => $this->service->getMutasiList(
                    $userId,
                    [
                        'q' => trim((string) $this->request->getGet('q')),
                        'id_kelas' => (int) $this->request->getGet('id_kelas'),
                    ]
                ),
            ]);
        }

        return $this->response->setBody(
            $this->renderWithLayout('manajemen_siswa/mutasi', [
                'title' => 'Mutasi Siswa',
                'kelasOptions' => $this->service->getActiveClassOptions($userId),
                'tahunAktif' => $this->service->getActiveYearInfo($userId),
                'extraJs' => ['assets/js/manajemen_siswa/mutasi.js'],
            ])
        );
    }

    public function prosesMutasi($id)
    {
        return $this->respondResult(
            $this->service->mutasi(
                (int) session()->get('user_id'),
                (int) $id,
                trim((string) $this->request->getPost('status')),
                trim((string) $this->request->getPost('keterangan'))
            )
        );
    }

    public function kelulusan()
    {
        $userId = (int) session()->get('user_id');

        return $this->response->setBody(
            $this->renderWithLayout('manajemen_siswa/kelulusan', [
                'title' => 'Kelulusan Siswa',
                'sourceClasses' => $this->service->getKelulusanSourceClasses($userId),
                'tahunAktif' => $this->service->getActiveYearInfo($userId),
                'extraJs' => ['assets/js/manajemen_siswa/kelulusan.js'],
            ])
        );
    }

    public function lulus($id)
    {
        $selected = $this->request->getPost('id_siswa');
        $selected = is_array($selected) ? $selected : [];

        return $this->respondResult(
            $this->service->lulus(
                (int) session()->get('user_id'),
                (int) $id,
                $selected
            )
        );
    }

    private function isJsonRequest(): bool
    {
        $path = trim($this->request->getUri()->getPath(), '/');

        return str_ends_with($path, '/json')
            || $this->request->isAJAX()
            || $this->request->getGet('format') === 'json';
    }

    private function respondResult(array $result, int $successCode = 200)
    {
        $success = (bool) ($result['success'] ?? false);

        return $this->response
            ->setStatusCode($success ? $successCode : 422)
            ->setJSON([
                'status' => $success ? 'success' : 'error',
                'message' => $result['message'] ?? ($success ? 'Berhasil.' : 'Gagal.'),
                'data' => $result,
            ]);
    }
}
