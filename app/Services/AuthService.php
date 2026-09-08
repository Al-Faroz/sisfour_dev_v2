<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * AuthService
 *
 * Menangani:
 * - autentikasi web berbasis session;
 * - rate limiting login;
 * - auth_version;
 * - multi-role;
 * - resolusi permission scope multi-scope;
 * - status Wali Kelas dinamis;
 * - daftar kelas yang diampu;
 * - daftar kelas terjadwal hari ini.
 *
 * Catatan:
 * - Wali Kelas bukan role.
 * - Status Wali selalu dihitung dinamis dari mapping_wali_kelas.
 * - Permission user adalah union primary role + secondary roles.
 * - Satu role dapat mempunyai lebih dari satu scope untuk permission yang sama.
 */
class AuthService
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Proses login web berbasis session.
     *
     * @return array{success:bool,message:string,user?:array}
     */
    public function attemptLogin(string $username, string $password): array
    {
        $username = trim($username);

        if ($username === '' || $password === '') {
            return [
                'success' => false,
                'message' => 'Username dan password wajib diisi.',
            ];
        }

        if ($this->isLocked($username)) {
            return [
                'success' => false,
                'message' => 'Akun terkunci sementara. Coba lagi dalam beberapa menit.',
            ];
        }

        $user = $this->db
            ->table('users')
            ->where('username', $username)
            ->where('status_aktif', 1)
            ->get()
            ->getRowArray();

        if (! $user) {
            $this->recordAttempt($username, false);

            return [
                'success' => false,
                'message' => 'Username atau password salah.',
            ];
        }

        if (! password_verify($password, (string) $user['password'])) {
            $this->recordAttempt($username, false);

            if ($this->isLocked($username)) {
                return [
                    'success' => false,
                    'message' => 'Terlalu banyak percobaan login. Akun terkunci sementara selama 5 menit.',
                ];
            }

            return [
                'success' => false,
                'message' => 'Username atau password salah.',
            ];
        }

        $this->recordAttempt($username, true);

        $newAuthVersion = ((int) ($user['auth_version'] ?? 0)) + 1;

        $updated = $this->db
            ->table('users')
            ->where('id', $user['id'])
            ->update([
                'auth_version' => $newAuthVersion,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        if (! $updated) {
            return [
                'success' => false,
                'message' => 'Login gagal diproses. Silakan coba lagi.',
            ];
        }

        $user['auth_version'] = $newAuthVersion;

        return [
            'success' => true,
            'message' => 'Login berhasil.',
            'user' => $user,
        ];
    }

    /**
     * Menyimpan data user ke session setelah login berhasil.
     * Status Wali tidak disimpan di session.
     */
    public function setUserSession(array $user): void
    {
        session()->set([
            'user_id' => (int) $user['id'],
            'role' => $user['role'],
            'username' => $user['username'],
            'id_guru' => $user['id_guru'] ?? null,
            'id_siswa' => $user['id_siswa'] ?? null,
            'id_pegawai' => $user['id_pegawai'] ?? null,
            'auth_version' => (int) $user['auth_version'],
            'logged_in' => true,
        ]);
    }

    protected function isLocked(string $username): bool
    {
        $attempts = $this->db
            ->table('login_attempts')
            ->select('berhasil, waktu')
            ->where('username', $username)
            ->orderBy('waktu', 'DESC')
            ->limit(5)
            ->get()
            ->getResultArray();

        if (count($attempts) < 5) {
            return false;
        }

        foreach ($attempts as $attempt) {
            if ((int) ($attempt['berhasil'] ?? 0) === 1) {
                return false;
            }
        }

        $lockStartedAt = strtotime((string) ($attempts[4]['waktu'] ?? ''));

        if ($lockStartedAt === false) {
            return false;
        }

        return time() < ($lockStartedAt + (5 * 60));
    }

    protected function recordAttempt(string $username, bool $berhasil): void
    {
        $this->db
            ->table('login_attempts')
            ->insert([
                'username' => $username,
                'ip_address' => service('request')->getIPAddress(),
                'waktu' => date('Y-m-d H:i:s'),
                'berhasil' => $berhasil ? 1 : 0,
            ]);
    }

    /**
     * Ambil seluruh effective role user.
     *
     * @return string[]
     */
    public function getUserRoles(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $roles = [];

        $primary = $this->db
            ->table('users')
            ->select('role')
            ->where('id', $userId)
            ->get()
            ->getRowArray();

        if ($primary && ! empty($primary['role'])) {
            $roles[] = (string) $primary['role'];
        }

        $extra = $this->db
            ->table('user_roles')
            ->select('role')
            ->where('id_user', $userId)
            ->get()
            ->getResultArray();

        foreach ($extra as $row) {
            if (! empty($row['role'])) {
                $roles[] = (string) $row['role'];
            }
        }

        return array_values(array_unique($roles));
    }

    /**
     * Ambil seluruh scope mentah untuk satu permission.
     *
     * @return string[]
     */
    public function getPermissionScopes(string $permissionKey, int $userId): array
    {
        $roles = $this->getUserRoles($userId);

        if ($roles === []) {
            return [];
        }

        $rows = $this->db
            ->table('role_permissions rp')
            ->select('rp.scope')
            ->join('permissions p', 'p.id = rp.id_permission')
            ->whereIn('rp.role', $roles)
            ->where('p.permission_key', $permissionKey)
            ->get()
            ->getResultArray();

        $scopes = [];

        foreach ($rows as $row) {
            $scope = trim((string) ($row['scope'] ?? ''));

            if ($scope !== '' && $scope !== 'TIDAK_ADA') {
                $scopes[$scope] = $scope;
            }
        }

        return array_values($scopes);
    }

    /**
     * Resolusi scalar scope untuk route gate/menu.
     *
     * Penting:
     * - Service bisnis tetap boleh membaca seluruh scope melalui getPermissionScopes().
     * - KELAS_DIAMPU hanya valid jika user benar-benar Wali aktif.
     * - Bila user bukan Wali tetapi juga memiliki KELAS_TERJADWAL,
     *   jangan mengembalikan TIDAK_ADA; gunakan KELAS_TERJADWAL.
     */
    public function resolveScope(string $permissionKey, int $userId): string
    {
        $scopes = $this->getPermissionScopes($permissionKey, $userId);

        if ($scopes === []) {
            return 'TIDAK_ADA';
        }

        if (in_array('SEMUA', $scopes, true)) {
            return 'SEMUA';
        }

        $user = $this->db
            ->table('users')
            ->select('id_guru')
            ->where('id', $userId)
            ->where('status_aktif', 1)
            ->get()
            ->getRowArray();

        $idGuru = (int) ($user['id_guru'] ?? 0);
        $isWali = $idGuru > 0 && $this->isWaliKelas($idGuru);

        if (in_array('KELAS_DIAMPU', $scopes, true) && $isWali) {
            return 'KELAS_DIAMPU';
        }

        if (in_array('KELAS_TERJADWAL', $scopes, true)) {
            return 'KELAS_TERJADWAL';
        }

        if (in_array('DIRI_SENDIRI', $scopes, true)) {
            return 'DIRI_SENDIRI';
        }

        return 'TIDAK_ADA';
    }

    public function hasPermission(string $permissionKey, int $userId): bool
    {
        return $this->resolveScope($permissionKey, $userId) !== 'TIDAK_ADA';
    }

    /**
     * Status Wali Kelas dinamis.
     */
    public function isWaliKelas(?int $idGuru, ?int $idTahun = null): bool
    {
        if (! $idGuru) {
            return false;
        }

        if ($idTahun === null) {
            $tahun = $this->db
                ->table('tahun_ajaran')
                ->select('id')
                ->where('status_aktif', 1)
                ->where('deleted_at', null)
                ->get()
                ->getRowArray();

            $idTahun = isset($tahun['id']) ? (int) $tahun['id'] : null;
        }

        if (! $idTahun) {
            return false;
        }

        return $this->db
            ->table('mapping_wali_kelas')
            ->where('id_guru', $idGuru)
            ->where('id_tahun', $idTahun)
            ->where('deleted_at', null)
            ->countAllResults() > 0;
    }

    /**
     * Kelas Wali aktif.
     *
     * @return int[]
     */
    public function getKelasDiampu(?int $idGuru, ?int $idTahun = null): array
    {
        if (! $idGuru) {
            return [];
        }

        if ($idTahun === null) {
            $tahun = $this->db
                ->table('tahun_ajaran')
                ->select('id')
                ->where('status_aktif', 1)
                ->where('deleted_at', null)
                ->get()
                ->getRowArray();

            $idTahun = isset($tahun['id']) ? (int) $tahun['id'] : null;
        }

        if (! $idTahun) {
            return [];
        }

        $rows = $this->db
            ->table('mapping_wali_kelas')
            ->select('id_kelas')
            ->where('id_guru', $idGuru)
            ->where('id_tahun', $idTahun)
            ->where('deleted_at', null)
            ->get()
            ->getResultArray();

        return array_values(
            array_unique(
                array_map('intval', array_column($rows, 'id_kelas'))
            )
        );
    }

    /**
     * Kelas yang terjadwal hari ini untuk Guru.
     *
     * @return int[]
     */
    public function getKelasTerjadwalHariIni(?int $idGuru, ?int $idTahun = null): array
    {
        if (! $idGuru) {
            return [];
        }

        if ($idTahun === null) {
            $tahun = $this->db
                ->table('tahun_ajaran')
                ->select('id')
                ->where('status_aktif', 1)
                ->where('deleted_at', null)
                ->get()
                ->getRowArray();

            $idTahun = isset($tahun['id']) ? (int) $tahun['id'] : null;
        }

        if (! $idTahun) {
            return [];
        }

        $rows = $this->db
            ->table('jadwal_guru')
            ->select('id_kelas')
            ->where('id_guru', $idGuru)
            ->where('id_tahun', $idTahun)
            ->where('hari', $this->hariIndonesia())
            ->where('status_jadwal', 'Aktif')
            ->get()
            ->getResultArray();

        return array_values(
            array_unique(
                array_map('intval', array_column($rows, 'id_kelas'))
            )
        );
    }

    protected function hariIndonesia(): string
    {
        return match (date('l')) {
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
            'Sunday' => 'Minggu',
            default => 'Senin',
        };
    }
}
