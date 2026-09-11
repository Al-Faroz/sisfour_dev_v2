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

    protected function widgetsSiswa(int $userId): array
    {
        $widgets = parent::widgetsSiswa($userId);
        $widgets['presensi_hari_ini'] = null;

        if (! $this->can($userId, 'presensi_siswa.view')) {
            return $widgets;
        }

        $user = $this->getUser($userId);
        $idSiswa = (int) ($user['id_siswa'] ?? 0);
        $tahun = $this->tahunAktif();
        $idTahun = (int) ($tahun['id'] ?? 0);

        if ($idSiswa <= 0 || $idTahun <= 0) {
            return $widgets;
        }

        $tanggal = $this->today();

        $row = $this->db
            ->table('presensi')
            ->select('tanggal, status')
            ->where('id_siswa', $idSiswa)
            ->where('id_tahun', $idTahun)
            ->where('tanggal', $tanggal)
            ->where('sesi', 'Sesi Awal')
            ->orderBy('id', 'DESC')
            ->get()
            ->getRowArray();

        $widgets['presensi_hari_ini'] = [
            'tanggal' => $tanggal,
            'status' => $row !== null
                ? (string) ($row['status'] ?? '')
                : null,
            'tersedia' => $row !== null,
        ];

        return $widgets;
    }

    protected function waliQuickLinks(int $userId, int $idKelas): array
    {
        $links = parent::waliQuickLinks($userId, $idKelas);

        foreach ($links as &$link) {
            if (
                ($link['label'] ?? '') === 'Presensi Kelas'
                && isset($link['url'])
            ) {
                $link['url'] = 'presensi/siswa?id_kelas=' . $idKelas;
            }
        }
        unset($link);

        $candidates = [
            [
                'permission' => 'laporan_matrix.view',
                'label' => 'Matrix Presensi',
                'url' => 'laporan/presensi/matrix?id_kelas=' . $idKelas,
            ],
            [
                'permission' => 'ews_radar.view',
                'label' => 'EWS Kelas',
                'url' => 'presensi/siswa/ews',
            ],
            [
                'permission' => 'bk_kasus.view',
                'label' => 'Kasus Siswa',
                'url' => 'bk/kasus',
            ],
            [
                'permission' => 'prestasi.view',
                'label' => 'Prestasi Siswa',
                'url' => 'bk/prestasi',
            ],
            [
                'permission' => 'kartu_pelajar.view',
                'label' => 'Kartu Pelajar',
                'url' => 'kartu/daftar',
            ],
        ];

        $existingLabels = array_fill_keys(
            array_map(
                static fn (array $link): string =>
                    (string) ($link['label'] ?? ''),
                $links
            ),
            true
        );

        foreach ($candidates as $candidate) {
            if (
                isset($existingLabels[$candidate['label']])
                || ! $this->can($userId, $candidate['permission'])
            ) {
                continue;
            }

            $links[] = $candidate;
            $existingLabels[$candidate['label']] = true;
        }

        return $links;
    }
}
