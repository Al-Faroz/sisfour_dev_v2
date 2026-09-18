<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * Period-aware student scope resolver untuk UKS.
 *
 * null  = SEMUA
 * []    = tidak ada siswa pada scope periode terpilih
 * [ids] = siswa yang boleh dibaca
 */
class UksScopeService
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
        int $userId,
        int $idTahun
    ): array {
        if ($idTahun <= 0 || ! $this->yearExists($idTahun)) {
            return $this->fail('INVALID_PERIOD', 'Tahun Ajaran tidak valid.');
        }

        $scope = $this->authService->resolveScope($permission, $userId);

        if ($scope === 'SEMUA') {
            return [
                'success' => true,
                'scope' => 'SEMUA',
                'student_ids' => null,
            ];
        }

        $identity = $this->identity($userId);

        if ($scope === 'DIRI_SENDIRI') {
            $idSiswa = (int) ($identity['id_siswa'] ?? 0);

            if ($idSiswa <= 0) {
                return $this->fail(
                    'NO_STUDENT_IDENTITY',
                    'User tidak memiliki identitas Siswa.'
                );
            }

            return [
                'success' => true,
                'scope' => 'DIRI_SENDIRI',
                'student_ids' => [$idSiswa],
            ];
        }

        if ($scope === 'KELAS_DIAMPU') {
            $idGuru = (int) ($identity['id_guru'] ?? 0);

            if ($idGuru <= 0) {
                return $this->fail(
                    'NO_GURU_IDENTITY',
                    'User tidak memiliki identitas Guru.'
                );
            }

            // Historis: kelas dihitung terhadap periode terpilih, bukan Tahun aktif.
            $kelasIds = $this->authService->getKelasDiampu(
                $idGuru,
                $idTahun
            );

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

        return $this->fail(
            'FORBIDDEN',
            'Anda tidak memiliki hak mengakses data UKS.'
        );
    }

    public function studentAllowed(
        ?array $allowedStudentIds,
        int $idSiswa
    ): bool {
        return $allowedStudentIds === null
            || in_array($idSiswa, $allowedStudentIds, true);
    }

    public function membershipForYear(int $idSiswa, int $idTahun): ?array
    {
        $row = $this->db
            ->table('anggota_kelas ak')
            ->select('ak.id_kelas, k.nama_kelas')
            ->join('kelas k', 'k.id = ak.id_kelas')
            ->where('ak.id_siswa', $idSiswa)
            ->where('ak.id_tahun', $idTahun)
            ->where('k.deleted_at', null)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    public function userPegawaiId(int $userId): ?int
    {
        $id = (int) ($this->identity($userId)['id_pegawai'] ?? 0);
        return $id > 0 ? $id : null;
    }

    private function yearExists(int $idTahun): bool
    {
        return $this->db
            ->table('tahun_ajaran')
            ->where('id', $idTahun)
            ->where('deleted_at', null)
            ->countAllResults() > 0;
    }

    private function identity(int $userId): array
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
