<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * Resolver scope siswa untuk BK, Prestasi, dan Kartu.
 *
 * null  = SEMUA
 * []    = tidak ada siswa
 * [ids] = dibatasi ke siswa tertentu
 */
class BkScopeService
{
    protected BaseConnection $db;
    protected AuthService $authService;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->authService = new AuthService();
    }

    public function resolveStudentIds(
        string $permission,
        int $userId
    ): array {
        $scope = $this->authService->resolveScope($permission, $userId);

        if ($scope === 'SEMUA') {
            return [
                'success' => true,
                'scope' => 'SEMUA',
                'student_ids' => null,
            ];
        }

        if ($scope === 'DIRI_SENDIRI') {
            $user = $this->getUserIdentity($userId);
            $idSiswa = (int) ($user['id_siswa'] ?? 0);

            if ($idSiswa <= 0) {
                return $this->fail('NO_STUDENT_IDENTITY', 'User tidak memiliki identitas siswa.');
            }

            return [
                'success' => true,
                'scope' => 'DIRI_SENDIRI',
                'student_ids' => [$idSiswa],
            ];
        }

        if ($scope === 'KELAS_DIAMPU') {
            $user = $this->getUserIdentity($userId);
            $idGuru = (int) ($user['id_guru'] ?? 0);

            if ($idGuru <= 0) {
                return $this->fail('NO_GURU_IDENTITY', 'User tidak memiliki identitas Guru.');
            }

            $tahun = $this->db
                ->table('tahun_ajaran')
                ->select('id')
                ->where('status_aktif', 1)
                ->where('deleted_at', null)
                ->get()
                ->getRowArray();

            $idTahun = (int) ($tahun['id'] ?? 0);

            if ($idTahun <= 0) {
                return $this->fail('NO_ACTIVE_YEAR', 'Tidak ada Tahun Ajaran aktif.');
            }

            $kelasIds = $this->authService->getKelasDiampu($idGuru, $idTahun);

            if ($kelasIds === []) {
                return [
                    'success' => true,
                    'scope' => 'KELAS_DIAMPU',
                    'student_ids' => [],
                ];
            }

            $rows = $this->db
                ->table('anggota_kelas')
                ->select('id_siswa')
                ->where('id_tahun', $idTahun)
                ->whereIn('id_kelas', $kelasIds)
                ->get()
                ->getResultArray();

            return [
                'success' => true,
                'scope' => 'KELAS_DIAMPU',
                'student_ids' => array_values(array_unique(
                    array_map('intval', array_column($rows, 'id_siswa'))
                )),
            ];
        }

        return $this->fail('FORBIDDEN', 'Anda tidak memiliki hak mengakses data ini.');
    }

    public function userGuruId(int $userId): ?int
    {
        $user = $this->getUserIdentity($userId);
        $id = (int) ($user['id_guru'] ?? 0);

        return $id > 0 ? $id : null;
    }

    public function userStudentId(int $userId): ?int
    {
        $user = $this->getUserIdentity($userId);
        $id = (int) ($user['id_siswa'] ?? 0);

        return $id > 0 ? $id : null;
    }

    public function studentAllowed(?array $allowedIds, int $idSiswa): bool
    {
        return $allowedIds === null || in_array($idSiswa, $allowedIds, true);
    }

    private function getUserIdentity(int $userId): array
    {
        return $this->db
            ->table('users')
            ->select('id_guru, id_siswa, id_pegawai')
            ->where('id', $userId)
            ->where('status_aktif', 1)
            ->get()
            ->getRowArray() ?? [];
    }

    private function fail(string $code, string $message): array
    {
        return [
            'success' => false,
            'code' => $code,
            'message' => $message,
        ];
    }
}
