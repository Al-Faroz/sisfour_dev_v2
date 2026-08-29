<?php

namespace App\Controllers;

use App\Services\AuthService;
use CodeIgniter\HTTP\ResponseInterface;

class Auth extends BaseController
{
    protected AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    // ================================================================
    // WEB — Session
    // ================================================================

    public function login()
    {
        // Sudah login -> langsung ke dashboard
        if (session()->get('logged_in')) {
            return redirect()->to('/dashboard');
        }

        if (strtolower($this->request->getMethod()) === 'post') {
            $username = trim((string) $this->request->getPost('username'));
            $password = (string) $this->request->getPost('password');

            if ($username === '' || $password === '') {
                return redirect()->back()->withInput()->with('error', 'Username & password wajib diisi.');
            }

            $result = $this->authService->attemptLogin($username, $password, $this->request->getIPAddress());

            if (!$result['success']) {
                return redirect()->back()->withInput()->with('error', $result['message']);
            }

            return redirect()->to('/dashboard');
        }

        return view('auth_login');
    }

    public function logout()
    {
        $this->authService->logout();

        return redirect()->to('/auth/login')->with('message', 'Anda telah logout.');
    }

    // ================================================================
    // API — JWT (Mobile / Cordova)
    // ================================================================

    public function apiLogin()
    {
        $username   = trim((string) $this->request->getPost('username'));
        $password   = (string) $this->request->getPost('password');
        $deviceName = $this->request->getPost('device_name');

        if ($username === '' || $password === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'Username & password wajib diisi.',
            ]);
        }

        $result = $this->authService->apiLogin($username, $password, $this->request->getIPAddress(), $deviceName);

        $statusCode = $result['success'] ? 200 : 401;

        return $this->response->setStatusCode($statusCode)->setJSON([
            'status'  => $result['success'] ? 'success' : 'error',
            'message' => $result['message'],
            'data'    => $result['data'] ?? null,
        ]);
    }

    public function apiRefresh()
    {
        $refreshToken = (string) $this->request->getPost('refresh_token');

        if ($refreshToken === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'refresh_token wajib diisi.',
            ]);
        }

        $result = $this->authService->apiRefresh($refreshToken);

        $statusCode = $result['success'] ? 200 : 401;

        return $this->response->setStatusCode($statusCode)->setJSON([
            'status'  => $result['success'] ? 'success' : 'error',
            'message' => $result['message'],
            'data'    => $result['data'] ?? null,
        ]);
    }

    /**
     * Dilindungi filter auth:api -> $this->request->authUser sudah tersedia.
     */
    public function apiLogout()
    {
        $header = $this->request->getHeaderLine('Authorization');
        if (preg_match('/^Bearer\s+(.+)$/i', trim($header), $m)) {
            $this->authService->apiLogout(trim($m[1]));
        }

        return $this->response->setJSON(['status' => 'success', 'message' => 'Logout berhasil.']);
    }

    /**
     * Dilindungi filter auth:api.
     */
    public function apiMe()
    {
        $user = $this->request->authUser ?? null;

        if (!$user) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Unauthenticated.']);
        }

        unset($user['password']);

        return $this->response->setJSON(['status' => 'success', 'data' => $user]);
    }
}
