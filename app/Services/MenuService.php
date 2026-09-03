<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * MenuService
 *
 * SATU-SATUNYA sumber kebenaran untuk sidebar. Tidak ada logic role di View.
 * View (_sidebar.php) hanya me-render array tree yang dihasilkan di sini secara
 * rekursif — menambah/ubah akses menu cukup ubah tabel `role_menus`, tanpa
 * menyentuh kode.
 *
 * Acuan: 03_AUTH_RBAC_MENU §4, 08_DASHBOARD... (tidak spesifik menu tapi konsisten pola)
 */
class MenuService
{
    protected BaseConnection $db;
    protected AuthService $authService;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->authService = new AuthService();
    }

    /**
     * Bangun tree menu untuk seorang user, sudah difilter sesuai role_menus.tampil=1
     * untuk SEMUA role yang dimiliki user (gabungan/union, bukan hanya role utama).
     *
     * Return format:
     * [
     *   ['id' => 2, 'nama_menu' => 'Presensi', 'icon' => '...', 'link' => '#',
     *    'children' => [ ['id' => 21, 'nama_menu' => 'Presensi Siswa', 'link' => 'presensi/siswa', 'children' => []], ... ]
     *   ],
     *   ...
     * ]
     */
    public function getMenuTree(int $userId): array
    {
        $roles = $this->authService->getUserRoles($userId);
        if (empty($roles)) {
            return [];
        }

        // Ambil id_menu yang tampil=1 untuk salah satu role user (union, distinct)
        $idMenus = $this->db->table('role_menus')
            ->select('id_menu')
            ->distinct()
            ->whereIn('role', $roles)
            ->where('tampil', 1)
            ->get()
            ->getResultArray();

        $idMenus = array_column($idMenus, 'id_menu');

        if (empty($idMenus)) {
            return [];
        }

        $menus = $this->db->table('menus')
            ->whereIn('id', $idMenus)
            ->orderBy('urutan', 'ASC')
            ->get()
            ->getResultArray();

        return $this->buildTree($menus);
    }

    /**
     * Susun array flat menjadi tree berdasarkan parent_id.
     */
    protected function buildTree(array $menus, ?int $parentId = null): array
    {
        $branch = [];

        foreach ($menus as $menu) {
            $currentParentId = $menu['parent_id'] !== null ? (int) $menu['parent_id'] : null;

            if ($currentParentId === $parentId) {
                $children = $this->buildTree($menus, (int) $menu['id']);
                $menu['children'] = $children;
                $branch[] = $menu;
            }
        }

        return $branch;
    }

    /**
     * Tandai active/open pada tree berdasarkan URI saat ini.
     * Dipanggil dari _sidebar.php (bukan dari Controller) supaya tetap generik.
     */
    public function markActive(array $tree, string $currentPath): array
    {
        foreach ($tree as &$item) {
            $itemActive = false;

            if (!empty($item['link']) && $item['link'] !== '#') {
                // Cocokkan prefix supaya /master/guru/update/5 tetap menandai menu "Data Guru"
                $itemActive = strpos($currentPath, ltrim($item['link'], '/')) === 0;
            }

            if (!empty($item['children'])) {
                $item['children'] = $this->markActive($item['children'], $currentPath);
                $childActive = (bool) array_filter($item['children'], fn ($c) => $c['active']);
                $item['active'] = $childActive;
                $item['open']   = $childActive;
            } else {
                $item['active'] = $itemActive;
                $item['open']   = false;
            }
        }

        return $tree;
    }
}
