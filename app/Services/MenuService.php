<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * MenuService
 *
 * SATU-SATUNYA sumber kebenaran untuk sidebar.
 *
 * Menu dasar berasal dari:
 * - menus
 * - role_menus
 *
 * Menu contextual Wali Kelas ditentukan melalui permission scope
 * yang di-resolve oleh AuthService.
 *
 * Wali Kelas bukan role.
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

    public function getMenuTree(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $roles = $this->authService->getUserRoles($userId);

        if (empty($roles)) {
            return [];
        }

        $menuRows = $this->db
            ->table('role_menus rm')
            ->select('rm.id_menu')
            ->distinct()
            ->whereIn('rm.role', $roles)
            ->where('rm.tampil', 1)
            ->get()
            ->getResultArray();

        $idMenus = array_values(
            array_unique(
                array_map(
                    'intval',
                    array_column($menuRows, 'id_menu')
                )
            )
        );

        if (empty($idMenus)) {
            return [];
        }

        $menus = $this->db
            ->table('menus')
            ->whereIn('id', $idMenus)
            ->orderBy('urutan', 'ASC')
            ->get()
            ->getResultArray();

        if (empty($menus)) {
            return [];
        }

        $menus = $this->filterContextualMenus(
            $menus,
            $userId,
            $roles
        );

        if (empty($menus)) {
            return [];
        }

        return $this->buildTree($menus);
    }

    protected function filterContextualMenus(
        array $menus,
        int $userId,
        array $roles
    ): array {
        $isGuru = in_array('guru', $roles, true);
        $isSiswa = in_array('siswa', $roles, true);

        $filtered = [];

        foreach ($menus as $menu) {
            $idMenu = (int) $menu['id'];

            if (
                $idMenu === 31
                && ($isGuru || $isSiswa)
            ) {
                continue;
            }

            $requiredPermissions = $this->menuPermissionMap($idMenu);

            if (!empty($requiredPermissions)) {
                $hasAccess = false;

                foreach ($requiredPermissions as $permissionKey) {
                    $scope = $this->authService->resolveScope(
                        $permissionKey,
                        $userId
                    );

                    if ($scope !== 'TIDAK_ADA') {
                        $hasAccess = true;
                        break;
                    }
                }

                if (!$hasAccess) {
                    continue;
                }
            }

            $filtered[] = $menu;
        }

        return $filtered;
    }

    protected function menuPermissionMap(int $idMenu): array
    {
        return match ($idMenu) {
            21 => [
                'presensi_siswa.input',
                'presensi_siswa.view',
            ],

            33 => [
                'master_siswa.view',
                'master_siswa.manage',
                'master_siswa.edit_biodata',
            ],

            41 => [
                'laporan_matrix.view',
            ],

            42 => [
                'laporan_export.generate',
            ],

            43 => [
                'laporan_jurnal.view',
            ],

            51 => [
                'bk_kasus.view',
                'bk_kasus.manage',
            ],

            53 => [
                'prestasi.view',
                'prestasi.manage',
            ],

            61 => [
                'kartu_pelajar.view',
                'kartu_pelajar.manage',
            ],

            default => [],
        };
    }

    protected function buildTree(
        array $menus,
        ?int $parentId = null
    ): array {
        $branch = [];

        foreach ($menus as $menu) {
            $currentParentId = $menu['parent_id'] !== null
                ? (int) $menu['parent_id']
                : null;

            if ($currentParentId !== $parentId) {
                continue;
            }

            $children = $this->buildTree(
                $menus,
                (int) $menu['id']
            );

            $menu['children'] = $children;
            $branch[] = $menu;
        }

        return $branch;
    }

    public function markActive(
        array $tree,
        string $currentPath
    ): array {
        foreach ($tree as &$item) {
            $itemActive = false;

            if (
                !empty($item['link'])
                && $item['link'] !== '#'
            ) {
                $link = ltrim(
                    (string) $item['link'],
                    '/'
                );

                $itemActive = strpos(
                    $currentPath,
                    $link
                ) === 0;
            }

            if (!empty($item['children'])) {
                $item['children'] = $this->markActive(
                    $item['children'],
                    $currentPath
                );

                $childActive = false;

                foreach ($item['children'] as $child) {
                    if (!empty($child['active'])) {
                        $childActive = true;
                        break;
                    }
                }

                $item['active'] = $childActive;
                $item['open'] = $childActive;
            } else {
                $item['active'] = $itemActive;
                $item['open'] = false;
            }
        }

        unset($item);

        return $tree;
    }
}
