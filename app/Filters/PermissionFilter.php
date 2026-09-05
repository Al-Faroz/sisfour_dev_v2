<?php

namespace App\Filters;

use App\Services\AuthService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * PermissionFilter
 *
 * Mengecek permission_key pada route.
 *
 * Contoh:
 *
 * permission:master_guru.manage
 *
 * atau:
 *
 * permission:master_guru.manage,master_guru.view
 *
 * Beberapa permission berarti OR.
 */
class PermissionFilter implements FilterInterface
{
    public function before(
        RequestInterface $request,
        $arguments = null
    ) {
        /*
         * AuthFilter seharusnya sudah dijalankan oleh
         * parent route group.
         *
         * Tetap cek session di sini sebagai defensive check.
         */
        if (session()->get('logged_in') !== true) {
            return redirect()->to('/auth/login');
        }

        /*
         * Route wajib memiliki permission key.
         */
        if (empty($arguments)) {
            return service('response')
                ->setStatusCode(403)
                ->setBody(
                    'Permission key tidak dikonfigurasi pada route ini.'
                );
        }

        $userId = (int) session()->get('user_id');

        if ($userId <= 0) {
            return service('response')
                ->setStatusCode(403)
                ->setBody(
                    'Identitas pengguna tidak valid.'
                );
        }

        $authService = new AuthService();

        $matchedPermission = null;
        $resolvedScope = 'TIDAK_ADA';

        /*
         * Permission OR:
         *
         * permission:a,b,c
         *
         * user cukup memiliki salah satunya.
         */
        foreach ($arguments as $permissionKey) {
            $permissionKey = trim((string) $permissionKey);

            if ($permissionKey === '') {
                continue;
            }

            $scope = $authService->resolveScope(
                $permissionKey,
                $userId
            );

            if ($scope !== 'TIDAK_ADA') {
                $matchedPermission = $permissionKey;
                $resolvedScope = $scope;
                break;
            }
        }

        /*
         * Tidak memiliki permission.
         */
        if ($matchedPermission === null) {
            log_message(
                'warning',
                'Akses ditolak: user_id={userId}, permissions=[{permissions}]',
                [
                    'userId' => $userId,
                    'permissions' => implode(',', $arguments),
                ]
            );

            return service('response')
                ->setStatusCode(403)
                ->setBody(
                    'Anda tidak memiliki akses ke halaman ini.'
                );
        }

        /*
         * Simpan hasil resolusi permission.
         *
         * Controller/Service nantinya dapat membaca:
         *
         * $request->permission
         */
        $request->permission = [
            'key' => $matchedPermission,
            'scope' => $resolvedScope,
        ];

        return null;
    }

    public function after(
        RequestInterface $request,
        ResponseInterface $response,
        $arguments = null
    ) {
        // Tidak ada aksi.
    }
}
