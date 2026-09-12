<?php

namespace App\Services;

/**
 * DashboardService dengan experience role dan contextual dashboard enhancement.
 *
 * Canonical priority:
 * admin > operator > pimpinan > bk > guru > siswa
 *
 * BK diprioritaskan sebelum Guru karena akun BK dapat tetap mempunyai
 * identity/secondary role Guru. Wali Kelas tetap context Guru, bukan role.
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
        $widgets['kelas_aktif'] = [];

        $user = $this->getUser($userId);
        $idSiswa = (int) ($user['id_siswa'] ?? 0);
        $tahun = $this->tahunAktif();
        $idTahun = (int) ($tahun['id'] ?? 0);

        if ($idSiswa > 0 && $idTahun > 0) {
            $widgets['kelas_aktif'] = $this->studentClassSummary(
                $idSiswa,
                $idTahun
            );
        }

        if (! $this->can($userId, 'presensi_siswa.view')) {
            return $widgets;
        }

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

    protected function waliSummary(
        int $userId,
        int $idGuru,
        int $idTahun
    ): array {
        $summary = parent::waliSummary($userId, $idGuru, $idTahun);

        if ($summary === []) {
            return [];
        }

        $jumlahSiswa = (int) ($summary['jumlah_siswa'] ?? 0);
        $presensi = $summary['presensi_hari_ini'] ?? [];
        $jumlahTercatat =
            (int) ($presensi['hadir'] ?? 0)
            + (int) ($presensi['sakit'] ?? 0)
            + (int) ($presensi['izin'] ?? 0)
            + (int) ($presensi['alpha'] ?? 0);

        $summary['presensi_hari_ini']['jumlah_tercatat'] = $jumlahTercatat;
        $summary['presensi_hari_ini']['tersedia'] = $jumlahTercatat > 0;
        $summary['presensi_hari_ini']['lengkap'] = $jumlahSiswa > 0
            && $jumlahTercatat >= $jumlahSiswa;

        $idKelas = (int) ($summary['id_kelas'] ?? 0);
        $summary['primary_action'] = null;

        if ($idKelas > 0 && $jumlahTercatat > 0) {
            if ($this->can($userId, 'presensi_siswa.revisi')) {
                $summary['primary_action'] = [
                    'label' => 'Revisi Presensi Kelas',
                    'icon' => 'bx-edit',
                    'url' => 'presensi/siswa/revisi/' . $idKelas
                        . '?tanggal=' . rawurlencode($this->today())
                        . '&sesi=' . rawurlencode('Sesi Awal'),
                ];
            }
        } elseif ($idKelas > 0 && $this->can($userId, 'presensi_siswa.input')) {
            $summary['primary_action'] = [
                'label' => 'Isi Presensi Kelas',
                'icon' => 'bx-check-square',
                'url' => 'presensi/siswa/input/' . $idKelas
                    . '?tanggal=' . rawurlencode($this->today())
                    . '&sesi=' . rawurlencode('Sesi Awal'),
            ];
        }

        return $summary;
    }

    protected function waliQuickLinks(int $userId, int $idKelas): array
    {
        $links = array_values(array_filter(
            parent::waliQuickLinks($userId, $idKelas),
            static fn (array $link): bool =>
                (string) ($link['label'] ?? '') !== 'Presensi Kelas'
        ));

        $candidates = [
            [
                'permission' => 'laporan_matrix.view',
                'label' => 'Matrix Presensi',
                'icon' => 'bx-grid-alt',
                'url' => 'laporan/presensi/matrix?id_kelas=' . $idKelas,
            ],
            [
                'permission' => 'ews_radar.view',
                'label' => 'EWS Kelas',
                'icon' => 'bx-radar',
                'url' => 'presensi/siswa/ews',
            ],
            [
                'permission' => 'bk_kasus.view',
                'label' => 'Kasus Siswa',
                'icon' => 'bx-note',
                'url' => 'bk/kasus',
            ],
            [
                'permission' => 'prestasi.view',
                'label' => 'Prestasi Siswa',
                'icon' => 'bx-trophy',
                'url' => 'bk/prestasi',
            ],
            [
                'permission' => 'kartu_pelajar.view',
                'label' => 'Kartu Pelajar',
                'icon' => 'bx-id-card',
                'url' => 'kartu/daftar',
            ],
        ];

        foreach ($links as &$link) {
            $label = (string) ($link['label'] ?? '');
            $link['icon'] = match ($label) {
                'Rekap Presensi' => 'bx-bar-chart-alt-2',
                'Data Siswa' => 'bx-group',
                default => 'bx-link-external',
            };
        }
        unset($link);

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

    private function studentClassSummary(int $idSiswa, int $idTahun): array
    {
        $row = $this->db
            ->table('anggota_kelas ak')
            ->select(
                'ak.id_kelas, k.nama_kelas, k.tingkat, k.rombel, '
                . 'g.id AS id_wali, g.nama AS nama_wali, '
                . 'g.no_telepon AS no_telepon_wali'
            )
            ->join(
                'kelas k',
                'k.id = ak.id_kelas AND k.deleted_at IS NULL',
                'inner',
                false
            )
            ->join(
                'mapping_wali_kelas mw',
                'mw.id_kelas = ak.id_kelas '
                . 'AND mw.id_tahun = ak.id_tahun '
                . 'AND mw.deleted_at IS NULL',
                'left',
                false
            )
            ->join(
                'guru g',
                'g.id = mw.id_guru AND g.deleted_at IS NULL',
                'left',
                false
            )
            ->where('ak.id_siswa', $idSiswa)
            ->where('ak.id_tahun', $idTahun)
            ->get()
            ->getRowArray();

        if (! $row) {
            return [];
        }

        $phone = trim((string) ($row['no_telepon_wali'] ?? ''));
        $whatsapp = $this->normalizeWhatsappNumber($phone);

        return [
            'id_kelas' => (int) ($row['id_kelas'] ?? 0),
            'nama_kelas' => (string) ($row['nama_kelas'] ?? ''),
            'tingkat' => (string) ($row['tingkat'] ?? ''),
            'rombel' => (string) ($row['rombel'] ?? ''),
            'id_wali' => isset($row['id_wali']) && $row['id_wali'] !== null
                ? (int) $row['id_wali']
                : null,
            'nama_wali' => trim((string) ($row['nama_wali'] ?? '')),
            'no_telepon_wali' => $phone,
            'whatsapp_number' => $whatsapp,
        ];
    }

    private function normalizeWhatsappNumber(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', trim($phone));

        if (! is_string($digits) || $digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62' . $digits;
        }

        if (! str_starts_with($digits, '62')) {
            return null;
        }

        $length = strlen($digits);

        if ($length < 10 || $length > 15) {
            return null;
        }

        return $digits;
    }
}
