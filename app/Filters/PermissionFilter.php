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
 * permission:master_guru.manage
 *
 * atau:
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
         * Tetap cek session sebagai defensive check.
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

        /*
         * Pastikan user_id valid.
         */
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
         * User cukup memiliki salah satunya.
         *
         * resolveScope() juga bertanggung jawab terhadap
         * validasi contextual scope seperti KELAS_DIAMPU.
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

            if ($scope === 'TIDAK_ADA') {
                continue;
            }

            $matchedPermission = $permissionKey;
            $resolvedScope = $scope;

            break;
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
         * Simpan hasil resolusi permission ke request.
         *
         * Controller/Service dapat membaca:
         *
         * $request->permission
         *
         * Scope tetap harus diterapkan oleh Model/Service
         * sesuai aturan masing-masing modul.
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
