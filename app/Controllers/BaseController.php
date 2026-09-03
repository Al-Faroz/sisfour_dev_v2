<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\MenuService;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController
 *
 * Semua Controller yang butuh layout (main.php + sidebar) WAJIB extend ini.
 * Tugasnya: sekali jalan menyiapkan $layoutData berisi menu tree (dari
 * MenuService, bukan hardcode role) dan info user, supaya setiap Controller
 * modul tidak perlu mengulang logic sidebar sendiri-sendiri — inilah akar
 * masalah "sidebar sering gagal" sebelumnya.
 */
abstract class BaseController extends Controller
{
    protected $helpers = ['url', 'form'];

    protected AuthService $authService;
    protected MenuService $menuService;

    /** @var array Data baku yang dikirim ke main.php di setiap halaman ber-layout */
    protected array $layoutData = [];

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
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
        $role   = session()->get('role');
        $idGuru = session()->get('id_guru');

        $tree = $this->menuService->getMenuTree($userId);
        $currentPath = trim(current_url(true)->getPath(), '/');
        $tree = $this->menuService->markActive($tree, $currentPath);

        $this->layoutData = [
            'menuTree'   => $tree,
            'authUser'   => [
                'username'  => session()->get('username'),
                'role'      => $role,
                'id_guru'   => $idGuru,
                'id_siswa'  => session()->get('id_siswa'),
                'is_wali'   => $idGuru ? $this->authService->isWaliKelas((int) $idGuru) : false,
            ],
        ];
    }

    /**
     * Helper untuk Controller modul: render view di dalam layout main.php
     * dengan $layoutData sudah tergabung otomatis.
     */
    protected function renderWithLayout(string $view, array $data = []): string
    {
        return view($view, array_merge($this->layoutData, $data));
    }
}
