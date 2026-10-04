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
    private const GLOBAL_MENU_IDS = [1];
    private const ACCOUNT_MENU_IDS = [9, 10, 12];

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

    /**
     * G3.9 multi-role Sidebar presentation.
     *
     * Permission tetap union melalui AuthService. Method ini hanya menjaga
     * provenance navigation Primary/Secondary role.
     */
    public function getMultiRoleMenuLayout(
        int $userId,
        array $dashboardComposition,
        bool $isWali
    ): array {
        if ($userId <= 0) {
            return [
                'is_multi_role' => false,
                'global_items' => [],
                'role_sections' => [],
                'account_items' => [],
            ];
        }

        $primaryRole = strtolower(trim((string) (
            $dashboardComposition['primary_role'] ?? ''
        )));
        $secondaryRoles = array_values(array_filter(array_map(
            static fn ($role): string => strtolower(trim((string) $role)),
            $dashboardComposition['secondary_roles'] ?? []
        )));
        $roles = array_values(array_unique(array_filter(array_merge(
            [$primaryRole],
            $secondaryRoles
        ))));

        $roleTrees = [];

        foreach ($roles as $role) {
            $roleTrees[$role] = $this->getMenuTreeForRole(
                $userId,
                $role,
                array_merge(
                    self::GLOBAL_MENU_IDS,
                    self::ACCOUNT_MENU_IDS
                )
            );
        }

        return SidebarMenuCompositionService::compose(
            $dashboardComposition,
            $roleTrees,
            $this->getGlobalMenuItems($userId, $roles),
            $this->getAccountMenuItems($userId),
            $isWali
        );
    }

    /**
     * Mark active state untuk seluruh multi-role layout menggunakan satu
     * best-match link global agar dua section tidak aktif bersamaan.
     */
    public function markMultiRoleLayoutActive(
        array $layout,
        string $currentPath
    ): array {
        $currentPath = $this->normalizeMenuPath($currentPath);
        $aggregate = $layout['global_items'] ?? [];

        foreach ($layout['role_sections'] ?? [] as $section) {
            $aggregate = array_merge(
                $aggregate,
                $section['items'] ?? []
            );
        }

        $aggregate = array_merge(
            $aggregate,
            $layout['account_items'] ?? []
        );

        $activeLink = $this->findBestActiveLink(
            $aggregate,
            $currentPath
        );

        $layout['global_items'] = $this->applyActiveState(
            $layout['global_items'] ?? [],
            $activeLink
        );

        foreach ($layout['role_sections'] ?? [] as &$section) {
            $section['items'] = $this->applyActiveState(
                $section['items'] ?? [],
                $activeLink
            );
        }
        unset($section);

        $layout['account_items'] = $this->applyActiveState(
            $layout['account_items'] ?? [],
            $activeLink
        );

        return $layout;
    }

    /**
     * @return list<array>
     */
    private function getMenuTreeForRole(
        int $userId,
        string $role,
        array $excludeIds = []
    ): array {
        $role = strtolower(trim($role));

        if ($role === '') {
            return [];
        }

        $rows = $this->db
            ->table('role_menus rm')
            ->select('rm.id_menu')
            ->where('rm.role', $role)
            ->where('rm.tampil', 1)
            ->get()
            ->getResultArray();

        $idMenus = array_map('intval', array_column($rows, 'id_menu'));

        if ($excludeIds !== []) {
            $idMenus = array_values(array_diff(
                $idMenus,
                array_map('intval', $excludeIds)
            ));
        }

        $menus = $this->getMenuRowsByIds($idMenus);

        if ($menus === []) {
            return [];
        }

        $menus = $this->filterContextualMenus($menus, $userId);

        return $menus === [] ? [] : $this->buildTree($menus);
    }

    /**
     * Dashboard global hanya satu dan tetap menghormati role_menus.
     *
     * @return list<array>
     */
    private function getGlobalMenuItems(
        int $userId,
        array $roles
    ): array {
        if ($roles === []) {
            return [];
        }

        $assigned = $this->db
            ->table('role_menus rm')
            ->whereIn('rm.role', $roles)
            ->whereIn('rm.id_menu', self::GLOBAL_MENU_IDS)
            ->where('rm.tampil', 1)
            ->countAllResults() > 0;

        if (! $assigned) {
            return [];
        }

        $menus = $this->filterContextualMenus(
            $this->getMenuRowsByIds(self::GLOBAL_MENU_IDS),
            $userId
        );

        return $menus === [] ? [] : $this->buildTree($menus);
    }

    /**
     * Profile adalah person identity surface, bukan operational role surface.
     *
     * @return list<array>
     */
    private function getAccountMenuItems(int $userId): array
    {
        $identity = $this->getUserIdentity($userId);
        $idMenu = null;

        if ((int) ($identity['id_guru'] ?? 0) > 0) {
            $idMenu = 9;
        } elseif ((int) ($identity['id_pegawai'] ?? 0) > 0) {
            $idMenu = 12;
        } elseif ((int) ($identity['id_siswa'] ?? 0) > 0) {
            $idMenu = 10;
        }

        if ($idMenu === null) {
            return [];
        }

        $menus = $this->filterContextualMenus(
            $this->getMenuRowsByIds([$idMenu]),
            $userId
        );

        return $menus === [] ? [] : $this->buildTree($menus);
    }

    /**
     * @return list<array>
     */
    private function getMenuRowsByIds(array $idMenus): array
    {
        $idMenus = array_values(array_unique(array_filter(array_map(
            'intval',
            $idMenus
        ))));

        if ($idMenus === []) {
            return [];
        }

        return $this->db
            ->table('menus')
            ->whereIn('id', $idMenus)
            ->orderBy('urutan', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();
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

            $requiredPermissions = $this->menuPermissionMap(
                $idMenu,
                trim((string) ($menu['link'] ?? '')),
                trim((string) ($menu['nama_menu'] ?? ''))
            );

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

    protected function menuPermissionMap(
        int $idMenu,
        string $link = '',
        string $name = ''
    ): array {
        $uks = match ($link) {
            'uks/ckg' => ['uks_ckg.view', 'uks_ckg.manage'],
            'uks/harian' => ['uks_harian.view', 'uks_harian.manage'],
            'uks/master' => ['uks_master.manage'],
            default => $name === 'UKS'
                ? ['uks_ckg.view', 'uks_harian.view', 'uks_master.manage']
                : null,
        };

        if ($uks !== null) {
            return $uks;
        }

        $ptsp = match ($link) {
            'ptsp/layanan' => ['ptsp_layanan.view', 'ptsp_layanan.manage'],
            'ptsp/polling' => ['ptsp_polling.view'],
            'ptsp/pengaduan' => ['ptsp_pengaduan.view', 'ptsp_pengaduan.manage'],
            default => $name === 'PTSP'
                ? ['ptsp_layanan.view', 'ptsp_polling.view', 'ptsp_pengaduan.view']
                : null,
        };

        if ($ptsp !== null) {
            return $ptsp;
        }

        if ($link === 'statistik') {
            return ['statistik.view'];
        }

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

            $link = trim((string) ($menu['link'] ?? ''));
            $isContainerOnly = $link === '' || $link === '#';

            if ($isContainerOnly && $menu['children'] === []) {
                continue;
            }

            $branch[] = $menu;
        }

        return $branch;
    }

    public function markActive(array $tree, string $currentPath): array
    {
        $currentPath = $this->normalizeMenuPath($currentPath);
        $activeLink = $this->findBestActiveLink($tree, $currentPath);

        return $this->applyActiveState($tree, $activeLink);
    }

    protected function findBestActiveLink(
        array $tree,
        string $currentPath
    ): ?string {
        $bestLink = null;

        foreach ($tree as $item) {
            $link = $this->normalizeMenuPath(
                (string) ($item['link'] ?? '')
            );

            if (
                $link !== ''
                && $link !== '#'
                && $this->pathMatchesMenuLink($currentPath, $link)
                && (
                    $bestLink === null
                    || strlen($link) > strlen($bestLink)
                )
            ) {
                $bestLink = $link;
            }

            $childBest = $this->findBestActiveLink(
                $item['children'] ?? [],
                $currentPath
            );

            if (
                $childBest !== null
                && (
                    $bestLink === null
                    || strlen($childBest) > strlen($bestLink)
                )
            ) {
                $bestLink = $childBest;
            }
        }

        return $bestLink;
    }

    protected function applyActiveState(
        array $tree,
        ?string $activeLink
    ): array {
        foreach ($tree as &$item) {
            $item['children'] = $this->applyActiveState(
                $item['children'] ?? [],
                $activeLink
            );

            $link = $this->normalizeMenuPath(
                (string) ($item['link'] ?? '')
            );

            $selfActive = $activeLink !== null
                && $link !== ''
                && $link !== '#'
                && $link === $activeLink;

            $childActive = false;

            foreach ($item['children'] as $child) {
                if (! empty($child['active'])) {
                    $childActive = true;
                    break;
                }
            }

            $item['active'] = $selfActive || $childActive;
            $item['open'] = $item['children'] !== []
                && $childActive;
        }

        unset($item);

        return $tree;
    }

    protected function pathMatchesMenuLink(
        string $currentPath,
        string $link
    ): bool {
        if ($currentPath === $link) {
            return true;
        }

        return str_starts_with(
            $currentPath,
            $link . '/'
        );
    }

    protected function normalizeMenuPath(string $path): string
    {
        return trim($path, "/ \t\n\r\0\x0B");
    }
}
