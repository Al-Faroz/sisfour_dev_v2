<?php

namespace App\Services;

/**
 * PermissionService
 *
 * Menerjemahkan hasil resolveScope() (dari AuthService) menjadi CONSTRAINT DATA
 * siap pakai, supaya setiap Controller/Model (Master Data, Presensi, Laporan, BK,
 * Kartu Pelajar, dst.) tidak perlu menulis ulang logika scope masing-masing.
 *
 * PENTING (15_TESTING_POLISH — "Guard for empty array"):
 * Untuk KELAS_DIAMPU / KELAS_TERJADWAL, jika daftar kelas kosong, Model WAJIB
 * langsung mengembalikan collection kosong TANPA menjalankan query builder
 * (hindari SQL `IN ()` yang invalid). Gunakan method idKelasList() di bawah
 * dan cek isEmptyScope() sebelum query.
 *
 * Acuan: 03_AUTH_RBAC_MENU §2.2, §3.3
 */
class PermissionService
{
    protected AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    /**
     * Bentuk constraint scope lengkap untuk satu user & satu permission.
     *
     * @return array{
     *   scope: string,
     *   id_kelas: int[]|null,   // null = SEMUA (tanpa filter), [] = tidak ada akses data
     *   id_guru: int|null,
     *   id_siswa: int|null
     * }
     */
    public function buildConstraint(string $permissionKey, array $sessionData): array
    {
        $userId  = (int) ($sessionData['user_id'] ?? 0);
        $idGuru  = $sessionData['id_guru'] ?? null;
        $idSiswa = $sessionData['id_siswa'] ?? null;

        $scope = $this->authService->resolveScope($permissionKey, $userId);

        $idKelas = match ($scope) {
            'SEMUA'            => null, // null artinya: tidak ada filter kelas
            'KELAS_DIAMPU'     => $this->idKelasDiampu($idGuru),
            'KELAS_TERJADWAL'  => $this->idKelasTerjadwal($idGuru),
            default            => [],
        };

        return [
            'scope'    => $scope,
            'id_kelas' => $idKelas,
            'id_guru'  => $idGuru,
            'id_siswa' => $idSiswa,
        ];
    }

    /**
     * @return int[] daftar id_kelas yang diampu (0 atau 1 kelas — 1 wali = 1 kelas)
     */
    public function idKelasDiampu($idGuru): array
    {
        $idKelas = $this->authService->getKelasDiampu($idGuru ? (int) $idGuru : null);

        return $idKelas ? [$idKelas] : [];
    }

    /**
     * @return int[] daftar id_kelas yang terjadwal untuk guru pada hari ini
     */
    public function idKelasTerjadwal($idGuru): array
    {
        return $this->authService->getKelasTerjadwalHariIni($idGuru ? (int) $idGuru : null);
    }

    /**
     * Helper untuk Model: true jika scope butuh filter kelas TAPI daftarnya kosong
     * (artinya: query TIDAK BOLEH dijalankan, langsung return collection kosong).
     */
    public function isEmptyScope(array $constraint): bool
    {
        return in_array($constraint['scope'], ['KELAS_DIAMPU', 'KELAS_TERJADWAL', 'TIDAK_ADA'], true)
            && empty($constraint['id_kelas']);
    }

    /**
     * Terapkan constraint scope ke sebuah Query Builder secara otomatis.
     * Return null jika builder TIDAK BOLEH dijalankan (scope kosong) —
     * caller wajib cek: if ($builder === null) return [];
     */
    public function applyToBuilder($builder, array $constraint, string $kolomKelas = 'id_kelas')
    {
        if ($this->isEmptyScope($constraint)) {
            return null;
        }

        if ($constraint['id_kelas'] !== null) {
            $builder->whereIn($kolomKelas, $constraint['id_kelas']);
        }

        return $builder;
    }
}
