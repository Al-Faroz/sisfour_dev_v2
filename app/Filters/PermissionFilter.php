<?php

namespace App\Filters;

use App\Services\AuthService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class PermissionFilter implements FilterInterface
{
    public function before(
        RequestInterface $request,
        $arguments = null
    ) {
        $isApi = $this->isApiRequest($request);

        if ($isApi) {
            $apiUser = $request->apiUser ?? null;

            if (!is_array($apiUser) || (int) ($apiUser['id'] ?? 0) <= 0) {
                return $this->deny(
                    true,
                    401,
                    'User API tidak terautentikasi.'
                );
            }

            $userId = (int) $apiUser['id'];
        } else {
            if (session()->get('logged_in') !== true) {
                return redirect()->to('/auth/login');
            }

            $userId = (int) session()->get('user_id');

            if ($userId <= 0) {
                return $this->deny(
                    false,
                    403,
                    'Identitas pengguna tidak valid.'
                );
            }
        }

        if (empty($arguments)) {
            return $this->deny(
                $isApi,
                403,
                'Permission key tidak dikonfigurasi pada route ini.'
            );
        }

        $authService = new AuthService();
        $matchedPermission = null;
        $resolvedScope = 'TIDAK_ADA';

        foreach ($arguments as $permissionKey) {
            $permissionKey = trim((string) $permissionKey);

            if ($permissionKey === '') {
                continue;
            }

            $scope = $authService->resolveScope(
                $permissionKey,
                $userId
            );

            if ($scope === 'TIDAK_ADA') {
                continue;
            }

            $matchedPermission = $permissionKey;
            $resolvedScope = $scope;
            break;
        }

        if ($matchedPermission === null) {
            log_message(
                'warning',
                'Akses ditolak: user_id={userId}, permissions=[{permissions}]',
                [
                    'userId' => $userId,
                    'permissions' => implode(',', (array) $arguments),
                ]
            );

            return $this->deny(
                $isApi,
                403,
                'Anda tidak memiliki akses ke resource ini.'
            );
        }

        $request->permission = [
            'key' => $matchedPermission,
            'scope' => $resolvedScope,
        ];

        return null;
    }

    public function after(
        RequestInterface $request,
        ResponseInterface $response,
        $arguments = null
    ) {
        return null;
    }

    private function isApiRequest(RequestInterface $request): bool
    {
        $path = trim($request->getUri()->getPath(), '/');

        return $path === 'api' || str_starts_with($path, 'api/');
    }

    private function deny(
        bool $isApi,
        int $status,
        string $message
    ) {
        $response = service('response')->setStatusCode($status);

        if ($isApi) {
            return $response
                ->setContentType('application/json')
                ->setJSON([
                    'success' => false,
                    'message' => $message,
                ]);
        }

        return $response->setBody($message);
    }
}
