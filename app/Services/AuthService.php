<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * AuthService
 *
 * Menangani autentikasi (web session & JWT), resolusi scope RBAC,
 * dan pengecekan status Wali Kelas (dinamis, TIDAK disimpan di session).
 *
 * Acuan: 03_AUTH_RBAC_MENU §1, §2.2, §3
 */
class AuthService
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Proses login web (session-based).
     *
     * @return array{success:bool, message:string, user?:array}
     */
    public function attemptLogin(string $username, string $password): array
    {
        // 1. Rate limiting: 5x gagal berturut-turut (berbasis username) -> lock 5 menit
        if ($this->isLocked($username)) {
            return ['success' => false, 'message' => 'Akun terkunci sementara. Coba lagi dalam beberapa menit.'];
        }

        $user = $this->db->table('users')
            ->where('username', $username)
            ->where('status_aktif', 1)
            ->get()
            ->getRowArray();

        if (!$user || !password_verify($password, $user['password'])) {
            $this->recordAttempt($username, false);
            return ['success' => false, 'message' => 'Username atau password salah.'];
        }

        // Login sukses -> reset attempts, increment auth_version (single active session)
        $this->recordAttempt($username, true);

        $newAuthVersion = (int) $user['auth_version'] + 1;
        $this->db->table('users')->where('id', $user['id'])->update([
            'auth_version' => $newAuthVersion,
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);
        $user['auth_version'] = $newAuthVersion;

        return ['success' => true, 'message' => 'Login berhasil.', 'user' => $user];
    }

    /**
     * Simpan struktur session baku setelah login berhasil (WAJIB — 03_AUTH_RBAC_MENU §1.3).
     */
    public function setUserSession(array $user): void
    {
        session()->set([
            'user_id'      => $user['id'],
            'role'         => $user['role'],
            'username'     => $user['username'],
            'id_guru'      => $user['id_guru'] ?? null,
            'id_siswa'     => $user['id_siswa'] ?? null,
            'id_pegawai'   => $user['id_pegawai'] ?? null,
            'auth_version' => $user['auth_version'],
            'logged_in'    => true,
        ]);
    }

    protected function isLocked(string $username): bool
    {
        $fiveMinutesAgo = date('Y-m-d H:i:s', strtotime('-5 minutes'));

        $failedCount = $this->db->table('login_attempts')
            ->where('username', $username)
            ->where('berhasil', 0)
            ->where('waktu >=', $fiveMinutesAgo)
            ->countAllResults();

        return $failedCount >= 5;
    }

    protected function recordAttempt(string $username, bool $berhasil): void
    {
        $this->db->table('login_attempts')->insert([
            'username'   => $username,
            'ip_address' => service('request')->getIPAddress(),
            'waktu'      => date('Y-m-d H:i:s'),
            'berhasil'   => $berhasil ? 1 : 0,
        ]);

        // Kalau berhasil, tidak perlu hapus histori gagal — retensi tabel biarkan tumbuh,
        // penghitungan lock selalu berbasis window 5 menit terakhir (lihat isLocked()).
    }

    /**
     * Ambil semua role milik user (role utama + user_roles / multi-role).
     *
     * @return string[]
     */
    public function getUserRoles(int $userId): array
    {
        $roles = [];

        $primary = $this->db->table('users')->select('role')->where('id', $userId)->get()->getRowArray();
        if ($primary) {
            $roles[] = $primary['role'];
        }

        $extra = $this->db->table('user_roles')->select('role')->where('id_user', $userId)->get()->getResultArray();
        foreach ($extra as $row) {
            $roles[] = $row['role'];
        }

        return array_values(array_unique($roles));
    }

    /**
     * Resolusi scope untuk sebuah permission_key berdasarkan seluruh role user.
     *
     * Jika user memiliki permission ini di lebih dari satu role dengan scope berbeda,
     * ambil scope "terluas": SEMUA > KELAS_DIAMPU/KELAS_TERJADWAL > DIRI_SENDIRI.
     *
     * Acuan: 03_AUTH_RBAC_MENU §3.2
     */
    public function resolveScope(string $permissionKey, int $userId): string
    {
        $roles = $this->getUserRoles($userId);
        if (empty($roles)) {
            return 'TIDAK_ADA';
        }

        $rows = $this->db->table('role_permissions rp')
            ->select('rp.scope')
            ->join('permissions p', 'p.id = rp.id_permission')
            ->whereIn('rp.role', $roles)
            ->where('p.permission_key', $permissionKey)
            ->get()
            ->getResultArray();

        if (empty($rows)) {
            return 'TIDAK_ADA';
        }

        $priority = ['SEMUA' => 4, 'KELAS_DIAMPU' => 3, 'KELAS_TERJADWAL' => 3, 'DIRI_SENDIRI' => 2, 'TIDAK_ADA' => 0];

        $best = 'TIDAK_ADA';
        $bestScore = -1;
        foreach ($rows as $row) {
            $scope = $row['scope'];
            $score = $priority[$scope] ?? 1;
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $scope;
            }
        }

        return $best;
    }

    /**
     * Cek status Wali Kelas secara dinamis (JANGAN pernah simpan di session).
     * Acuan: 03_AUTH_RBAC_MENU §3.4
     */
    public function isWaliKelas(?int $idGuru, ?int $idTahun = null): bool
    {
        if (!$idGuru) {
            return false;
        }

        if ($idTahun === null) {
            $tahun = $this->db->table('tahun_ajaran')->where('status_aktif', 1)->get()->getRowArray();
            $idTahun = $tahun['id'] ?? null;
        }

        if (!$idTahun) {
            return false;
        }

        return (bool) $this->db->table('mapping_wali_kelas')
            ->where('id_guru', $idGuru)
            ->where('id_tahun', $idTahun)
            ->where('deleted_at', null)
            ->countAllResults();
    }

    /**
     * Ambil daftar id_kelas yang diampu oleh guru (untuk scope KELAS_DIAMPU).
     *
     * @return int[]
     */
    public function getKelasDiampu(int $idGuru, ?int $idTahun = null): array
    {
        if ($idTahun === null) {
            $tahun = $this->db->table('tahun_ajaran')->where('status_aktif', 1)->get()->getRowArray();
            $idTahun = $tahun['id'] ?? null;
        }
        if (!$idTahun) {
            return [];
        }

        $rows = $this->db->table('mapping_wali_kelas')
            ->select('id_kelas')
            ->where('id_guru', $idGuru)
            ->where('id_tahun', $idTahun)
            ->where('deleted_at', null)
            ->get()
            ->getResultArray();

        return array_column($rows, 'id_kelas');
    }

    /**
     * Ambil daftar id_kelas yang terjadwal untuk guru pada hari ini (scope KELAS_TERJADWAL).
     *
     * @return int[]
     */
    public function getKelasTerjadwalHariIni(int $idGuru, ?int $idTahun = null): array
    {
        if ($idTahun === null) {
            $tahun = $this->db->table('tahun_ajaran')->where('status_aktif', 1)->get()->getRowArray();
            $idTahun = $tahun['id'] ?? null;
        }
        if (!$idTahun) {
            return [];
        }

        $hariIni = $this->hariIndonesia();

        $rows = $this->db->table('jadwal_guru')
            ->select('id_kelas')
            ->where('id_guru', $idGuru)
            ->where('id_tahun', $idTahun)
            ->where('hari', $hariIni)
            ->where('status_jadwal', 'Aktif')
            ->get()
            ->getResultArray();

        return array_values(array_unique(array_column($rows, 'id_kelas')));
    }

    protected function hariIndonesia(): string
    {
        $map = [
            'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu',
        ];
        return $map[date('l')] ?? 'Senin';
    }
}
