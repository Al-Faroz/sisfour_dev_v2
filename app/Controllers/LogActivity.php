<?php

namespace App\Controllers;

use App\Services\ActivityLogService;
use App\Services\LogActivityService;
use CodeIgniter\HTTP\ResponseInterface;

class LogActivity extends BaseController
{
    protected LogActivityService $service;
    protected ActivityLogService $activityLog;

    public function __construct()
    {
        $this->service = new LogActivityService();
        $this->activityLog = new ActivityLogService();
    }

    public function index()
    {
        $result = $this->service->page(
            $this->request->getGet()
        );

        if ($this->wantsJson()) {
            return $this->respond($result);
        }

        return $this->response->setBody(
            $this->renderWithLayout(
                'log/activity',
                [
                    'title' => 'Log Activity',
                    'initial' => $result,
                    'extraJs' => [
                        'assets/js/log/activity.js',
                    ],
                ]
            )
        );
    }

    public function export()
    {
        $result = $this->service->export(
            $this->request->getGet()
        );

        if (!$result['success']) {
            return $this->respond($result);
        }

        $this->activityLog->write(
            (int) session()->get('user_id'),
            'EXPORT',
            'Log Activity',
            'Export Log Activity sebanyak '
                . (int) $result['count']
                . ' baris.'
        );

        return $this->response
            ->setStatusCode(200)
            ->setHeader(
                'Content-Type',
                'text/csv; charset=UTF-8'
            )
            ->setHeader(
                'Content-Disposition',
                'attachment; filename="'
                    . $result['filename']
                    . '"'
            )
            ->setHeader(
                'Cache-Control',
                'no-store, no-cache, must-revalidate'
            )
            ->setBody(
                $result['content']
            );
    }

    private function wantsJson(): bool
    {
        $path = rtrim(
            $this->request
                ->getUri()
                ->getPath(),
            '/'
        );

        return $this->request
                ->getGet('format') === 'json'
            || $this->request->isAJAX()
            || str_ends_with(
                $path,
                '/json'
            );
    }

    private function respond(array $result)
    {
        $success = (bool) (
            $result['success'] ?? false
        );

        return $this->response
            ->setStatusCode(
                $success
                    ? ResponseInterface::HTTP_OK
                    : ResponseInterface::HTTP_UNPROCESSABLE_ENTITY
            )
            ->setJSON([
                'status' => $success
                    ? 'success'
                    : 'error',
                'message' => $result['message']
                    ?? (
                        $success
                            ? 'Berhasil.'
                            : 'Gagal.'
                    ),
                'data' => $result,
            ]);
    }
}
