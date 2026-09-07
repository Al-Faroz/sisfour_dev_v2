<?php

namespace App\Filters;

use App\Services\JwtService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ApiAuthFilter implements FilterInterface
{
    public function before(
        RequestInterface $request,
        $arguments = null
    ) {
        $header = $request->getHeaderLine('Authorization');

        if ($header === '') {
            return $this->unauthorized(
                'Authorization Bearer token wajib diisi.'
            );
        }

        if (
            !preg_match(
                '/^Bearer\s+(.+)$/i',
                trim($header),
                $matches
            )
        ) {
            return $this->unauthorized(
                'Format Authorization tidak valid.'
            );
        }

        $token = trim($matches[1]);

        try {
            $jwtService = new JwtService();

            $auth = $jwtService->validateAccessToken(
                $token
            );

            /*
             * Simpan hasil autentikasi pada request.
             * Controller dapat mengambilnya dari request attribute.
             */
            $request->apiUser = $auth['user'];
            $request->apiToken = $auth['token'];
            $request->apiClaims = $auth['claims'];
            $request->apiAccessToken = $token;
        } catch (\Throwable $e) {
            return $this->unauthorized(
                $e->getMessage()
            );
        }

        return null;
    }

    public function after(
        RequestInterface $request,
        ResponseInterface $response,
        $arguments = null
    ) {
        // Tidak ada aksi setelah response.
    }

    protected function unauthorized(string $message)
    {
        return service('response')
            ->setStatusCode(401)
            ->setJSON([
                'success' => false,
                'message' => $message,
            ]);
    }
}
