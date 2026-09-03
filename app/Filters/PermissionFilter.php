<?php

namespace App\Filters;

use App\Services\AuthService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * PermissionFilter
 *
 * Dipasang di setiap grup route (master/, presensi/, laporan/, bk/, kartu/,
 * settings/, backup/, log/, profile/). Mengecek permission_key yang didefinisikan
 * di argumen filter route, resolve scope via AuthService::resolveScope(), dan
 * menyimpan scope hasil resolusi ke request agar bisa dipakai Model/Controller
 * (tanpa Controller melakukan cek permission manual).
 *
 * Penggunaan di Routes.php:
 *   $routes->get('master/guru', 'MasterGuru::index', ['filter' => 'permission:master_guru.manage,master_guru.view']);
 *
 * Acuan: 03_AUTH_RBAC_MENU §3.1
 */
class PermissionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (!session()->get('logged_in')) {
            return redirect()->to('/auth/login');
        }

        if (empty($arguments)) {
            // Route dipasangi filter tanpa permission_key -> anggap kesalahan config, tolak demi keamanan.
            return service('response')->setStatusCode(403)->setBody('Permission key tidak dikonfigurasi di route ini.');
        }

        $userId = (int) session()->get('user_id');
        $authService = new AuthService();

        // Boleh lebih dari 1 permission_key dipisah koma (OR) — misal:
        // 'master_guru.manage,master_guru.view' -> lolos jika salah satu ada.
        $resolvedScope = 'TIDAK_ADA';
        $matchedPermission = null;

        foreach ($arguments as $permissionKey) {
            $scope = $authService->resolveScope($permissionKey, $userId);
            if ($scope !== 'TIDAK_ADA') {
                $resolvedScope = $scope;
                $matchedPermission = $permissionKey;
                break;
            }
        }

        if ($matchedPermission === null) {
            log_message('warning', 'Akses ditolak: user_id={userId} tidak punya permission [{perms}]', [
                'userId' => $userId,
                'perms'  => implode(',', $arguments),
            ]);
            return service('response')->setStatusCode(403)->setBody('Anda tidak memiliki akses ke halaman ini.');
        }

        // Simpan hasil resolusi scope ke request agar Controller/Model bisa memakainya
        // tanpa perlu resolve ulang (dan tanpa perlu cek permission manual).
        $request->permission = [
            'key'   => $matchedPermission,
            'scope' => $resolvedScope,
        ];

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Tidak ada aksi setelah response.
    }
}
