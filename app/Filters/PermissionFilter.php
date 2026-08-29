<?php

namespace App\Filters;

use App\Services\AuthService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * PermissionFilter
 *
 * Dipasang di setiap route via ['filter' => 'permission:key'] atau
 * ['filter' => 'permission:key1,key2'] (OR — lolos jika punya salah satu).
 *
 * Alur (03_AUTH_RBAC_MENU §3.1):
 *  1. Baca permission_key dari argumen filter.
 *  2. Panggil AuthService::resolveScope().
 *  3. Jika TIDAK_ADA -> 403.
 *  4. Jika ada -> simpan scope hasil resolusi ke $request agar Controller/Model
 *     bisa memakainya (lewat PermissionService::buildConstraint()).
 *
 * Controller TIDAK BOLEH mengecek permission secara manual — semua di sini.
 */
class PermissionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (empty($arguments)) {
            // Route dipasang filter 'permission' tanpa key -> kesalahan konfigurasi, tolak demi aman
            return $this->forbidden($request, 'Permission belum dikonfigurasi untuk route ini.');
        }

        $permissionKeys = array_map('trim', $arguments);
        $isApi          = str_starts_with(ltrim($request->getPath(), '/'), 'api/');

        $userId = $isApi
            ? ($request->authUser['id'] ?? null)
            : session()->get('user_id');

        if (!$userId) {
            return $this->forbidden($request, 'Tidak terautentikasi.');
        }

        $authService = new AuthService();
        $result      = $authService->resolveScopeAny($permissionKeys, (int) $userId);

        if ($result['scope'] === 'TIDAK_ADA') {
            return $this->forbidden($request, 'Anda tidak memiliki akses untuk aksi ini.');
        }

        // Simpan hasil resolusi permission+scope agar dipakai Controller/Model berikutnya
        $request->resolvedPermission = $result['permission'];
        $request->resolvedScope      = $result['scope'];

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // no-op
    }

    protected function forbidden(RequestInterface $request, string $message)
    {
        $isApi     = str_starts_with(ltrim($request->getPath(), '/'), 'api/');
        $wantsJson = $isApi
            || str_contains($request->getPath(), '/json')
            || $request->getGet('format') === 'json';

        if ($wantsJson) {
            return service('response')
                ->setStatusCode(403)
                ->setJSON(['status' => 'error', 'message' => $message]);
        }

        return service('response')
            ->setStatusCode(403)
            ->setBody(view('errors/html/error_403', ['message' => $message]));
    }
}
