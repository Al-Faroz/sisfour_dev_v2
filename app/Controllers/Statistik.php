<?php

namespace App\Controllers;

use App\Services\ActivityLogService;
use App\Services\StatistikPdfService;
use App\Services\StatistikService;
use CodeIgniter\HTTP\ResponseInterface;

class Statistik extends BaseController
{
    protected StatistikService $service;
    protected StatistikPdfService $pdfService;
    protected ActivityLogService $activityLog;

    public function __construct()
    {
        $this->service = new StatistikService();
        $this->pdfService = new StatistikPdfService();
        $this->activityLog = new ActivityLogService();
    }

    public function index()
    {
        $result = $this->service->page(
            $this->currentActorUserId(),
            $this->request->getGet()
        );

        if (! ($result['success'] ?? false)) {
            return $this->response
                ->setStatusCode($this->statusFor($result))
                ->setBody(
                    $this->renderWithLayout('statistik/index', [
                        'pageTitle' => 'Statistik',
                        'statistik' => $result,
                    ])
                );
        }

        return $this->response->setBody(
            $this->renderWithLayout('statistik/index', [
                'pageTitle' => 'Statistik',
                'statistik' => $result,
            ])
        );
    }

    public function data()
    {
        $result = $this->service->page(
            $this->currentActorUserId(),
            $this->request->getGet()
        );
        $success = (bool) ($result['success'] ?? false);

        return $this->response
            ->setStatusCode(
                $success ? ResponseInterface::HTTP_OK : $this->statusFor($result)
            )
            ->setJSON([
                'status' => $success ? 'success' : 'error',
                'message' => $result['message'] ?? null,
                'data' => $result,
            ]);
    }

    public function exportPdf()
    {
        $userId = $this->currentActorUserId();
        $input = strtoupper($this->request->getMethod()) === 'POST'
            ? $this->request->getPost()
            : $this->request->getGet();

        $result = $this->service->exportData(
            $userId,
            $input
        );

        if (! ($result['success'] ?? false)) {
            return $this->response
                ->setStatusCode($this->statusFor($result))
                ->setBody($result['message'] ?? 'Export Statistik gagal.');
        }

        $chartSvgs = [];
        if (strtoupper($this->request->getMethod()) === 'POST') {
            $rawCharts = (string) ($this->request->getPost('chart_svgs') ?? '');
            if ($rawCharts !== '') {
                $decoded = json_decode($rawCharts, true);
                $chartSvgs = is_array($decoded) ? $decoded : [];
            }
        }

        $pdf = $this->pdfService->generate($result, $chartSvgs);
        if (! ($pdf['success'] ?? false)) {
            return $this->response
                ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)
                ->setBody($pdf['message'] ?? 'PDF Statistik gagal dibentuk.');
        }

        $filters = $result['filters'] ?? [];
        $this->activityLog->write(
            $userId,
            'EXPORT',
            'Statistik',
            'Export PDF Statistik Tahun #' . (int) ($filters['id_tahun'] ?? 0)
            . ' · periode ' . (string) ($filters['periode'] ?? 'all')
        );

        return $this->response
            ->setContentType('application/pdf')
            ->setHeader(
                'Content-Disposition',
                'attachment; filename="' . $pdf['filename'] . '"'
            )
            ->setHeader('Cache-Control', 'private, no-store, max-age=0')
            ->setBody($pdf['binary']);
    }

    private function statusFor(array $result): int
    {
        return match ($result['code'] ?? '') {
            'FORBIDDEN' => ResponseInterface::HTTP_FORBIDDEN,
            'NO_PERIOD' => ResponseInterface::HTTP_UNPROCESSABLE_ENTITY,
            default => ResponseInterface::HTTP_UNPROCESSABLE_ENTITY,
        };
    }
}
