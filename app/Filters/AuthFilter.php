<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Database;

/**
 * AuthFilter — Session-based (web). Acuan: 03_AUTH_RBAC_MENU §1.1, §1.2
 */
class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (!session()->get('logged_in')) {
            session()->setFlashdata('error', 'Silakan login terlebih dahulu.');
            return redirect()->to('/auth/login');
        }

        // Single Active Session: cek auth_version session vs database
        $db = Database::connect();
        $user = $db->table('users')
            ->select('auth_version, status_aktif')
            ->where('id', session()->get('user_id'))
            ->get()
            ->getRowArray();

        if (!$user || (int) $user['status_aktif'] !== 1 || (int) $user['auth_version'] !== (int) session()->get('auth_version')) {
            session()->destroy();
            session()->setFlashdata('error', 'Sesi Anda telah berakhir (login di perangkat lain atau akun dinonaktifkan). Silakan login ulang.');
            return redirect()->to('/auth/login');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Tidak ada aksi setelah response untuk filter ini.
    }
}
