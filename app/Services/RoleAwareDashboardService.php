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

    protected function widgetsGuru(int $userId, bool $isWali): array
    {
        $widgets = parent::widgetsGuru($userId, $isWali);
        $jadwal = is_array($widgets['jadwal_hari_ini'] ?? null)
            ? $widgets['jadwal_hari_ini']
            : [];
        $summary = is_array($widgets['task_summary'] ?? null)
            ? $widgets['task_summary']
            : [];

        $summary['belum_presensi'] = 0;
        $summary['belum_jurnal'] = 0;
        $summary['selesai'] = 0;
        $summary['actionable_now'] = 0;

        foreach ($jadwal as $row) {
            $presensiState = (string) ($row['presensi_state'] ?? '');
            $jurnalState = (string) ($row['jurnal_state'] ?? '');
            $presensiApplicable = $presensiState !== 'not_applicable';
            $presensiDone = in_array(
                $presensiState,
                ['not_applicable', 'submitted'],
                true
            );
            $jurnalDone = $jurnalState === 'submitted';

            if ($presensiApplicable && $presensiState !== 'submitted') {
                $summary['belum_presensi']++;
            }

            if (! $jurnalDone) {
                $summary['belum_jurnal']++;
            }

            if ($presensiDone && $jurnalDone) {
                $summary['selesai']++;
            }

            if (
                in_array($presensiState, ['available', 'wali_available'], true)
                || $jurnalState === 'available'
            ) {
                $summary['actionable_now']++;
            }
        }

        $widgets['task_summary'] = $summary;
        $widgets['quick_actions'] = $this->guruQuickActions($userId);
        $widgets['next_schedule'] = $this->nextTeacherSchedule($jadwal);

        return $widgets;
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

    private function guruQuickActions(int $userId): array
    {
        $candidates = [
            [
                'permissions' => ['presensi_siswa.input'],
                'label' => 'Presensi',
                'description' => 'Isi presensi siswa',
                'icon' => 'bx-list-check',
                'url' => 'presensi/siswa',
            ],
            [
                'permissions' => ['presensi_mengajar.input'],
                'label' => 'Jurnal',
                'description' => 'Isi jurnal mengajar',
                'icon' => 'bx-book-content',
                'url' => 'presensi/mengajar',
            ],
            [
                'permissions' => [
                    'jadwal_guru.view',
                    'jadwal_guru.view_all',
                    'jadwal_guru.manage',
                ],
                'label' => 'Jadwal',
                'description' => 'Lihat jadwal mengajar',
                'icon' => 'bx-calendar',
                'url' => 'master/jadwal',
            ],
            [
                'permissions' => ['profile_guru.view'],
                'label' => 'Profil',
                'description' => 'Buka profil saya',
                'icon' => 'bx-user',
                'url' => 'profile/guru',
            ],
        ];

        $actions = [];

        foreach ($candidates as $candidate) {
            if (! $this->canAny($userId, $candidate['permissions'])) {
                continue;
            }

            unset($candidate['permissions']);
            $actions[] = $candidate;
        }

        return $actions;
    }

    private function canAny(int $userId, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->can($userId, (string) $permission)) {
                return true;
            }
        }

        return false;
    }

    private function nextTeacherSchedule(array $jadwal): ?array
    {
        foreach ($jadwal as $row) {
            if (($row['window_state'] ?? '') === 'NOT_STARTED') {
                $row['dashboard_state'] = 'berikutnya';
                return $row;
            }
        }

        foreach ($jadwal as $row) {
            if (($row['window_state'] ?? '') === 'VALID') {
                $row['dashboard_state'] = 'berlangsung';
                return $row;
            }
        }

        return null;
    }
}
