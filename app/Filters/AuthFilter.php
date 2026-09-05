<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Database;

class AuthFilter implements FilterInterface
{
    /**
     * Filter autentikasi session web.
     */
    public function before(
        RequestInterface $request,
        $arguments = null
    ) {
        /*
         * 1. Pastikan session login tersedia.
         */
        if (session()->get('logged_in') !== true) {
            session()->setFlashdata(
                'error',
                'Silakan login terlebih dahulu.'
            );

            return redirect()->to('/auth/login');
        }

        /*
         * 2. Pastikan user_id valid.
         */
        $userId = (int) session()->get('user_id');

        if ($userId <= 0) {
            session()->destroy();

            return redirect()->to('/auth/login');
        }

        /*
         * 3. Ambil status user dan auth_version terbaru.
         */
        $db = Database::connect();

        $user = $db
            ->table('users')
            ->select('auth_version, status_aktif')
            ->where('id', $userId)
            ->get()
            ->getRowArray();

        /*
         * 4. User tidak ada / nonaktif / auth_version berubah.
         */
        if (
            !$user
            || (int) $user['status_aktif'] !== 1
            || (int) $user['auth_version']
                !== (int) session()->get('auth_version')
        ) {
            session()->destroy();

            return redirect()
                ->to('/auth/login')
                ->with(
                    'error',
                    'Sesi Anda telah berakhir. Silakan login kembali.'
                );
        }

        return null;
    }

    public function after(
        RequestInterface $request,
        ResponseInterface $response,
        $arguments = null
    ) {
        // Tidak ada aksi setelah response.
    }
}
