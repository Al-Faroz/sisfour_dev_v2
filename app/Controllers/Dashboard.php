<?php

namespace App\Controllers;

use App\Services\RoleAwareDashboardService;

/**
 * Dashboard
 *
 * Controller tipis. Effective role, contextual Wali, permission widget,
 * dan agregasi data ditangani DashboardService.
 */
class Dashboard extends BaseController
{
    protected RoleAwareDashboardService $dashboardService;

    public function __construct()
    {
        $this->dashboardService = new RoleAwareDashboardService();
    }

    public function index()
    {
        $userId = $this->currentActorUserId();
        $result = $this->dashboardService->build($userId);

        if ($this->requestWantsJson()) {
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
