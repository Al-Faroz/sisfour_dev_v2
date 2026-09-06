<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * AuthService
 *
 * Menangani:
 * - autentikasi web berbasis session
 * - rate limiting login
 * - auth_version
 * - multi-role
 * - resolusi permission scope
 * - status Wali Kelas dinamis
 * - daftar kelas yang diampu
 * - daftar kelas terjadwal hari ini
 *
 * Catatan:
 * - Wali Kelas bukan role.
 * - Status Wali selalu dihitung dinamis dari mapping_wali_kelas.
 * - JWT/API ditangani pada tahap API/Mobile.
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
     * Aturan:
     * - username/password wajib diisi
     * - 5 kegagalan berturut-turut untuk username yang sama
     *   menyebabkan lock selama 5 menit
     * - login sukses memutus rangkaian kegagalan
     * - auth_version dinaikkan setiap login sukses
     *
     * @return array{
     *     success: bool,
     *     message: string,
     *     user?: array
     * }
     */
    public function attemptLogin(
        string $username,
        string $password
    ): array {
        $username = trim($username);

        if ($username === '' || $password === '') {
            return [
                'success' => false,
                'message' => 'Username dan password wajib diisi.',
            ];
        }

        /*
         * Cek lock berdasarkan username.
         */
        if ($this->isLocked($username)) {
            return [
                'success' => false,
                'message' => 'Akun terkunci sementara. Coba lagi dalam beberapa menit.',
            ];
        }

        /*
         * Ambil user aktif.
         */
        $user = $this->db
            ->table('users')
            ->where('username', $username)
            ->where('status_aktif', 1)
            ->get()
            ->getRowArray();

        /*
         * User tidak ditemukan / tidak aktif.
         *
         * Tetap dicatat sebagai kegagalan berdasarkan username.
         */
        if (!$user) {
            $this->recordAttempt($username, false);

            return [
                'success' => false,
                'message' => 'Username atau password salah.',
            ];
        }

        /*
         * Verifikasi password menggunakan bcrypt/password_verify().
         */
        $passwordValid = password_verify(
            $password,
            (string) $user['password']
        );

        if (!$passwordValid) {
            $this->recordAttempt($username, false);

            /*
             * Setelah kegagalan ke-5, langsung dianggap locked.
             */
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

        /*
         * Login berhasil.
         *
         * Record success memutus rangkaian kegagalan berturut-turut.
         */
        $this->recordAttempt($username, true);

        /*
         * Single Active Session:
         * setiap login sukses menaikkan auth_version.
         *
         * Session/token lama dengan auth_version sebelumnya
         * otomatis menjadi tidak valid.
         */
        $newAuthVersion = ((int) ($user['auth_version'] ?? 0)) + 1;

        $updated = $this->db
            ->table('users')
            ->where('id', $user['id'])
            ->update([
                'auth_version' => $newAuthVersion,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        if (!$updated) {
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
     *
     * Wali Kelas TIDAK disimpan di session.
     * Status Wali Kelas selalu dihitung secara dinamis.
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

    /**
     * Mengecek apakah username sedang terkunci.
     *
     * Aturan:
     * - dihitung berdasarkan kegagalan BERTURUT-TURUT;
     * - keberhasilan login memutus rangkaian;
     * - setelah 5 kegagalan berturut-turut, username terkunci 5 menit;
     * - lock dihitung dari waktu kegagalan ke-5.
     *
     * Contoh:
     * gagal
     * gagal
     * gagal
     * sukses
     * gagal
     *
     * Hanya 1 kegagalan terakhir yang dihitung.
     */
    protected function isLocked(string $username): bool
    {
        /*
         * Ambil 5 attempt terakhir untuk username.
         *
         * Tidak menggunakan WHERE waktu >= -5 menit sebagai
         * dasar utama karena yang menentukan lock adalah 5
         * kegagalan berturut-turut, lalu lock berlangsung 5 menit
         * sejak kegagalan ke-5.
         */
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

        /*
         * Lima attempt terakhir harus semuanya gagal.
         */
        foreach ($attempts as $attempt) {
            if ((int) ($attempt['berhasil'] ?? 0) === 1) {
                return false;
            }
        }

        /*
         * Attempt paling lama dari lima kegagalan tersebut
         * adalah titik terjadinya kegagalan ke-5 berturut-turut.
         *
         * Karena hasil diurutkan DESC:
         * [0] = terbaru
         * [4] = paling lama
         */
        $lockStartedAt = strtotime((string) ($attempts[4]['waktu'] ?? ''));

        if ($lockStartedAt === false) {
            return false;
        }

        /*
         * Lock berlaku selama 5 menit sejak kegagalan ke-5.
         */
        return time() < ($lockStartedAt + (5 * 60));
    }

    /**
     * Menyimpan histori percobaan login.
     */
    protected function recordAttempt(
        string $username,
        bool $berhasil
    ): void {
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
     * Ambil semua role milik user.
     *
     * Role utama:
     * users.role
     *
     * Role tambahan:
     * user_roles.role
     *
     * Permission user adalah UNION dari keduanya.
     *
     * @return string[]
     */
    public function getUserRoles(int $userId): array
    {
        $roles = [];

        /*
         * Role utama.
         */
        $primary = $this->db
            ->table('users')
            ->select('role')
            ->where('id', $userId)
            ->get()
            ->getRowArray();

        if ($primary && !empty($primary['role'])) {
            $roles[] = (string) $primary['role'];
        }

        /*
         * Multi-role.
         */
        $extra = $this->db
            ->table('user_roles')
            ->select('role')
            ->where('id_user', $userId)
            ->get()
            ->getResultArray();

        foreach ($extra as $row) {
            if (!empty($row['role'])) {
                $roles[] = (string) $row['role'];
            }
        }

        return array_values(array_unique($roles));
    }

    /**
     * Resolusi scope permission berdasarkan seluruh role user.
     *
     * Prioritas:
     *
     * SEMUA
     * > KELAS_DIAMPU / KELAS_TERJADWAL
     * > DIRI_SENDIRI
     * > TIDAK_ADA
     *
     * Catatan penting:
     * KELAS_DIAMPU adalah scope kontekstual Wali Kelas.
     * Jika user bukan Wali Kelas aktif, scope tersebut tidak
     * boleh menghasilkan akses.
     */
    public function resolveScope(
        string $permissionKey,
        int $userId
    ): string {
        $roles = $this->getUserRoles($userId);

        if (empty($roles)) {
            return 'TIDAK_ADA';
        }

        $rows = $this->db
            ->table('role_permissions rp')
            ->select('rp.scope')
            ->join(
                'permissions p',
                'p.id = rp.id_permission'
            )
            ->whereIn('rp.role', $roles)
            ->where(
                'p.permission_key',
                $permissionKey
            )
            ->get()
            ->getResultArray();

        if (empty($rows)) {
            return 'TIDAK_ADA';
        }

        /*
         * Prioritas scope.
         */
        $priority = [
            'SEMUA' => 4,
            'KELAS_DIAMPU' => 3,
            'KELAS_TERJADWAL' => 3,
            'DIRI_SENDIRI' => 2,
            'TIDAK_ADA' => 0,
        ];

        $best = 'TIDAK_ADA';
        $bestScore = 0;

        foreach ($rows as $row) {
            $scope = (string) ($row['scope'] ?? '');
            $score = $priority[$scope] ?? 0;

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $scope;
            }
        }

        /*
         * KELAS_DIAMPU adalah akses kontekstual Wali Kelas.
         *
         * Jangan memberikan scope ini kepada Guru Biasa hanya
         * karena permission ditemukan dari role/multi-role.
         */
        if ($best === 'KELAS_DIAMPU') {
            $user = $this->db
                ->table('users')
                ->select('id_guru')
                ->where('id', $userId)
                ->get()
                ->getRowArray();

            $idGuru = isset($user['id_guru'])
                ? (int) $user['id_guru']
                : 0;

            if ($idGuru <= 0 || !$this->isWaliKelas($idGuru)) {
                return 'TIDAK_ADA';
            }
        }

        return $best;
    }

    /**
     * Mengecek apakah guru merupakan Wali Kelas
     * pada Tahun Ajaran tertentu.
     *
     * Status Wali Kelas bersifat dinamis.
     *
     * @param int|null $idGuru
     * @param int|null $idTahun
     */
    public function isWaliKelas(
        ?int $idGuru,
        ?int $idTahun = null
    ): bool {
        if (!$idGuru) {
            return false;
        }

        /*
         * Jika tahun tidak diberikan, gunakan tahun ajaran aktif.
         */
        if ($idTahun === null) {
            $tahun = $this->db
                ->table('tahun_ajaran')
                ->where('status_aktif', 1)
                ->get()
                ->getRowArray();

            $idTahun = isset($tahun['id'])
                ? (int) $tahun['id']
                : null;
        }

        if (!$idTahun) {
            return false;
        }

        return (bool) $this->db
            ->table('mapping_wali_kelas')
            ->where('id_guru', $idGuru)
            ->where('id_tahun', $idTahun)
            ->where('deleted_at', null)
            ->countAllResults();
    }

    /**
     * Ambil daftar kelas yang sedang menjadi kelas wali
     * untuk scope KELAS_DIAMPU.
     *
     * @return int[]
     */
    public function getKelasDiampu(
        ?int $idGuru,
        ?int $idTahun = null
    ): array {
        if (!$idGuru) {
            return [];
        }

        /*
         * Jika tahun tidak diberikan, gunakan tahun ajaran aktif.
         */
        if ($idTahun === null) {
            $tahun = $this->db
                ->table('tahun_ajaran')
                ->where('status_aktif', 1)
                ->get()
                ->getRowArray();

            $idTahun = isset($tahun['id'])
                ? (int) $tahun['id']
                : null;
        }

        if (!$idTahun) {
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
                array_map(
                    'intval',
                    array_column($rows, 'id_kelas')
                )
            )
        );
    }

    /**
     * Ambil kelas yang terjadwal untuk guru hari ini.
     *
     * @return int[]
     */
    public function getKelasTerjadwalHariIni(
        ?int $idGuru,
        ?int $idTahun = null
    ): array {
        if (!$idGuru) {
            return [];
        }

        /*
         * Jika tahun tidak diberikan, gunakan tahun ajaran aktif.
         */
        if ($idTahun === null) {
            $tahun = $this->db
                ->table('tahun_ajaran')
                ->where('status_aktif', 1)
                ->get()
                ->getRowArray();

            $idTahun = isset($tahun['id'])
                ? (int) $tahun['id']
                : null;
        }

        if (!$idTahun) {
            return [];
        }

        $hariIni = $this->hariIndonesia();

        $rows = $this->db
            ->table('jadwal_guru')
            ->select('id_kelas')
            ->where('id_guru', $idGuru)
            ->where('id_tahun', $idTahun)
            ->where('hari', $hariIni)
            ->where('status_jadwal', 'Aktif')
            ->get()
            ->getResultArray();

        return array_values(
            array_unique(
                array_map(
                    'intval',
                    array_column($rows, 'id_kelas')
                )
            )
        );
    }

    /**
     * Nama hari Indonesia.
     */
    protected function hariIndonesia(): string
    {
        $map = [
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
            'Sunday' => 'Minggu',
        ];

        return $map[date('l')] ?? 'Senin';
    }
}
