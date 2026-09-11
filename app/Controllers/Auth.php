<?php

namespace App\Controllers;

use App\Models\SettingSistemModel;
use App\Services\ActivityLogService;
use App\Services\AuthService;
use App\Services\JwtService;
use App\Support\RequestContext;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class Auth extends Controller
{
    protected AuthService $authService;
    protected JwtService $jwtService;
    protected ActivityLogService $activityLog;

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
        $this->activityLog = new ActivityLogService();
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
            return redirect()->to($this->webLandingPath(
                (int) session()->get('user_id'),
                session()->get('id_pegawai') !== null
                    ? (int) session()->get('id_pegawai')
                    : null
            ));
        }

        if (
            strtoupper(
                $this->request->getMethod()
            ) !== 'POST'
        ) {
            return view(
                'auth_login',
                [
                    'loginBranding' => $this->loginBranding(),
                ]
            );
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

        if (! $result['success']) {
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

        $this->activityLog->write(
            (int) $result['user']['id'],
            'LOGIN',
            'Auth',
            'Login Web berhasil.'
        );

        return redirect()->to(
            $this->webLandingPath(
                (int) $result['user']['id'],
                isset($result['user']['id_pegawai']) && $result['user']['id_pegawai'] !== null
                    ? (int) $result['user']['id_pegawai']
                    : null
            )
        );
    }

    /**
     * Logout web.
     *
     * Hanya POST.
     */
    public function logout()
    {
        $userId = (int) session()->get('user_id');

        if ($userId > 0) {
            $this->activityLog->write(
                $userId,
                'LOGOUT',
                'Auth',
                'Logout Web.'
            );
        }

        session()->destroy();

        return redirect()->to('/auth/login');
    }

    /**
     * POST /api/auth/login
     */
    public function apiLogin()
    {
        $data = $this->request->getJSON(true);

        if (! is_array($data)) {
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

        if (! $result['success']) {
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
        } catch (Throwable $e) {
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

        $this->activityLog->write(
            (int) $result['user']['id'],
            'LOGIN',
            'Auth',
            'Login API berhasil.'
        );

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
        $token = RequestContext::get(
            $this->request,
            'api_access_token'
        );

        $user = RequestContext::get(
            $this->request,
            'api_user'
        );

        if (! $token) {
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

        if (is_array($user) && ! empty($user['id'])) {
            $this->activityLog->write(
                (int) $user['id'],
                'LOGOUT',
                'Auth',
                'Logout API.'
            );
        }

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
        $user = RequestContext::get(
            $this->request,
            'api_user'
        );

        if (! $user) {
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

        if (! is_array($data)) {
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
        } catch (Throwable $e) {
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
     * Password dan field sensitif tidak pernah dikirim.
     */
    protected function publicUser(array $user): array
    {
        return [
            'id' => (int) $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
            'id_guru' => isset($user['id_guru'])
                ? (
                    $user['id_guru'] !== null
                        ? (int) $user['id_guru']
                        : null
                )
                : null,
            'id_siswa' => isset($user['id_siswa'])
                ? (
                    $user['id_siswa'] !== null
                        ? (int) $user['id_siswa']
                        : null
                )
                : null,
            'id_pegawai' => isset($user['id_pegawai'])
                ? (
                    $user['id_pegawai'] !== null
                        ? (int) $user['id_pegawai']
                        : null
                )
                : null,
        ];
    }

    /**
     * Akun Pegawai boleh dibuat sebelum role operasional ditentukan Admin.
     * Dalam kondisi tersebut landing page harus tetap dapat digunakan untuk
     * self-service Profile Pegawai dan tidak diarahkan ke Dashboard yang
     * membutuhkan permission dashboard.view.
     */
    private function webLandingPath(int $userId, ?int $idPegawai): string
    {
        if ($userId > 0 && $idPegawai !== null && $idPegawai > 0) {
            if ($this->authService->getUserRoles($userId) === []) {
                return '/profile/pegawai';
            }
        }

        return '/dashboard';
    }

    private function loginBranding(): array
    {
        $branding = [
            'nama_sekolah' => 'MTsN 4 Jombang',
            'logo_sekolah' => '',
            'icon_sekolah' => '',
        ];

        try {
            $rows = (new SettingSistemModel())->allAssoc();

            foreach ($branding as $key => $default) {
                if (! isset($rows[$key])) {
                    continue;
                }

                $value = trim(
                    (string) (
                        $rows[$key]['setting_value']
                        ?? ''
                    )
                );

                if ($value !== '') {
                    $branding[$key] = $value;
                }
            }
        } catch (Throwable $e) {
            log_message(
                'warning',
                'Login gagal memuat branding: {message}',
                ['message' => $e->getMessage()]
            );
        }

        return $branding;
    }
}
