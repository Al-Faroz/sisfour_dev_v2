<?php

namespace App\Filters;

use App\Services\JwtService;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Database;
use Throwable;

class MaintenanceFilter implements FilterInterface
{
    private const DEFAULT_MESSAGE =
        'Sistem sedang dalam pemeliharaan. Silakan coba beberapa saat lagi.';

    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function before(RequestInterface $request, $arguments = null)
    {
        $settings = $this->maintenanceSettings();

        if (!$settings['enabled']) {
            return null;
        }

        $path = trim($request->getUri()->getPath(), '/');
        $method = strtoupper($request->getMethod());

        if ($this->isExactLogoutRoute($path, $method)) {
            return null;
        }

        if ($this->isWebLoginPage($path, $method)) {
            return null;
        }

        if ($this->isLoginAttempt($path, $method)) {
            $username = $this->extractUsername($request);

            if (
                $username !== ''
                && $this->usernameIsEffectiveAdmin($username)
            ) {
                return null;
            }

            return $this->maintenanceResponse(
                $request,
                $settings['message']
            );
        }

        /*
         * Authentication mode ditentukan dari path, bukan dari header
         * Accept / X-Requested-With.
         *
         * Request Web AJAX tetap memakai session Web. Sebaliknya endpoint
         * /api/... tetap wajib menggunakan Bearer token.
         */
        if ($this->isApiPath($request)) {
            $apiUserId = $this->validatedApiUserId($request);

            if (
                $apiUserId > 0
                && $this->userIsEffectiveAdmin($apiUserId)
            ) {
                return null;
            }

            return $this->maintenanceResponse(
                $request,
                $settings['message']
            );
        }

        $userId = (int) session()->get('user_id');

        if (
            session()->get('logged_in')
            && $userId > 0
            && $this->userIsEffectiveAdmin($userId)
        ) {
            return null;
        }

        return $this->maintenanceResponse(
            $request,
            $settings['message']
        );
    }

    public function after(
        RequestInterface $request,
        ResponseInterface $response,
        $arguments = null
    ) {
        return null;
    }

    private function maintenanceSettings(): array
    {
        try {
            $rows = $this->db
                ->table('setting_sistem')
                ->select('setting_key, setting_value')
                ->whereIn(
                    'setting_key',
                    ['maintenance_mode', 'maintenance_message']
                )
                ->get()
                ->getResultArray();
        } catch (Throwable $e) {
            log_message(
                'error',
                'MaintenanceFilter gagal membaca setting: {message}',
                ['message' => $e->getMessage()]
            );

            return [
                'enabled' => false,
                'message' => self::DEFAULT_MESSAGE,
            ];
        }

        $settings = [
            'maintenance_mode' => '0',
            'maintenance_message' => self::DEFAULT_MESSAGE,
        ];

        foreach ($rows as $row) {
            $key = (string) ($row['setting_key'] ?? '');

            if (array_key_exists($key, $settings)) {
                $settings[$key] = (string) ($row['setting_value'] ?? '');
            }
        }

        $message = trim($settings['maintenance_message']);

        return [
            'enabled' => in_array(
                strtolower(trim($settings['maintenance_mode'])),
                ['1', 'true', 'on', 'yes'],
                true
            ),
            'message' => $message !== ''
                ? $message
                : self::DEFAULT_MESSAGE,
        ];
    }

    private function isWebLoginPage(string $path, string $method): bool
    {
        if ($method !== 'GET') {
            return false;
        }

        return $path === ''
            || $path === 'auth/login';
    }

    private function isLoginAttempt(string $path, string $method): bool
    {
        if ($method !== 'POST') {
            return false;
        }

        return $path === 'auth/login'
            || $path === 'api/auth/login';
    }

    private function isExactLogoutRoute(string $path, string $method): bool
    {
        if ($method !== 'POST') {
            return false;
        }

        return $path === 'auth/logout'
            || $path === 'api/auth/logout';
    }

    private function extractUsername(RequestInterface $request): string
    {
        $username = trim((string) $request->getPost('username'));

        if ($username !== '') {
            return $username;
        }

        try {
            $json = $request->getJSON(true);
        } catch (Throwable $e) {
            $json = null;
        }

        if (is_array($json)) {
            return trim((string) ($json['username'] ?? ''));
        }

        return '';
    }

    private function validatedApiUserId(RequestInterface $request): int
    {
        $authorization = trim($request->getHeaderLine('Authorization'));

        if (
            $authorization === ''
            || !preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)
        ) {
            return 0;
        }

        $token = trim((string) ($matches[1] ?? ''));

        if ($token === '') {
            return 0;
        }

        try {
            $validated = (new JwtService())->validateAccessToken($token);
        } catch (Throwable $e) {
            return 0;
        }

        return (int) ($validated['user']['id'] ?? 0);
    }

    private function usernameIsEffectiveAdmin(string $username): bool
    {
        $row = $this->db
            ->table('users')
            ->select('id')
            ->where('username', $username)
            ->where('status_aktif', 1)
            ->get()
            ->getRowArray();

        if (!$row) {
            return false;
        }

        return $this->userIsEffectiveAdmin((int) $row['id']);
    }

    private function userIsEffectiveAdmin(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        $primary = $this->db
            ->table('users')
            ->select('role, status_aktif')
            ->where('id', $userId)
            ->get()
            ->getRowArray();

        if (!$primary || (int) ($primary['status_aktif'] ?? 0) !== 1) {
            return false;
        }

        if (($primary['role'] ?? null) === 'admin') {
            return true;
        }

        return $this->db
            ->table('user_roles')
            ->where('id_user', $userId)
            ->where('role', 'admin')
            ->countAllResults() > 0;
    }

    private function maintenanceResponse(
        RequestInterface $request,
        string $message
    ): ResponseInterface {
        $response = service('response')
            ->setStatusCode(ResponseInterface::HTTP_SERVICE_UNAVAILABLE)
            ->setHeader('Retry-After', '300')
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate');

        if ($this->wantsJsonResponse($request)) {
            return $response
                ->setContentType('application/json')
                ->setJSON([
                    'success' => false,
                    'code' => 'MAINTENANCE',
                    'message' => $message,
                ]);
        }

        return $response
            ->setContentType('text/html')
            ->setBody(
                view('errors/html/maintenance', [
                    'message' => $message,
                ])
            );
    }

    private function isApiPath(RequestInterface $request): bool
    {
        $path = trim($request->getUri()->getPath(), '/');

        return $path === 'api'
            || str_starts_with($path, 'api/');
    }

    private function wantsJsonResponse(RequestInterface $request): bool
    {
        if ($this->isApiPath($request)) {
            return true;
        }

        $accept = strtolower($request->getHeaderLine('Accept'));

        return str_contains($accept, 'application/json')
            || $request->isAJAX();
    }
}
