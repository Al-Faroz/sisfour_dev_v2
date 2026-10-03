<?php

namespace App\Services;

/**
 * G3.9 role-assignment policy.
 *
 * Pure service: tidak melakukan query DB dan tidak menggantikan authorization.
 * Ia menjaga assignment Role 1/secondary + person identity tetap sesuai contract
 * sebelum state tersebut disimpan oleh Settings User atau dipakai sebagai Wali.
 */
final class RoleAssignmentPolicyService
{
    private const ROLES = [
        'admin',
        'operator',
        'pimpinan',
        'bk',
        'kesehatan',
        'ptsp',
        'guru',
        'siswa',
    ];

    private const SECONDARY_ORDER = [
        'operator',
        'pimpinan',
        'kesehatan',
        'ptsp',
    ];

    private const STAFF_OPERATIONAL_ROLES = [
        'bk',
        'kesehatan',
        'ptsp',
    ];

    /**
     * @return array{
     *   success:bool,
     *   code:string,
     *   message:string,
     *   secondary_roles:list<string>
     * }
     */
    public static function validate(
        ?string $primaryRole,
        array $secondaryRoles,
        ?int $idGuru,
        ?int $idPegawai,
        ?int $idSiswa,
        bool $isWali
    ): array {
        $primary = strtolower(trim((string) $primaryRole));
        $secondary = self::normalizeRoles($secondaryRoles);

        if ($primary !== '' && ! in_array($primary, self::ROLES, true)) {
            return self::fail(
                'ROLE_INVALID',
                'Primary role tidak valid.'
            );
        }

        foreach ($secondary as $role) {
            if (! in_array($role, self::ROLES, true)) {
                return self::fail(
                    'ROLE_INVALID',
                    'Secondary role tidak valid.'
                );
            }
        }

        if ($primary !== '') {
            $secondary = array_values(array_diff($secondary, [$primary]));
        }

        $identityCount = count(array_filter([
            $idGuru,
            $idPegawai,
            $idSiswa,
        ], static fn ($id): bool => (int) $id > 0));

        if ($identityCount > 1) {
            return self::fail(
                'MULTI_IDENTITY',
                'Satu user hanya boleh terhubung ke satu identitas Guru, Pegawai, atau Siswa.'
            );
        }

        if ($primary === '') {
            if ($secondary !== []) {
                return self::fail(
                    'ROLE_COMBINATION',
                    'Secondary role tidak dapat digunakan tanpa Primary Role.'
                );
            }

            if ($isWali && (int) $idGuru > 0) {
                return self::fail(
                    'WALI_PRIMARY_ROLE',
                    'Guru yang aktif sebagai Wali harus menggunakan Primary Role Guru.'
                );
            }

            if ((int) $idSiswa > 0) {
                return self::fail(
                    'STUDENT_IDENTITY_ROLE',
                    'Identitas Siswa harus menggunakan Primary Role Siswa.'
                );
            }

            return self::pass([]);
        }

        if ($primary === 'admin' || $primary === 'siswa') {
            if ($secondary !== []) {
                return self::fail(
                    'ROLE_COMBINATION',
                    ucfirst($primary) . ' bersifat eksklusif dan tidak boleh memiliki secondary role.'
                );
            }
        } else {
            $allowed = DashboardCompositionService::allowedSecondaryRoles(
                $primary,
                $isWali
            );
            $limit = DashboardCompositionService::secondaryLimit($primary);

            if ($secondary !== [] && $allowed === []) {
                return self::fail(
                    'ROLE_COMBINATION',
                    ucfirst($primary) . ' tidak dapat menjadi Primary Role multi-role pada contract G3.9.'
                );
            }

            if (count($secondary) > $limit) {
                return self::fail(
                    'ROLE_COMBINATION',
                    sprintf(
                        'Primary Role %s hanya boleh memiliki maksimal %d secondary role.',
                        ucfirst($primary),
                        $limit
                    )
                );
            }

            foreach ($secondary as $role) {
                if (! in_array($role, $allowed, true)) {
                    return self::fail(
                        'ROLE_COMBINATION',
                        sprintf(
                            'Kombinasi %s + %s tidak diizinkan pada contract G3.9%s.',
                            ucfirst($primary),
                            ucfirst($role),
                            $isWali ? ' untuk Guru yang aktif sebagai Wali' : ''
                        )
                    );
                }
            }
        }

        if ((int) $idSiswa > 0 && $primary !== 'siswa') {
            return self::fail(
                'STUDENT_IDENTITY_ROLE',
                'Identitas Siswa tidak boleh menerima role operasional personel.'
            );
        }

        if ($primary === 'siswa' && (int) $idSiswa <= 0) {
            return self::fail(
                'STUDENT_IDENTITY_REQUIRED',
                'Role Siswa memerlukan relasi Siswa.'
            );
        }

        if ($primary === 'guru' && (int) $idGuru <= 0) {
            return self::fail(
                'GURU_IDENTITY_REQUIRED',
                'Role Guru memerlukan relasi Guru.'
            );
        }

        if ($isWali && (int) $idGuru > 0 && $primary !== 'guru') {
            return self::fail(
                'WALI_PRIMARY_ROLE',
                'Guru yang aktif sebagai Wali harus menggunakan Primary Role Guru.'
            );
        }

        $effectiveRoles = array_merge([$primary], $secondary);
        $needsStaffIdentity = array_intersect(
            self::STAFF_OPERATIONAL_ROLES,
            $effectiveRoles
        ) !== [];

        if (
            $needsStaffIdentity
            && (int) $idGuru <= 0
            && (int) $idPegawai <= 0
        ) {
            return self::fail(
                'STAFF_IDENTITY_REQUIRED',
                'Role BK/Kesehatan/PTSP memerlukan staff identity Guru atau Pegawai.'
            );
        }

        return self::pass(self::canonicalSecondary($secondary));
    }

    /**
     * @return list<string>
     */
    private static function normalizeRoles(array $roles): array
    {
        $result = [];

        foreach ($roles as $role) {
            $role = strtolower(trim((string) $role));

            if ($role === '' || in_array($role, $result, true)) {
                continue;
            }

            $result[] = $role;
        }

        return $result;
    }

    /**
     * @return list<string>
     */
    private static function canonicalSecondary(array $roles): array
    {
        $result = [];

        foreach (self::SECONDARY_ORDER as $role) {
            if (in_array($role, $roles, true)) {
                $result[] = $role;
            }
        }

        return $result;
    }

    /**
     * @return array{
     *   success:true,
     *   code:string,
     *   message:string,
     *   secondary_roles:list<string>
     * }
     */
    private static function pass(array $secondaryRoles): array
    {
        return [
            'success' => true,
            'code' => 'OK',
            'message' => 'Role assignment valid.',
            'secondary_roles' => $secondaryRoles,
        ];
    }

    /**
     * @return array{
     *   success:false,
     *   code:string,
     *   message:string,
     *   secondary_roles:list<string>
     * }
     */
    private static function fail(string $code, string $message): array
    {
        return [
            'success' => false,
            'code' => $code,
            'message' => $message,
            'secondary_roles' => [],
        ];
    }
}
