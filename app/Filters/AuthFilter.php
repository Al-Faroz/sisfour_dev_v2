<?php

namespace App\Filters;

use App\Services\JwtService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Database;
use Throwable;

class AuthFilter implements FilterInterface
{
    public function before(
        RequestInterface $request,
        $arguments = null
    ) {
        $arguments = is_array($arguments) ? $arguments : [];

        if (in_array('api', $arguments, true)) {
            return $this->beforeApi($request);
        }

        return $this->beforeWeb();
    }

    public function after(
        RequestInterface $request,
        ResponseInterface $response,
        $arguments = null
    ) {
        return null;
    }

    private function beforeWeb()
    {
        if (session()->get('logged_in') !== true) {
            session()->setFlashdata(
                'error',
                'Silakan login terlebih dahulu.'
            );

            return redirect()->to('/auth/login');
        }

        $userId = (int) session()->get('user_id');

        if ($userId <= 0) {
            session()->destroy();

            return redirect()->to('/auth/login');
        }

        $sessionAuthVersion = session()->get('auth_version');

        if ($sessionAuthVersion === null) {
            session()->destroy();

            return redirect()
                ->to('/auth/login')
                ->with(
                    'error',
                    'Sesi Anda tidak valid. Silakan login kembali.'
                );
        }

        $db = Database::connect();

        $user = $db
            ->table('users')
            ->select('auth_version, status_aktif')
            ->where('id', $userId)
            ->get()
            ->getRowArray();

        if (
            !$user
            || (int) $user['status_aktif'] !== 1
            || (int) $user['auth_version'] !== (int) $sessionAuthVersion
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

    private function beforeApi(RequestInterface $request)
    {
        $token = $this->bearerToken($request);

        if ($token === '') {
            return service('response')
                ->setStatusCode(401)
                ->setContentType('application/json')
                ->setJSON([
                    'success' => false,
                    'message' => 'Bearer token wajib dikirim.',
                ]);
        }

        try {
            $validated = (new JwtService())->validateAccessToken($token);
        } catch (Throwable $e) {
            return service('response')
                ->setStatusCode(401)
                ->setContentType('application/json')
                ->setJSON([
                    'success' => false,
                    'message' => $e->getMessage(),
                ]);
        }

        $request->apiUser = $validated['user'];
        $request->apiAccessToken = $token;
        $request->apiTokenRow = $validated['token'];
        $request->apiClaims = $validated['claims'];

        return null;
    }

    private function bearerToken(RequestInterface $request): string
    {
        $authorization = trim($request->getHeaderLine('Authorization'));

        if (
            $authorization === ''
            || !preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)
        ) {
            return '';
        }

        return trim((string) ($matches[1] ?? ''));
    }
}
