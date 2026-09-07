<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\JwtService;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class Auth extends Controller
{
    protected AuthService $authService;

    protected JwtService $jwtService;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController(
            $request,
            $response,
            $logger
        );

        $this->authService = new AuthService();
        $this->jwtService = new JwtService();
    }

    /**
     * Login web.
     *
     * GET  /auth/login -> tampilkan form
     * POST /auth/login -> proses login
     */
    public function login()
    {
        if (session()->get('logged_in') === true) {
            return redirect()->to('/dashboard');
        }

        if (
            strtoupper(
                $this->request->getMethod()
            ) !== 'POST'
        ) {
            return view('auth_login');
        }

        $username = trim(
            (string) $this->request->getPost('username')
        );

        $password = (string) $this->request->getPost('password');

        if ($username !== '') {
            session()->setFlashdata(
                'login_username',
                $username
            );
        }

        $result = $this->authService->attemptLogin(
            $username,
            $password
        );

        if (!$result['success']) {
            session()->setFlashdata(
                'error',
                $result['message']
            );

            return redirect()->to('/auth/login');
        }

        session()->regenerate(true);

        $this->authService->setUserSession(
            $result['user']
        );

        return redirect()->to('/dashboard');
    }

    /**
     * Logout web.
     *
     * Hanya POST.
     */
    public function logout()
    {
        session()->destroy();

        return redirect()->to('/auth/login');
    }

    /**
     * POST /api/auth/login
     */
    public function apiLogin()
    {
        $data = $this->request->getJSON(true);

        if (!is_array($data)) {
            $data = $this->request->getPost();
        }

        $username = trim(
            (string) ($data['username'] ?? '')
        );

        $password = (string) ($data['password'] ?? '');

        $deviceName = isset($data['device_name'])
            ? trim((string) $data['device_name'])
            : null;

        if ($username === '' || $password === '') {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'success' => false,
                    'message' => 'Username dan password wajib diisi.',
                ]);
        }

        $result = $this->authService->attemptLogin(
            $username,
            $password
        );

        if (!$result['success']) {
            return $this->response
                ->setStatusCode(401)
                ->setJSON([
                    'success' => false,
                    'message' => $result['message'],
                ]);
        }

        try {
            $tokens = $this->jwtService->issueTokens(
                $result['user'],
                $deviceName
            );
        } catch (\Throwable $e) {
            log_message(
                'error',
                'API login token error: {message}',
                [
                    'message' => $e->getMessage(),
                ]
            );

            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'success' => false,
                    'message' => 'Login berhasil tetapi token gagal dibuat.',
                ]);
        }

        return $this->response
            ->setStatusCode(200)
            ->setJSON([
                'success' => true,
                'message' => 'Login berhasil.',
                'data' => [
                    'user' => $this->publicUser(
                        $result['user']
                    ),
                    'access_token' => $tokens['access_token'],
                    'refresh_token' => $tokens['refresh_token'],
                    'token_type' => 'Bearer',
                    'expires_in' => $tokens['expires_in'],
                    'refresh_expires_in' => $tokens['refresh_expires_in'],
                ],
            ]);
    }

    /**
     * POST /api/auth/logout
     *
     * Membatalkan access token yang sedang digunakan.
     */
    public function apiLogout()
    {
        $token = $this->request->apiAccessToken ?? null;

        if (!$token) {
            return $this->response
                ->setStatusCode(401)
                ->setJSON([
                    'success' => false,
                    'message' => 'Token tidak ditemukan.',
                ]);
        }

        $this->jwtService->revokeAccessToken(
            (string) $token
        );

        return $this->response
            ->setStatusCode(200)
            ->setJSON([
                'success' => true,
                'message' => 'Logout berhasil.',
            ]);
    }

    /**
     * GET /api/auth/me
     */
    public function apiMe()
    {
        $user = $this->request->apiUser ?? null;

        if (!$user) {
            return $this->response
                ->setStatusCode(401)
                ->setJSON([
                    'success' => false,
                    'message' => 'User tidak terautentikasi.',
                ]);
        }

        return $this->response
            ->setStatusCode(200)
            ->setJSON([
                'success' => true,
                'data' => [
                    'user' => $this->publicUser($user),
                ],
            ]);
    }

    /**
     * POST /api/auth/refresh
     */
    public function apiRefresh()
    {
        $data = $this->request->getJSON(true);

        if (!is_array($data)) {
            $data = $this->request->getPost();
        }

        $refreshToken = trim(
            (string) ($data['refresh_token'] ?? '')
        );

        $deviceName = isset($data['device_name'])
            ? trim((string) $data['device_name'])
            : null;

        if ($refreshToken === '') {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'success' => false,
                    'message' => 'Refresh token wajib diisi.',
                ]);
        }

        try {
            $tokens = $this->jwtService->refresh(
                $refreshToken,
                $deviceName
            );
        } catch (\Throwable $e) {
            return $this->response
                ->setStatusCode(401)
                ->setJSON([
                    'success' => false,
                    'message' => $e->getMessage(),
                ]);
        }

        return $this->response
            ->setStatusCode(200)
            ->setJSON([
                'success' => true,
                'message' => 'Token berhasil diperbarui.',
                'data' => [
                    'access_token' => $tokens['access_token'],
                    'refresh_token' => $tokens['refresh_token'],
                    'token_type' => 'Bearer',
                    'expires_in' => $tokens['expires_in'],
                    'refresh_expires_in' => $tokens['refresh_expires_in'],
                ],
            ]);
    }

    /**
     * Data user yang aman dikirim ke client.
     *
     * Password dan field sensitif tidak pernah dikirim.
     */
    protected function publicUser(array $user): array
    {
        return [
            'id' => (int) $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
            'id_guru' => isset($user['id_guru'])
                ? ($user['id_guru'] !== null
                    ? (int) $user['id_guru']
                    : null)
                : null,
            'id_siswa' => isset($user['id_siswa'])
                ? ($user['id_siswa'] !== null
                    ? (int) $user['id_siswa']
                    : null)
                : null,
            'id_pegawai' => isset($user['id_pegawai'])
                ? ($user['id_pegawai'] !== null
                    ? (int) $user['id_pegawai']
                    : null)
                : null,
        ];
    }
}
