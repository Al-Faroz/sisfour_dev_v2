<?php

namespace App\Controllers;

use App\Services\ExportService;
use App\Services\LaporanPresensiService;
use CodeIgniter\HTTP\ResponseInterface;

class LaporanPresensi extends BaseController
{
    protected LaporanPresensiService $service;
    protected ExportService $exportService;

    public function __construct()
    {
        $this->service = new LaporanPresensiService();
        $this->exportService = new ExportService();
    }

    public function matrix()
    {
        $userId = (int) session()->get('user_id');
        $idTahun = (int) $this->request->getGet('id_tahun');
        $idKelas = (int) $this->request->getGet('id_kelas');
        $bulan = trim((string) ($this->request->getGet('bulan') ?: $this->service->defaultMonth()));

        if ($this->wantsJson()) {
            if ($idTahun <= 0 || $idKelas <= 0) {
                return $this->respondService(
                    $this->service->getFilterOptions($userId, $idTahun ?: null)
                );
            }

            return $this->respondService(
                $this->service->getMatrix(
                    $userId,
                    $idTahun,
                    $idKelas,
                    $bulan
                )
            );
        }

        $options = $this->service->getFilterOptions($userId, $idTahun ?: null);

        return $this->response->setBody(
            $this->renderWithLayout('laporan/presensi_matrix', [
                'title' => 'Matrix Presensi',
                'options' => $options,
                'selectedTahun' => $idTahun ?: (int) ($options['selected_tahun'] ?? 0),
                'selectedKelas' => $idKelas,
                'bulan' => $bulan,
                'extraJs' => ['assets/js/laporan/presensi-matrix.js'],
            ])
        );
    }

    public function export()
    {
        $userId = (int) session()->get('user_id');
        $idTahun = (int) $this->request->getGet('id_tahun');

        $options = $this->service->getFilterOptions($userId, $idTahun ?: null);

        return $this->response->setBody(
            $this->renderWithLayout('laporan/presensi_export', [
                'title' => 'Export Presensi',
                'options' => $options,
                'selectedTahun' => $idTahun ?: (int) ($options['selected_tahun'] ?? 0),
                'bulan' => trim((string) ($this->request->getGet('bulan') ?: $this->service->defaultMonth())),
                'extraJs' => ['assets/js/laporan/presensi-export.js'],
            ])
        );
    }

    public function exportBulan()
    {
        $userId = (int) session()->get('user_id');
        $result = $this->service->getMonthlyExportData(
            $userId,
            (int) $this->request->getGet('id_tahun'),
            (int) $this->request->getGet('id_kelas'),
            trim((string) $this->request->getGet('bulan'))
        );

        if (!$result['success']) {
            return $this->respondService($result);
        }

        $file = $this->exportService->exportPresensiBulanan($result, $userId);

        return $this->downloadResult($file);
    }

    public function exportSemester()
    {
        $userId = (int) session()->get('user_id');
        $result = $this->service->getSemesterExportData(
            $userId,
            (int) $this->request->getGet('id_tahun'),
            (int) $this->request->getGet('id_kelas')
        );

        if (!$result['success']) {
            return $this->respondService($result);
        }

        $file = $this->exportService->exportPresensiSemester($result, $userId);

        return $this->downloadResult($file);
    }

    private function downloadResult(array $file)
    {
        if (!($file['success'] ?? false)) {
            return $this->respondService($file);
        }

        return $this->response
            ->download($file['path'], null)
            ->setFileName($file['filename']);
    }

    private function wantsJson(): bool
    {
        $path = rtrim($this->request->getUri()->getPath(), '/');

        return $this->request->getGet('format') === 'json'
            || $this->request->isAJAX()
            || str_ends_with($path, '/json');
    }

    private function respondService(array $result)
    {
        $success = (bool) ($result['success'] ?? false);

        return $this->response
            ->setStatusCode($success ? ResponseInterface::HTTP_OK : $this->httpCode($result['code'] ?? ''))
            ->setJSON([
                'status' => $success ? 'success' : 'error',
                'message' => $result['message'] ?? ($success ? 'Berhasil.' : 'Gagal.'),
                'data' => $result,
            ]);
    }

    private function httpCode(string $code): int
    {
        return match ($code) {
            'FORBIDDEN', 'FORBIDDEN_VIEW', 'OUTSIDE_WALI_YEAR' => 403,
            default => 422,
        };
    }
}
