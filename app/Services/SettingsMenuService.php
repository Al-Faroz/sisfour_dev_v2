<?php

namespace App\Services;

use App\Models\SettingsMenuModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\I18n\Time;
use Config\Database;
use Throwable;

class SettingsMenuService
{
    private const ROLES = ['admin', 'operator', 'pimpinan', 'bk', 'guru', 'siswa'];

    protected SettingsMenuModel $model;
    protected BaseConnection $db;
    protected AuthService $authService;

    public function __construct()
    {
        $this->model = new SettingsMenuModel();
        $this->db = Database::connect();
        $this->authService = new AuthService();
    }

    public function page(): array
    {
        if (! $this->canManage($this->currentUserId())) {
            return $this->fail('FORBIDDEN', 'Anda tidak memiliki hak mengelola Menu & Role.');
        }

        $menus = $this->model->getMenus();
        $roleMenus = $this->model->getRoleMenus();

        $enabled = [];

        foreach ($roleMenus as $row) {
            $enabled[(int) $row['id_menu']][] = (string) $row['role'];
        }

        foreach ($menus as &$menu) {
            $menu['roles'] = array_values(array_unique($enabled[(int) $menu['id']] ?? []));
            $menu['required_permissions'] = $this->requiredPermissions((string) ($menu['link'] ?? ''));
        }
        unset($menu);

        return [
            'success' => true,
            'roles' => self::ROLES,
            'menus' => $menus,
            'permission_summary' => $this->model->permissionSummary(),
        ];
    }

    public function update(int $actorUserId, int $idMenu, array $input): array
    {
        if (! $this->canManage($actorUserId)) {
            return $this->fail('FORBIDDEN', 'Anda tidak memiliki hak mengelola Menu & Role.');
        }

        $menu = $this->model->getMenu($idMenu);

        if (!$menu) {
            return $this->fail('NOT_FOUND', 'Menu tidak ditemukan.');
        }

        $roles = $input['roles'] ?? [];

        if (!is_array($roles)) {
            $roles = [$roles];
        }

        $roles = array_values(array_unique(array_filter(array_map(
            static fn ($role) => trim((string) $role),
            $roles
        ))));

        foreach ($roles as $role) {
            if (!in_array($role, self::ROLES, true)) {
                return $this->fail('VALIDATION', 'Role menu tidak valid.');
            }
        }

        $required = $this->requiredPermissions((string) ($menu['link'] ?? ''));

        foreach ($roles as $role) {
            if (
                $required !== []
                && !$this->model->roleHasAnyPermission($role, $required)
            ) {
                return $this->fail(
                    'INCONSISTENT',
                    "Role {$role} tidak memiliki permission yang membuat menu {$menu['nama_menu']} usable."
                );
            }
        }

        $this->db->transBegin();

        try {
            $this->model->replaceRoles($idMenu, $roles);

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi gagal.');
            }

            $this->log(
                $actorUserId,
                "Menu #{$idMenu} {$menu['nama_menu']} → " . implode(',', $roles)
            );
            $this->db->transCommit();

            return ['success' => true, 'message' => 'Konfigurasi menu berhasil diperbarui.'];
        } catch (Throwable $e) {
            $this->db->transRollback();
            return $this->fail('UPDATE_FAILED', 'Konfigurasi menu gagal diperbarui.');
        }
    }

    private function requiredPermissions(string $link): array
    {
        $link = trim($link, '/');

        if ($link === '' || $link === '#') {
            return [];
        }

        return match ($link) {
            'dashboard' => ['dashboard.view'],
            'presensi/siswa' => ['presensi_siswa.input'],
            'presensi/mengajar' => ['presensi_mengajar.input'],
            'presensi/siswa/rekap' => ['presensi_siswa.view'],
            'presensi/siswa/ews' => ['ews_radar.view'],

            'master/guru' => ['master_guru.manage', 'master_guru.view'],
            'master/pegawai' => ['master_pegawai.manage', 'master_pegawai.view'],
            'master/siswa' => ['master_siswa.view'],
            'master/kelas' => ['master_kelas.manage'],
            'master/tahun' => ['master_tahun_ajaran.manage'],
            'master/mapel' => ['master_mapel.manage'],
            'master/wali-kelas' => ['mapping_wali.view', 'mapping_wali.manage', 'mapping_wali.view_all'],
            'master/jadwal' => ['jadwal_guru.view', 'jadwal_guru.view_all', 'jadwal_guru.manage'],

            'manajemen-siswa/kelas',
            'manajemen-siswa/kenaikan',
            'manajemen-siswa/mutasi',
            'manajemen-siswa/kelulusan' => ['master_siswa.manage'],

            'laporan/presensi/matrix' => ['laporan_matrix.view'],
            'laporan/presensi/export' => ['laporan_export.generate'],
            'laporan/jurnal' => ['laporan_jurnal.view'],

            'bk/kasus' => ['bk_kasus.view'],
            'bk/pelanggaran' => ['bk_pelanggaran_master.manage'],
            'bk/prestasi' => ['prestasi.view'],

            'kartu/daftar' => ['kartu_pelajar.view'],

            'settings/user' => ['settings_user.manage'],
            'settings/menu' => ['settings_menu.manage'],
            'settings/sistem' => ['settings_sistem.manage'],

            'backup' => ['backup.manage'],
            'log/activity' => ['log_activity.view'],

            'profile/guru' => ['profile_guru.view'],
            'profile/siswa' => ['profile_siswa.view'],

            default => [],
        };
    }

    private function canManage(int $actorUserId): bool
    {
        return $actorUserId > 0
            && $this->authService->resolveScope(
                'settings_menu.manage',
                $actorUserId
            ) === 'SEMUA';
    }

    private function currentUserId(): int
    {
        return (int) (session()->get('user_id') ?? 0);
    }

    private function log(int $userId, string $keterangan): void
    {
        $this->db->table('log_activity')->insert([
            'id_user' => $userId,
            'aksi' => 'UPDATE',
            'modul' => 'Settings Menu',
            'keterangan' => $keterangan,
            'waktu' => Time::now('Asia/Jakarta')->format('Y-m-d H:i:s'),
        ]);
    }

    private function fail(string $code, string $message): array
    {
        return ['success' => false, 'code' => $code, 'message' => $message];
    }
}
