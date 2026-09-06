<?php

namespace App\Services;

/**
 * PermissionService
 *
 * Menerjemahkan hasil resolveScope() dari AuthService
 * menjadi CONSTRAINT DATA siap pakai.
 *
 * Acuan:
 * - 03_AUTH_RBAC_MENU — SisisFour
 * - 15_TESTING_POLISH — SisisFour
 *
 * Scope:
 * - SEMUA
 * - KELAS_DIAMPU
 * - KELAS_TERJADWAL
 * - DIRI_SENDIRI
 * - TIDAK_ADA
 */
class PermissionService
{
    protected AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    /**
     * Bentuk constraint scope lengkap untuk satu user
     * dan satu permission.
     *
     * @return array{
     *   scope: string,
     *   id_kelas: int[]|null,
     *   id_guru: int|null,
     *   id_siswa: int|null
     * }
     */
    public function buildConstraint(
        string $permissionKey,
        array $sessionData
    ): array {
        $userId = (int) ($sessionData['user_id'] ?? 0);
        $idGuru = isset($sessionData['id_guru'])
            ? (int) $sessionData['id_guru']
            : null;
        $idSiswa = isset($sessionData['id_siswa'])
            ? (int) $sessionData['id_siswa']
            : null;

        if ($idGuru !== null && $idGuru <= 0) {
            $idGuru = null;
        }

        if ($idSiswa !== null && $idSiswa <= 0) {
            $idSiswa = null;
        }

        $scope = $this->authService->resolveScope(
            $permissionKey,
            $userId
        );

        $idKelas = match ($scope) {
            'SEMUA' => null,

            'KELAS_DIAMPU' =>
                $this->idKelasDiampu($idGuru),

            'KELAS_TERJADWAL' =>
                $this->idKelasTerjadwal($idGuru),

            default => [],
        };

        return [
            'scope' => $scope,
            'id_kelas' => $idKelas,
            'id_guru' => $idGuru,
            'id_siswa' => $idSiswa,
        ];
    }

    /**
     * Mengambil daftar id_kelas untuk scope KELAS_DIAMPU.
     *
     * IMPORTANT:
     * AuthService::getKelasDiampu() SUDAH mengembalikan
     * int[] sehingga tidak boleh dibungkus lagi menjadi
     * [$idKelas].
     *
     * Hasil:
     * [1, 2, 3]
     *
     * Bukan:
     * [[1, 2, 3]]
     *
     * @return int[]
     */
    public function idKelasDiampu(?int $idGuru): array
    {
        if (!$idGuru) {
            return [];
        }

        return $this->authService->getKelasDiampu($idGuru);
    }

    /**
     * Mengambil daftar id_kelas yang terjadwal untuk
     * guru pada hari berjalan.
     *
     * @return int[]
     */
    public function idKelasTerjadwal(?int $idGuru): array
    {
        if (!$idGuru) {
            return [];
        }

        return $this->authService->getKelasTerjadwalHariIni(
            $idGuru
        );
    }

    /**
     * Helper untuk Model:
     *
     * true jika scope membutuhkan filter kelas tetapi
     * daftar kelas kosong.
     *
     * Dalam kondisi ini query database TIDAK BOLEH
     * dilanjutkan.
     */
    public function isEmptyScope(array $constraint): bool
    {
        $scope = (string) ($constraint['scope'] ?? '');

        return in_array(
            $scope,
            [
                'KELAS_DIAMPU',
                'KELAS_TERJADWAL',
                'TIDAK_ADA',
            ],
            true
        ) && empty($constraint['id_kelas']);
    }

    /**
     * Terapkan constraint scope ke Query Builder.
     *
     * Return:
     * - null  = scope kosong, query tidak boleh dijalankan
     * - builder = query siap diteruskan
     */
    public function applyToBuilder(
        $builder,
        array $constraint,
        string $kolomKelas = 'id_kelas'
    ) {
        if ($this->isEmptyScope($constraint)) {
            return null;
        }

        /*
         * SEMUA tidak membutuhkan filter kelas.
         */
        if ($constraint['id_kelas'] !== null) {
            $builder->whereIn(
                $kolomKelas,
                $constraint['id_kelas']
            );
        }

        return $builder;
    }
}
