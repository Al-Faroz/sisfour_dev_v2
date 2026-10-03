<?php

namespace App\Services;

/**
 * G3.9 pure Dashboard composition contract.
 *
 * Tidak melakukan query DB dan tidak menjadi authorization boundary.
 * Effective role tetap berasal dari AuthService; service ini hanya menentukan
 * experience owner dan urutan section Dashboard.
 */
final class DashboardCompositionService
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

    /**
     * Dipakai hanya untuk account legacy yang belum mempunyai users.role valid.
     * G3.9 normal path selalu memakai stored Primary Role.
     */
    private const LEGACY_FALLBACK_ORDER = [
        'admin',
        'operator',
        'pimpinan',
        'bk',
        'kesehatan',
        'ptsp',
        'guru',
        'siswa',
    ];

    /**
     * Canonical additional-role ordering; bukan urutan row user_roles.
     */
    private const SECONDARY_ORDER = [
        'operator',
        'pimpinan',
        'kesehatan',
        'ptsp',
    ];

    private const ACTION_SURFACE_ROLES = [
        'bk',
        'kesehatan',
        'ptsp',
        'guru',
    ];

    /**
     * @return array{
     *   primary_role:string,
     *   primary_source:string,
     *   secondary_roles:list<string>,
     *   action_roles:list<string>,
     *   ignored_roles:list<string>
     * }
     */
    public static function compose(
        ?string $storedPrimaryRole,
        array $effectiveRoles,
        bool $isWali
    ): array {
        $effective = self::normalizeRoles($effectiveRoles);
        $primary = self::resolvePrimaryRole($storedPrimaryRole, $effective);
        $secondary = self::resolveSecondaryRoles(
            $primary,
            $effective,
            $isWali
        );

        $ignored = array_values(array_diff(
            $effective,
            array_merge([$primary], $secondary)
        ));

        return [
            'primary_role' => $primary,
            'primary_source' => self::isValidStoredPrimary(
                $storedPrimaryRole,
                $effective
            ) ? 'users.role' : 'legacy_fallback',
            'secondary_roles' => $secondary,
            'action_roles' => self::resolveActionRoles($primary, $secondary),
            'ignored_roles' => $ignored,
        ];
    }

    public static function resolvePrimaryRole(
        ?string $storedPrimaryRole,
        array $effectiveRoles
    ): string {
        $effective = self::normalizeRoles($effectiveRoles);
        $primary = strtolower(trim((string) $storedPrimaryRole));

        if (
            in_array($primary, self::ROLES, true)
            && in_array($primary, $effective, true)
        ) {
            return $primary;
        }

        foreach (self::LEGACY_FALLBACK_ORDER as $role) {
            if (in_array($role, $effective, true)) {
                return $role;
            }
        }

        // Defensive legacy fallback. Normal authenticated G3.9 account should
        // always have a valid Primary Role.
        return 'guru';
    }

    /**
     * @return list<string>
     */
    public static function resolveSecondaryRoles(
        string $primaryRole,
        array $effectiveRoles,
        bool $isWali
    ): array {
        $effective = self::normalizeRoles($effectiveRoles);
        $allowed = self::allowedSecondaryRoles($primaryRole, $isWali);
        $limit = self::secondaryLimit($primaryRole);

        if ($allowed === [] || $limit <= 0) {
            return [];
        }

        $result = [];

        foreach (self::SECONDARY_ORDER as $role) {
            if (
                $role === $primaryRole
                || ! in_array($role, $allowed, true)
                || ! in_array($role, $effective, true)
            ) {
                continue;
            }

            $result[] = $role;

            if (count($result) >= $limit) {
                break;
            }
        }

        return $result;
    }

    /**
     * @return list<string>
     */
    public static function resolveActionRoles(
        string $primaryRole,
        array $secondaryRoles
    ): array {
        $roles = [];

        if (in_array($primaryRole, self::ACTION_SURFACE_ROLES, true)) {
            $roles[] = $primaryRole;
        }

        foreach ($secondaryRoles as $role) {
            if (
                in_array($role, self::ACTION_SURFACE_ROLES, true)
                && ! in_array($role, $roles, true)
            ) {
                $roles[] = $role;
            }
        }

        return $roles;
    }

    /**
     * @return list<string>
     */
    public static function allowedSecondaryRoles(
        string $primaryRole,
        bool $isWali
    ): array {
        if ($primaryRole === 'bk') {
            return ['operator', 'pimpinan', 'kesehatan', 'ptsp'];
        }

        if ($primaryRole === 'guru') {
            return $isWali
                ? ['operator', 'kesehatan']
                : ['operator', 'pimpinan', 'kesehatan', 'ptsp'];
        }

        return [];
    }

    public static function secondaryLimit(string $primaryRole): int
    {
        return match ($primaryRole) {
            'bk' => 2,
            'guru' => 1,
            default => 0,
        };
    }

    /**
     * @return list<string>
     */
    private static function normalizeRoles(array $roles): array
    {
        $result = [];

        foreach ($roles as $role) {
            $role = strtolower(trim((string) $role));

            if (
                $role === ''
                || ! in_array($role, self::ROLES, true)
                || in_array($role, $result, true)
            ) {
                continue;
            }

            $result[] = $role;
        }

        return $result;
    }

    private static function isValidStoredPrimary(
        ?string $storedPrimaryRole,
        array $effectiveRoles
    ): bool {
        $primary = strtolower(trim((string) $storedPrimaryRole));

        return in_array($primary, self::ROLES, true)
            && in_array($primary, $effectiveRoles, true);
    }
}
