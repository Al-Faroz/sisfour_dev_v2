<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Database;

/**
 * MaintenanceFilter — Acuan: 08_DASHBOARD_SETTINGS_BACKUP §2.5
 * Semua role KECUALI Admin diarahkan ke halaman maintenance saat mode aktif.
 */
class MaintenanceFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $db = Database::connect();

        $setting = $db->table('setting_sistem')
            ->select('setting_value')
            ->where('setting_key', 'maintenance_mode')
            ->get()
            ->getRowArray();

        $isMaintenance = $setting && (int) $setting['setting_value'] === 1;

        if (!$isMaintenance) {
            return null;
        }

        // Admin tetap boleh mengakses aplikasi untuk mematikan mode maintenance.
        if (session()->get('role') === 'admin') {
            return null;
        }

        $message = $db->table('setting_sistem')
            ->select('setting_value')
            ->where('setting_key', 'maintenance_message')
            ->get()
            ->getRowArray();

        return service('response')->setStatusCode(503)->setBody(
            view('errors/html/maintenance', [
                'message' => $message['setting_value'] ?? 'Sistem sedang dalam pemeliharaan.',
            ])
        );
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Tidak ada aksi setelah response.
    }
}
