<?php

namespace App\Controllers;

use App\Services\JurnalExportService;
use App\Services\LaporanJurnalExceptionService;
use CodeIgniter\HTTP\ResponseInterface;

class LaporanJurnal extends BaseController
{
    protected LaporanJurnalExceptionService $service;
    protected JurnalExportService $exportService;

    public function __construct()
    {
        $this->service = new LaporanJurnalExceptionService();
        $this->exportService = new JurnalExportService();
    }

    public function index()
    {
        $userId = $this->currentActorUserId();
        $idTahun = (int) $this->request->getGet('id_tahun');
        $idJurnal = (int) $this->request->getGet('id_jurnal');
        $defaults = $this->service->defaultDates();

        if ($this->requestWantsJson()) {
            if ($idJurnal > 0) {
                return $this->respondService(
                    $this->service->getDetail($userId, $idJurnal)
                );
            }

            return $this->respondService(
                $this->service->getPaged(
                    $userId,
                    $this->request->getGet()
                )
            );
        }

        $options = $this->service->getOptions(
            $userId,
            $idTahun ?: null
        );

        return $this->response->setBody(
            $this->renderWithLayout('laporan/jurnal', [
                'title' => 'Laporan Jurnal Mengajar',
                'options' => $options,
                'selectedTahun' => $idTahun
                    ?: (int) ($options['selected_tahun'] ?? 0),
                'tanggalMulai' => trim(
                    (string) (
                        $this->request->getGet('tanggal_mulai')
                        ?: $defaults['tanggal_mulai']
                    )
                ),
                'tanggalSelesai' => trim(
                    (string) (
                        $this->request->getGet('tanggal_selesai')
                        ?: $defaults['tanggal_selesai']
                    )
                ),
                'extraJs' => ['assets/js/laporan/jurnal.js'],
            ])
        );
    }

    public function export()
    {
        $userId = $this->currentActorUserId();

        $result = $this->service->getExportData(
            $userId,
            $this->request->getGet()
        );

        if (!$result['success']) {
            return $this->respondService($result);
        }

        $file = $this->exportService->exportJurnal(
            $result,
            $userId
        );

        if (!($file['success'] ?? false)) {
            return $this->respondService($file);
        }

        return $this->downloadAndCleanup(
            (string) $file['path'],
            (string) $file['filename']
        );
    }

    private function downloadAndCleanup(string $path, string $filename)
    {
        register_shutdown_function(
            static function () use ($path): void {
                if (is_file($path)) {
                    @unlink($path);
                }
            }
        );

        return $this->response
            ->download($path, null)
            ->setFileName($filename);
    }

    private function respondService(array $result)
    {
        $success = (bool) ($result['success'] ?? false);

        return $this->response
            ->setStatusCode(
                $success
                    ? ResponseInterface::HTTP_OK
                    : $this->httpCode((string) ($result['code'] ?? ''))
            )
            ->setJSON([
                'status' => $success ? 'success' : 'error',
                'message' => $result['message']
                    ?? ($success ? 'Berhasil.' : 'Gagal.'),
                'data' => $result,
            ]);
    }

    private function httpCode(string $code): int
    {
        return match ($code) {
            'FORBIDDEN',
            'FORBIDDEN_VIEW',
            'NO_GURU_IDENTITY' => 403,
            'NOT_FOUND' => 404,
            default => 422,
        };
    }
}
