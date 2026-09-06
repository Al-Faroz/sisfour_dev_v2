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
 * Menu contextual Wali Kelas ditentukan dari:
 * - AuthService::isWaliKelas()
 * - permission contextual yang dimiliki user
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

    /**
     * Bangun tree menu untuk user.
     *
     * Role menu merupakan UNION dari seluruh role user.
     *
     * Setelah role menu diperoleh, menu yang bersifat
     * contextual akan divalidasi kembali berdasarkan permission
     * dan status Wali Kelas aktif.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getMenuTree(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $roles = $this->authService->getUserRoles($userId);

        if (empty($roles)) {
            return [];
        }

        /*
         * Ambil seluruh menu yang tampil untuk salah satu role user.
         *
         * Ini UNION, bukan hanya users.role.
         */
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

        /*
         * Ambil data menu.
         */
        $menus = $this->db
            ->table('menus')
            ->whereIn('id', $idMenus)
            ->orderBy('urutan', 'ASC')
            ->get()
            ->getResultArray();

        if (empty($menus)) {
            return [];
        }

        /*
         * Filter menu yang memang tidak boleh tampil
         * berdasarkan konteks user.
         */
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

    /**
     * Filter menu berdasarkan konteks authorization.
     *
     * Aturan final:
     *
     * 1. Data Guru (31) tidak boleh tampil untuk guru/siswa.
     * 2. Menu Wali-only tidak boleh tampil untuk Guru biasa.
     * 3. Menu yang memiliki permission contextual hanya tampil
     *    jika permission tersebut benar-benar resolve untuk user.
     */
    protected function filterContextualMenus(
        array $menus,
        int $userId,
        array $roles
    ): array {
        $isGuru = in_array('guru', $roles, true);
        $isSiswa = in_array('siswa', $roles, true);

        /*
         * Ambil id_guru user.
         */
        $user = $this->db
            ->table('users')
            ->select('id_guru, id_siswa')
            ->where('id', $userId)
            ->get()
            ->getRowArray();

        $idGuru = isset($user['id_guru'])
            ? (int) $user['id_guru']
            : null;

        /*
         * Status Wali selalu dinamis.
         */
        $isWali = $this->authService->isWaliKelas($idGuru);

        $filtered = [];

        foreach ($menus as $menu) {
            $idMenu = (int) $menu['id'];

            /*
             * ---------------------------------------------------------
             * Data Guru (31)
             * ---------------------------------------------------------
             *
             * Dokumentasi secara eksplisit melarang menu ini untuk
             * guru dan siswa, termasuk Guru yang merupakan Wali.
             */
            if (
                $idMenu === 31
                && ($isGuru || $isSiswa)
            ) {
                continue;
            }

            /*
             * ---------------------------------------------------------
             * Menu yang membutuhkan konteks Wali
             * ---------------------------------------------------------
             *
             * Permission yang memiliki KELAS_DIAMPU harus tetap
             * diselesaikan melalui AuthService.
             *
             * Jika permission tersebut hanya tersedia dalam konteks
             * Wali dan user bukan Wali, menu tidak ditampilkan.
             */
            $requiredPermissions = $this->menuPermissionMap($idMenu);

            if (!empty($requiredPermissions)) {
                $hasContextualAccess = false;

                foreach ($requiredPermissions as $permissionKey) {
                    $scope = $this->authService->resolveScope(
                        $permissionKey,
                        $userId
                    );

                    if ($scope !== 'TIDAK_ADA') {
                        $hasContextualAccess = true;
                        break;
                    }
                }

                if (!$hasContextualAccess) {
                    continue;
                }
            }

            /*
             * ---------------------------------------------------------
             * Menu Wali-only
             * ---------------------------------------------------------
             *
             * Wali Kelas bukan role. Jika menu membutuhkan konteks
             * Wali, user harus benar-benar Wali aktif.
             */
            if (
                $this->isWaliOnlyMenu($idMenu)
                && !$isWali
            ) {
                continue;
            }

            $filtered[] = $menu;
        }

        return $filtered;
    }

    /**
     * Mapping menu ke permission utama.
     *
     * Hanya menu yang membutuhkan authorization contextual
     * yang didefinisikan di sini.
     *
     * Menu tetap aman karena akses halaman sebenarnya
     * dijaga PermissionFilter.
     *
     * @return string[]
     */
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

    /**
     * Menu yang secara bisnis merupakan menu Wali Kelas.
     *
     * Wali Kelas memperoleh beberapa menu yang memiliki scope
     * KELAS_DIAMPU, tetapi tetap bukan role tersendiri.
     *
     * @return bool
     */
    protected function isWaliOnlyMenu(int $idMenu): bool
    {
        return in_array(
            $idMenu,
            [
                33, // Data Siswa
                42, // Export Presensi
            ],
            true
        );
    }

    /**
     * Susun array flat menjadi tree berdasarkan parent_id.
     */
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

    /**
     * Tandai active/open pada tree berdasarkan URI saat ini.
     *
     * Dipanggil dari sidebar/view.
     */
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
