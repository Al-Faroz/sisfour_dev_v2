<?php

namespace App\Filters;

use App\Services\AuthService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * AuthFilter
 *
 * - 'auth'      -> Cek session web aktif (database session) + auth_version.
 * - 'auth:api'  -> Cek JWT access token di header Authorization: Bearer.
 *
 * Konvensi argumen mengikuti Routes.php yang sudah ada:
 *   ['filter' => 'auth']       => $arguments = null
 *   ['filter' => 'auth:api']   => $arguments = ['api']
 *
 * Acuan: 03_AUTH_RBAC_MENU §1, §3.1; 16_MOBILE_CORDOVA §4
 */
class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $isApi = is_array($arguments) && in_array('api', $arguments, true);

        return $isApi
            ? $this->checkApi($request)
            : $this->checkWeb($request);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // no-op
    }

    // ----------------------------------------------------------------
    // WEB (Session)
    // ----------------------------------------------------------------

    protected function checkWeb(RequestInterface $request)
    {
        $authService = new AuthService();

        if (!$authService->isWebSessionValid()) {
            session()->destroy();

            if ($this->wantsJson($request)) {
                return service('response')
                    ->setStatusCode(401)
                    ->setJSON(['status' => 'error', 'message' => 'Sesi berakhir, silakan login kembali.']);
            }

            return redirect()->to('/auth/login')->with('error', 'Sesi berakhir, silakan login kembali.');
        }

        return null;
    }

    // ----------------------------------------------------------------
    // API (JWT)
    // ----------------------------------------------------------------

    protected function checkApi(RequestInterface $request)
    {
        $header = $request->getHeaderLine('Authorization');

        if (!preg_match('/^Bearer\s+(.+)$/i', trim($header), $matches)) {
            return $this->unauthorized('Token tidak ditemukan.');
        }

        $authService = new AuthService();
        $user        = $authService->verifyAccessToken(trim($matches[1]));

        if (!$user) {
            return $this->unauthorized('Session expired, please login again.');
        }

        // Simpan user hasil verifikasi JWT agar bisa dipakai PermissionFilter & Controller
        $request->authUser = $user;

        return null;
    }

    protected function unauthorized(string $message)
    {
        return service('response')
            ->setStatusCode(401)
            ->setJSON(['status' => 'error', 'message' => $message]);
    }

    protected function wantsJson(RequestInterface $request): bool
    {
        return str_contains($request->getPath(), '/json')
            || $request->getGet('format') === 'json'
            || str_contains($request->getHeaderLine('Accept'), 'application/json');
    }
}
