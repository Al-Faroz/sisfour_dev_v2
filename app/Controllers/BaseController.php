<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\MenuService;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class BaseController extends Controller
{
    protected $helpers = ['url', 'form'];
    protected AuthService $authService;
    protected MenuService $menuService;
    protected array $layoutData = [];

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);

        $this->authService = new AuthService();
        $this->menuService = new MenuService();

        if (session()->get('logged_in')) {
            $this->prepareLayoutData();
        }
    }

    protected function prepareLayoutData(): void
    {
        $userId = (int) session()->get('user_id');
        $role = session()->get('role');
        $idGuru = session()->get('id_guru');

        $tree = $this->menuService->getMenuTree($userId);
        $tree = $this->pruneEmptyMenuGroups($tree);

        $currentPath = trim(current_url(true)->getPath(), '/');
        $tree = $this->menuService->markActive($tree, $currentPath);

        $this->layoutData = [
            'menuTree' => $tree,
            'authUser' => [
                'username' => session()->get('username'),
                'role' => $role,
                'id_guru' => $idGuru,
                'id_siswa' => session()->get('id_siswa'),
                'is_wali' => $idGuru
                    ? $this->authService->isWaliKelas((int) $idGuru)
                    : false,
            ],
        ];
    }

    protected function renderWithLayout(string $view, array $data = []): string
    {
        return view($view, array_merge($this->layoutData, $data));
    }

    protected function pruneEmptyMenuGroups(array $tree): array
    {
        $result = [];

        foreach ($tree as $item) {
            $children = $this->pruneEmptyMenuGroups($item['children'] ?? []);
            $item['children'] = $children;

            $isGroup = empty($item['link']) || $item['link'] === '#';

            if ($isGroup && $children === []) {
                continue;
            }

            $result[] = $item;
        }

        return $result;
    }
}
