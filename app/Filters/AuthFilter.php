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
            // DEBUG SEMENTARA — hapus setelah bug login selesai
            log_message('debug', 'AuthFilter: session logged_in kosong. session_id={sid}, cookie_ci_session={cookie}', [
                'sid' => session_id(),
                'cookie' => $_COOKIE['ci_session'] ?? '(tidak ada cookie ci_session terkirim)',
            ]);

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
            // DEBUG SEMENTARA — hapus setelah bug login selesai
            log_message('debug', 'AuthFilter: auth_version/status mismatch. user_id={uid}, db_auth_version={dbv}, session_auth_version={sv}, db_status_aktif={st}, user_row_ditemukan={found}', [
                'uid' => session()->get('user_id'),
                'dbv' => $user['auth_version'] ?? '(user tidak ditemukan)',
                'sv' => session()->get('auth_version'),
                'st' => $user['status_aktif'] ?? '-',
                'found' => $user ? 'ya' : 'tidak',
            ]);

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
