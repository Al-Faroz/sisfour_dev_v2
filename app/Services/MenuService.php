<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * MenuService
 *
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

        if ($roles === []) {
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
                array_map('intval', array_column($menuRows, 'id_menu'))
            )
        );

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

    /**
     * Context yang tidak dapat direpresentasikan hanya dengan existence permission.
     */
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

        // Mapping Wali Kelas untuk Guru biasa tidak bermakna bila user bukan
        // Wali aktif. Actor dengan manage/view_all tetap boleh melihatnya.
        if ($idMenu === 37) {
            if (
                $this->authService->hasPermission('mapping_wali.manage', $userId)
                || $this->authService->hasPermission('mapping_wali.view_all', $userId)
            ) {
                return true;
            }

            return $isWali
                && $this->authService->hasPermission('mapping_wali.view', $userId);
        }

        return true;
    }

    /**
     * Mapping permission mengikuti route tujuan menu.
     * Parent group tidak perlu permission karena BaseController akan prune group kosong.
     *
     * @return string[]
     */
    protected function menuPermissionMap(int $idMenu): array
    {
        return match ($idMenu) {
            1 => ['dashboard.view'],

            // Presensi
            21 => ['presensi_siswa.input'],
            22 => ['presensi_mengajar.input'],
            23 => ['presensi_siswa.view'],
            24 => ['ews_radar.view'],

            // Master Data
            31 => ['master_guru.manage', 'master_guru.view'],
            32 => ['master_pegawai.manage', 'master_pegawai.view'],
            33 => ['master_siswa.view', 'master_siswa.manage', 'master_siswa.edit_biodata'],
            34 => ['master_kelas.manage'],
            35 => ['master_tahun_ajaran.manage'],
            36 => ['master_mapel.manage'],
            37 => ['mapping_wali.manage', 'mapping_wali.view', 'mapping_wali.view_all'],
            38 => ['jadwal_guru.manage', 'jadwal_guru.view', 'jadwal_guru.view_all'],

            // Manajemen Siswa
            111, 112, 113, 114 => ['master_siswa.manage'],

            // Laporan
            41 => ['laporan_matrix.view'],
            42 => ['laporan_export.generate'],
            43 => ['laporan_jurnal.view'],

            // BK & Prestasi
            51 => ['bk_kasus.view', 'bk_kasus.manage'],
            52 => ['bk_pelanggaran_master.manage'],
            53 => ['prestasi.view', 'prestasi.manage'],

            // KT-P
            61 => ['kartu_pelajar.view', 'kartu_pelajar.manage'],

            // Settings
            71 => ['settings_user.manage'],
            72 => ['settings_menu.manage'],
            73 => ['settings_sistem.manage'],

            // Backup & Log
            81 => ['backup.manage'],
            82 => ['log_activity.view'],

            // Profile
            9 => ['profile_guru.view'],
            10 => ['profile_siswa.view'],

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
            $currentParentId = $menu['parent_id'] !== null
                ? (int) $menu['parent_id']
                : null;

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
