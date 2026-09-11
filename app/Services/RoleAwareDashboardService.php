<?php

namespace App\Services;

/**
 * DashboardService dengan priority experience role yang konsisten
 * terhadap effective role dan managed Guru identity.
 *
 * SettingsUserService mempertahankan role Guru sebagai secondary role
 * ketika sebuah akun mempunyai id_guru. Karena itu BK harus diprioritaskan
 * sebelum Guru agar akun BK+Guru tetap memperoleh Dashboard BK.
 *
 * Semua widget dan data authorization tetap diwarisi dari DashboardService.
 */
class RoleAwareDashboardService extends DashboardService
{
    public function resolveDashboardRole(array $roles): string
    {
        foreach (
            ['admin', 'operator', 'pimpinan', 'bk', 'guru', 'siswa']
            as $role
        ) {
            if (in_array($role, $roles, true)) {
                return $role;
            }
        }

        return 'guru';
    }
}
