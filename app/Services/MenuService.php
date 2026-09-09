<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * Sumber sidebar:
 * - menus;
 * - role_menus;
 * - effective roles user;
 * - permission effective user;
 * - context identity/Wali Kelas.
 *
 * Menu bukan security boundary. PermissionFilter + Service tetap authoritative.
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
        $identity = $this->getUserIdentity($userId);
        $idMenus = [];

        if ($roles !== []) {
            $menuRows = $this->db
                ->table('role_menus rm')
                ->select('rm.id_menu')
                ->distinct()
                ->whereIn('rm.role', $roles)
                ->where('rm.tampil', 1)
                ->get()
                ->getResultArray();

            $idMenus = array_map('intval', array_column($menuRows, 'id_menu'));
        }

        // Profile Pegawai adalah self-service berbasis identity, bukan role.
        // Akun Pegawai baru dapat belum mempunyai role operasional.
        if ((int) ($identity['id_pegawai'] ?? 0) > 0) {
            $idMenus[] = 12;
        }

        $idMenus = array_values(array_unique($idMenus));

        if ($idMenus === []) {
            return [];
        }

        $menus = $this->db
            ->table('menus')
            ->whereIn('id', $idMenus)
            ->orderBy('urutan', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        $menus = $this->filterContextualMenus($menus, $userId);

        return $menus === [] ? [] : $this->buildTree($menus);
    }

    protected function filterContextualMenus(array $menus, int $userId): array
    {
        $identity = $this->getUserIdentity($userId);
        $idGuru = (int) ($identity['id_guru'] ?? 0);
        $isWali = $idGuru > 0 && $this->authService->isWaliKelas($idGuru);
        $filtered = [];

        foreach ($menus as $menu) {
            $idMenu = (int) ($menu['id'] ?? 0);

            if (! $this->identityContextAllowed($idMenu, $identity, $isWali, $userId)) {
                continue;
            }

            $requiredPermissions = $this->menuPermissionMap($idMenu);

            if ($requiredPermissions !== []) {
                $hasAccess = false;

                foreach ($requiredPermissions as $permissionKey) {
                    if ($this->authService->hasPermission($permissionKey, $userId)) {
                        $hasAccess = true;
                        break;
                    }
                }

                if (! $hasAccess) {
                    continue;
                }
            }

            $filtered[] = $menu;
        }

        return $filtered;
    }

    protected function identityContextAllowed(
        int $idMenu,
        array $identity,
        bool $isWali,
        int $userId
    ): bool {
        if ($idMenu === 9) {
            return (int) ($identity['id_guru'] ?? 0) > 0;
        }

        if ($idMenu === 10) {
            return (int) ($identity['id_siswa'] ?? 0) > 0;
        }

        if ($idMenu === 12) {
            return (int) ($identity['id_pegawai'] ?? 0) > 0;
        }

        if ($idMenu === 37) {
            if (
                $this->authService->hasPermission('mapping_wali.manage', $userId)
                || $this->authService->hasPermission('mapping_wali.view_all', $userId)
            ) {
                return true;
            }

            return $isWali && $this->authService->hasPermission('mapping_wali.view', $userId);
        }

        return true;
    }

    protected function menuPermissionMap(int $idMenu): array
    {
        return match ($idMenu) {
            1 => ['dashboard.view'],

            21 => ['presensi_siswa.input'],
            22 => ['presensi_mengajar.input'],
            23 => ['presensi_siswa.view'],
            24 => ['ews_radar.view'],

            31 => ['master_guru.manage', 'master_guru.view'],
            32 => ['master_pegawai.manage', 'master_pegawai.view'],
            33 => ['master_siswa.view', 'master_siswa.manage', 'master_siswa.edit_biodata'],
            34 => ['master_kelas.manage'],
            35 => ['master_tahun_ajaran.manage'],
            36 => ['master_mapel.manage'],
            37 => ['mapping_wali.manage', 'mapping_wali.view', 'mapping_wali.view_all'],
            38 => ['jadwal_guru.manage', 'jadwal_guru.view', 'jadwal_guru.view_all'],

            111, 112, 113, 114 => ['master_siswa.manage'],

            41 => ['laporan_matrix.view'],
            42 => ['laporan_export.generate'],
            43 => ['laporan_jurnal.view'],

            51 => ['bk_kasus.view', 'bk_kasus.manage'],
            52 => ['bk_pelanggaran_master.manage'],
            53 => ['prestasi.view', 'prestasi.manage'],

            61 => ['kartu_pelajar.view', 'kartu_pelajar.manage'],

            71 => ['settings_user.manage'],
            72 => ['settings_menu.manage'],
            73 => ['settings_sistem.manage'],

            81 => ['backup.manage'],
            82 => ['log_activity.view'],

            9 => ['profile_guru.view'],
            10 => ['profile_siswa.view'],
            12 => [],

            default => [],
        };
    }

    protected function getUserIdentity(int $userId): array
    {
        return $this->db
            ->table('users')
            ->select('id_guru, id_pegawai, id_siswa')
            ->where('id', $userId)
            ->where('status_aktif', 1)
            ->get()
            ->getRowArray() ?? [];
    }

    protected function buildTree(array $menus, ?int $parentId = null): array
    {
        $branch = [];

        foreach ($menus as $menu) {
            $currentParentId = $menu['parent_id'] !== null ? (int) $menu['parent_id'] : null;

            if ($currentParentId !== $parentId) {
                continue;
            }

            $menu['children'] = $this->buildTree($menus, (int) $menu['id']);
            $branch[] = $menu;
        }

        return $branch;
    }

    public function markActive(array $tree, string $currentPath): array
    {
        foreach ($tree as &$item) {
            $itemActive = false;

            if (! empty($item['link']) && $item['link'] !== '#') {
                $link = ltrim((string) $item['link'], '/');
                $itemActive = strpos($currentPath, $link) === 0;
            }

            if (! empty($item['children'])) {
                $item['children'] = $this->markActive($item['children'], $currentPath);
                $childActive = false;

                foreach ($item['children'] as $child) {
                    if (! empty($child['active'])) {
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
