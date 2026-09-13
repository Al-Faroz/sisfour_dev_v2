<?php

namespace App\Controllers;

use App\Services\MappingWaliIntegrityService;

/**
 * MappingWaliKelas
 *
 * Controller Master Mapping Wali Kelas.
 */
class MappingWaliKelas extends BaseController
{
    protected MappingWaliIntegrityService $mappingService;

    public function __construct()
    {
        $this->mappingService = new MappingWaliIntegrityService();
    }

    public function index()
    {
        $userId = (int) session()->get('user_id');
        $filter = $this->filters();

        if ($this->isJsonRequest()) {
            return $this->response->setJSON([
                'status' => 'success',
                'data' => $this->mappingService->getList(
                    $filter,
                    $userId
                ),
            ]);
        }

        return $this->response->setBody(
            $this->renderWithLayout(
                'master/mapping_wali_kelas',
                [
                    'title' => 'Mapping Wali Kelas',
                    'filters' => $filter,
                    'tahunOptions' => $this->mappingService
                        ->getTahunOptions($userId),
                    'kelasFilterOptions' => $this->mappingService
                        ->getKelasFilterOptions($userId),
                    'canManage' => $this->mappingService
                        ->canManage($userId),
                    'extraJs' => [
                        'assets/js/master/mapping-wali-kelas.js',
                    ],
                ]
            )
        );
    }

    public function options()
    {
        $userId = (int) session()->get('user_id');
        $idTahun = (int) $this->request
            ->getGet('id_tahun');

        return $this->respondResult(
            $this->mappingService
                ->getAssignOptions(
                    $idTahun,
                    $userId
                )
        );
    }

    public function assign()
    {
        $userId = (int) session()->get('user_id');

        return $this->respondResult(
            $this->mappingService->assign(
                (int) $this->request->getPost('id_guru'),
                (int) $this->request->getPost('id_kelas'),
                (int) $this->request->getPost('id_tahun'),
                $userId
            ),
            201
        );
    }

    public function delete($id)
    {
        $userId = (int) session()->get('user_id');

        return $this->respondResult(
            $this->mappingService->delete(
                (int) $id,
                $userId
            )
        );
    }

    public function recycle()
    {
        $userId = (int) session()->get('user_id');

        if ($this->isJsonRequest()) {
            return $this->response->setJSON([
                'status' => 'success',
                'data' => $this->mappingService->getList(
                    $this->filters(),
                    $userId,
                    true
                ),
            ]);
        }

        return $this->response->setBody(
            $this->renderWithLayout(
                'master/mapping_wali_kelas_recycle',
                [
                    'title' => 'Recycle Bin Mapping Wali Kelas',
                    'extraJs' => [
                        'assets/js/master/mapping-wali-kelas-recycle.js',
                    ],
                ]
            )
        );
    }

    public function restore($id)
    {
        $userId = (int) session()->get('user_id');

        return $this->respondResult(
            $this->mappingService->restore(
                (int) $id,
                $userId
            )
        );
    }

    public function forceDelete($id)
    {
        $userId = (int) session()->get('user_id');

        return $this->respondResult(
            $this->mappingService->forceDelete(
                (int) $id,
                $userId
            )
        );
    }

    protected function filters(): array
    {
        return [
            'id_tahun' => (int) $this->request
                ->getGet('id_tahun'),
            'guru' => trim(
                (string) $this->request
                    ->getGet('guru')
            ),
            'id_kelas' => (int) $this->request
                ->getGet('id_kelas'),
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
                $success ? $successCode : 422
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
