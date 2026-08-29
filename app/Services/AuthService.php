<?php

namespace App\Services;

use App\Models\ApiTokensModel;
use App\Models\LoginAttemptsModel;
use App\Models\UserModel;
use App\Models\UserRolesModel;
use CodeIgniter\Database\ConnectionInterface;
use Config\Database;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * AuthService
 *
 * Menangani seluruh logika autentikasi & RBAC inti:
 * - Login/logout web (session, ci_sessions)
 * - Login/refresh/logout mobile (JWT access + refresh token)
 * - Rate limiting (5x gagal -> lock 5 menit, berbasis username)
 * - Single Active Session (auth_version)
 * - resolveScope() untuk PermissionFilter
 * - Status Wali Kelas (dinamis, TIDAK PERNAH disimpan di session)
 *
 * Acuan: 01_MASTERPLAN §9, 03_AUTH_RBAC_MENU §1-3, 16_MOBILE_CORDOVA §4
 */
class AuthService
{
    protected UserModel $userModel;
    protected UserRolesModel $userRolesModel;
    protected LoginAttemptsModel $loginAttemptsModel;
    protected ApiTokensModel $apiTokensModel;
    protected ConnectionInterface $db;

    protected int $maxAttempts      = 5;      // 03_AUTH_RBAC_MENU §1.4
    protected int $lockMinutes      = 5;
    protected int $accessTokenTtl   = 3600;      // 1 jam
    protected int $refreshTokenTtl  = 2592000;   // 30 hari
    protected string $jwtSecret;

    public function __construct()
    {
        $this->userModel          = new UserModel();
        $this->userRolesModel     = new UserRolesModel();
        $this->loginAttemptsModel = new LoginAttemptsModel();
        $this->apiTokensModel     = new ApiTokensModel();
        $this->db                 = Database::connect();

        // WAJIB diisi di .env: JWT_SECRET=<random string panjang>
        $this->jwtSecret = (string) env('JWT_SECRET', '');
    }

    // ================================================================
    // RATE LIMITING — §1.4
    // ================================================================

    public function isLocked(string $username): bool
    {
        return $this->loginAttemptsModel->countRecentFailedAttempts($username, $this->lockMinutes) >= $this->maxAttempts;
    }

    // ================================================================
    // WEB LOGIN — Session (ci_sessions) — §1.1, §1.3
    // ================================================================

    /**
     * @return array{success: bool, message: string, user?: array}
     */
    public function attemptLogin(string $username, string $password, string $ipAddress): array
    {
        if ($this->isLocked($username)) {
            return ['success' => false, 'message' => 'Akun terkunci sementara karena terlalu banyak percobaan gagal. Coba lagi dalam beberapa menit.'];
        }

        $user = $this->userModel->findByUsername($username);

        if (!$user || !password_verify($password, $user['password'])) {
            $this->loginAttemptsModel->catat($username, $ipAddress, false);

            return ['success' => false, 'message' => 'Username atau password salah.'];
        }

        if ((int) $user['status_aktif'] !== 1) {
            $this->loginAttemptsModel->catat($username, $ipAddress, false);

            return ['success' => false, 'message' => 'Akun tidak aktif. Hubungi Admin.'];
        }

        // Single Active Session: increment auth_version -> sesi/token lama otomatis invalid
        $newVersion = (int) $user['auth_version'] + 1;
        $this->userModel->update($user['id'], ['auth_version' => $newVersion]);
        $user['auth_version'] = $newVersion;

        $this->loginAttemptsModel->catat($username, $ipAddress, true);

        // Struktur session WAJIB — 03_AUTH_RBAC_MENU §1.3
        session()->regenerate(true);
        session()->set([
            'user_id'      => $user['id'],
            'role'         => $user['role'],
            'all_roles'    => $this->getUserRoles((int) $user['id']),
            'username'     => $user['username'],
            'id_guru'      => $user['id_guru'],
            'id_siswa'     => $user['id_siswa'],
            'id_pegawai'   => $user['id_pegawai'],
            'auth_version' => $user['auth_version'],
            'logged_in'    => true,
        ]);

        return ['success' => true, 'message' => 'Login berhasil.', 'user' => $user];
    }

    public function logout(): void
    {
        session()->destroy();
    }

    /**
     * Cek validitas session (dipanggil AuthFilter di setiap request web).
     * Membandingkan auth_version di session vs database (Single Active Session).
     */
    public function isWebSessionValid(): bool
    {
        if (!session()->get('logged_in')) {
            return false;
        }

        $user = $this->userModel->find(session()->get('user_id'));

        if (!$user || (int) $user['status_aktif'] !== 1) {
            return false;
        }

        return (int) $user['auth_version'] === (int) session()->get('auth_version');
    }

    // ================================================================
    // MULTI-ROLE — §2.1
    // ================================================================

    /**
     * Gabungan users.role (role utama) + seluruh baris di user_roles.
     *
     * @return list<string>
     */
    public function getUserRoles(int $userId): array
    {
        $user = $this->userModel->find($userId);
        if (!$user) {
            return [];
        }

        $roles   = $this->userRolesModel->getRolesByUser($userId);
        $roles[] = $user['role'];

        return array_values(array_unique($roles));
    }

    // ================================================================
    // RESOLVE SCOPE (RBAC) — §3.2, dipanggil oleh PermissionFilter
    // ================================================================

    public function resolveScope(string $permissionKey, int $userId): string
    {
        $roles = $this->getUserRoles($userId);

        if (empty($roles)) {
            return 'TIDAK_ADA';
        }

        $result = $this->db->table('role_permissions')
            ->select('role_permissions.scope')
            ->join('permissions', 'permissions.id = role_permissions.id_permission')
            ->whereIn('role_permissions.role', $roles)
            ->where('permissions.permission_key', $permissionKey)
            ->get()
            ->getRow();

        return $result->scope ?? 'TIDAK_ADA';
    }

    /**
     * Cek beberapa permission_key sekaligus (OR — dipakai untuk route seperti
     * 'permission:master_guru.manage,master_guru.view'). Mengembalikan scope
     * dari permission PERTAMA yang cocok, prioritas sesuai urutan array.
     */
    public function resolveScopeAny(array $permissionKeys, int $userId): array
    {
        foreach ($permissionKeys as $key) {
            $scope = $this->resolveScope($key, $userId);
            if ($scope !== 'TIDAK_ADA') {
                return ['permission' => $key, 'scope' => $scope];
            }
        }

        return ['permission' => $permissionKeys[0] ?? null, 'scope' => 'TIDAK_ADA'];
    }

    // ================================================================
    // STATUS WALI KELAS — DINAMIS, JANGAN DISIMPAN DI SESSION — §3.4
    // ================================================================

    public function isWaliKelas(?int $idGuru, ?int $idTahun = null): bool
    {
        return $this->getKelasDiampu($idGuru, $idTahun) !== null;
    }

    /**
     * Ambil id_kelas yang diampu guru (jika sedang menjadi wali aktif).
     * Return null jika bukan wali kelas.
     */
    public function getKelasDiampu(?int $idGuru, ?int $idTahun = null): ?int
    {
        if (!$idGuru) {
            return null;
        }

        $idTahun ??= $this->getActiveTahunAjaranId();
        if (!$idTahun) {
            return null;
        }

        $row = $this->db->table('mapping_wali_kelas')
            ->select('id_kelas')
            ->where('id_guru', $idGuru)
            ->where('id_tahun', $idTahun)
            ->where('deleted_at', null)
            ->get()
            ->getRow();

        return $row->id_kelas ?? null;
    }

    /**
     * Ambil daftar id_kelas yang terjadwal untuk guru pada hari ini (scope KELAS_TERJADWAL).
     *
     * @return int[]
     */
    public function getKelasTerjadwalHariIni(?int $idGuru, ?int $idTahun = null): array
    {
        if (!$idGuru) {
            return [];
        }

        $idTahun ??= $this->getActiveTahunAjaranId();
        if (!$idTahun) {
            return [];
        }

        $hariIni = $this->namaHariIndonesia();

        $rows = $this->db->table('jadwal_guru')
            ->select('DISTINCT id_kelas', false)
            ->where('id_guru', $idGuru)
            ->where('id_tahun', $idTahun)
            ->where('hari', $hariIni)
            ->where('status_jadwal', 'Aktif')
            ->get()
            ->getResultArray();

        return array_map('intval', array_column($rows, 'id_kelas'));
    }

    public function getActiveTahunAjaranId(): ?int
    {
        $row = $this->db->table('tahun_ajaran')
            ->select('id')
            ->where('status_aktif', 1)
            ->where('deleted_at', null)
            ->get()
            ->getRow();

        return $row->id ?? null;
    }

    protected function namaHariIndonesia(): string
    {
        $map = [
            'Sunday'    => 'Minggu',
            'Monday'    => 'Senin',
            'Tuesday'   => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday'  => 'Kamis',
            'Friday'    => 'Jumat',
            'Saturday'  => 'Sabtu',
        ];

        return $map[date('l')] ?? 'Senin';
    }

    // ================================================================
    // MOBILE — JWT (Access 1 jam / Refresh 30 hari) — 16_MOBILE_CORDOVA §4
    // ================================================================

    /**
     * @return array{success: bool, message: string, data?: array}
     */
    public function apiLogin(string $username, string $password, string $ipAddress, ?string $deviceName = null): array
    {
        if ($this->isLocked($username)) {
            return ['success' => false, 'message' => 'Akun terkunci sementara. Coba lagi dalam beberapa menit.'];
        }

        $user = $this->userModel->findByUsername($username);

        if (!$user || !password_verify($password, $user['password'])) {
            $this->loginAttemptsModel->catat($username, $ipAddress, false);

            return ['success' => false, 'message' => 'Username atau password salah.'];
        }

        if ((int) $user['status_aktif'] !== 1) {
            $this->loginAttemptsModel->catat($username, $ipAddress, false);

            return ['success' => false, 'message' => 'Akun tidak aktif. Hubungi Admin.'];
        }

        // Single Active Session: increment auth_version + revoke semua token lama
        $newVersion = (int) $user['auth_version'] + 1;
        $this->userModel->update($user['id'], ['auth_version' => $newVersion]);
        $this->apiTokensModel->revokeAllByUser((int) $user['id']);

        $this->loginAttemptsModel->catat($username, $ipAddress, true);

        $tokens = $this->issueTokenPair((int) $user['id'], $newVersion, $deviceName);

        return [
            'success' => true,
            'message' => 'Login berhasil.',
            'data'    => [
                'access_token'  => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token_plain'],
                'token_type'    => 'Bearer',
                'expires_in'    => $this->accessTokenTtl,
                'user'          => [
                    'id'       => $user['id'],
                    'username' => $user['username'],
                    'role'     => $user['role'],
                    'id_guru'  => $user['id_guru'],
                    'id_siswa' => $user['id_siswa'],
                ],
            ],
        ];
    }

    protected function issueTokenPair(int $userId, int $authVersion, ?string $deviceName = null): array
    {
        $now = time();

        $accessPayload = [
            'sub'          => $userId,
            'auth_version' => $authVersion,
            'iat'          => $now,
            'exp'          => $now + $this->accessTokenTtl,
        ];
        $accessToken = JWT::encode($accessPayload, $this->jwtSecret, 'HS256');

        // Refresh token: random string, disimpan sebagai HASH (SHA-256) — 16_MOBILE_CORDOVA §10
        $refreshPlain = bin2hex(random_bytes(32));
        $refreshHash  = hash('sha256', $refreshPlain);

        $this->apiTokensModel->insert([
            'id_user'            => $userId,
            'token'              => hash('sha256', $accessToken),
            'refresh_token'      => $refreshHash,
            'device_name'        => $deviceName,
            'expires_at'         => date('Y-m-d H:i:s', $now + $this->accessTokenTtl),
            'refresh_expires_at' => date('Y-m-d H:i:s', $now + $this->refreshTokenTtl),
        ]);

        return ['access_token' => $accessToken, 'refresh_token_plain' => $refreshPlain];
    }

    /**
     * Decode & validasi access token JWT. Return data user jika valid.
     * Mengecek auth_version di token vs database (§B5 — Single Active Session).
     */
    public function verifyAccessToken(string $token): ?array
    {
        try {
            $decoded = (array) JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
        } catch (\Throwable $e) {
            return null;
        }

        $user = $this->userModel->find((int) ($decoded['sub'] ?? 0));
        if (!$user || (int) $user['status_aktif'] !== 1) {
            return null;
        }

        if ((int) $user['auth_version'] !== (int) ($decoded['auth_version'] ?? -1)) {
            // Sesi lama — sudah login ulang di device lain
            return null;
        }

        $user['all_roles'] = $this->getUserRoles((int) $user['id']);

        return $user;
    }

    /**
     * @return array{success: bool, message: string, data?: array}
     */
    public function apiRefresh(string $refreshTokenPlain): array
    {
        $hash = hash('sha256', $refreshTokenPlain);
        $row  = $this->apiTokensModel->findActiveByRefreshToken($hash);

        if (!$row) {
            return ['success' => false, 'message' => 'Refresh token tidak valid atau sudah kedaluwarsa.'];
        }

        $user = $this->userModel->find($row['id_user']);
        if (!$user || (int) $user['status_aktif'] !== 1) {
            return ['success' => false, 'message' => 'User tidak aktif.'];
        }

        // Rotasi: revoke token lama, terbitkan pasangan baru
        $this->apiTokensModel->revoke($row['id']);
        $tokens = $this->issueTokenPair((int) $user['id'], (int) $user['auth_version'], $row['device_name']);

        return [
            'success' => true,
            'message' => 'Token berhasil diperbarui.',
            'data'    => [
                'access_token'  => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token_plain'],
                'token_type'    => 'Bearer',
                'expires_in'    => $this->accessTokenTtl,
            ],
        ];
    }

    public function apiLogout(string $accessToken): void
    {
        $hash = hash('sha256', $accessToken);
        $row  = $this->apiTokensModel->where('token', $hash)->first();

        if ($row) {
            $this->apiTokensModel->revoke($row['id']);
        }
    }
}
