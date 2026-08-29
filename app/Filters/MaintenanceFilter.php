<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Database;

/**
 * MaintenanceFilter
 *
 * Global filter (before, semua route) — mengecek setting_sistem.maintenance_mode.
 * Semua role KECUALI Admin diarahkan ke halaman maintenance.
 *
 * Acuan: 08_DASHBOARD_SETTINGS_BACKUP §2.5
 */
class MaintenanceFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Jangan blokir route publik penting (login, api auth, verifikasi QR)
        $whitelist = ['auth/login', 'auth/logout', 'api/auth/login', 'api/auth/refresh', 'api/version'];
        $path      = ltrim($request->getPath(), '/');

        foreach ($whitelist as $allowed) {
            if (str_starts_with($path, $allowed)) {
                return null;
            }
        }

        $db  = Database::connect();
        $row = $db->table('setting_sistem')->where('setting_key', 'maintenance_mode')->get()->getRow();

        $maintenanceOn = $row && (string) $row->setting_value === '1';

        if (!$maintenanceOn) {
            return null;
        }

        // Admin tetap bisa login & bekerja meski maintenance ON
        if (session()->get('role') === 'admin') {
            return null;
        }

        // Cek juga untuk request API dengan role admin (JWT) — cek via authUser bila sudah lolos AuthFilter
        if (isset($request->authUser) && ($request->authUser['role'] ?? null) === 'admin') {
            return null;
        }

        $messageRow = $db->table('setting_sistem')->where('setting_key', 'maintenance_message')->get()->getRow();
        $message    = $messageRow->setting_value ?? 'Sistem sedang dalam pemeliharaan. Silakan coba beberapa saat lagi.';

        $isApi = str_starts_with($path, 'api/');

        if ($isApi || str_contains($path, '/json') || $request->getGet('format') === 'json') {
            return service('response')
                ->setStatusCode(503)
                ->setJSON(['status' => 'error', 'message' => $message]);
        }

        return service('response')
            ->setStatusCode(503)
            ->setBody(view('errors/html/error_maintenance', ['message' => $message]));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // no-op
    }
}
