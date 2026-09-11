<?php

namespace App\Filters;

use App\Services\JwtService;
use App\Support\RequestContext;
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

            RequestContext::clear($request);
            RequestContext::merge(
                $request,
                [
                    'api_user' => $auth['user'],
                    'api_token_row' => $auth['token'],
                    'api_claims' => $auth['claims'],
                    'api_access_token' => $token,
                ]
            );
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
