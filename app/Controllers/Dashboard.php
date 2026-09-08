<?php

namespace App\Controllers;

use App\Services\DashboardService;

/**
 * Dashboard
 *
 * Controller tipis. Effective role, contextual Wali, permission widget,
 * dan agregasi data ditangani DashboardService.
 */
class Dashboard extends BaseController
{
    protected DashboardService $dashboardService;

    public function __construct()
    {
        $this->dashboardService = new DashboardService();
    }

    public function index()
    {
        $userId = (int) session()->get('user_id');
        $result = $this->dashboardService->build($userId);

        if ($this->request->getGet('format') === 'json' || $this->request->isAJAX()) {
            return $this->response->setJSON([
                'status' => 'success',
                'data' => $result,
            ]);
        }

        $view = match ($result['dashboard_role']) {
            'admin' => 'dashboard_admin',
            'operator' => 'dashboard_operator',
            'pimpinan' => 'dashboard_pimpinan',
            'bk' => 'dashboard_bk',
            'siswa' => 'dashboard_siswa',
            'guru' => $result['is_wali'] ? 'dashboard_wali' : 'dashboard_guru',
            default => 'dashboard_guru',
        };

        return $this->response->setBody(
            $this->renderWithLayout($view, [
                'widgets' => $result['widgets'],
                'effectiveRoles' => $result['roles'],
                'dashboardRole' => $result['dashboard_role'],
                'isWali' => $result['is_wali'],
            ])
        );
    }

    public function data()
    {
        return $this->index();
    }
}
