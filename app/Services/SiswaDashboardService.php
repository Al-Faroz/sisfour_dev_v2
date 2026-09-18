<?php

namespace App\Services;

/**
 * G3.6 Dashboard Siswa.
 *
 * Hanya experience Siswa yang dioverride. Dashboard Pimpinan G3.5, BK G3.4,
 * dan role lain tetap diwarisi dari service phase sebelumnya.
 *
 * Dashboard Siswa adalah current-state Tahun Ajaran aktif dan seluruh data
 * siswa dibatasi identity login / DIRI_SENDIRI oleh service domain terkait.
 */
class SiswaDashboardService extends PimpinanDashboardService
{
    private const SISWA_LIMIT = 5;

    protected function widgetsSiswa(int $userId): array
    {
        $user = $this->getUser($userId);
        $idSiswa = (int) ($user['id_siswa'] ?? 0);
        $tahun = $this->tahunAktif();
        $idTahun = (int) ($tahun['id'] ?? 0);

        $hasIdentity = $idSiswa > 0;
        $hasActiveYear = $idTahun > 0;

        $canPresensi = $this->can($userId, 'presensi_siswa.view');
        $canPrestasi = $this->can($userId, 'prestasi.view');
        $canPelanggaran = $this->can($userId, 'bk_kasus.view');
        $canKartu = $this->can($userId, 'kartu_pelajar.view');
        $canProfile = $this->can($userId, 'profile_siswa.view');

        return [
            'tahun_aktif' => $tahun,
            'period_available' => $hasActiveYear,
            'identity_available' => $hasIdentity,
            'access' => [
                'presensi' => $canPresensi,
                'prestasi' => $canPrestasi,
                'pelanggaran' => $canPelanggaran,
                'kartu' => $canKartu,
                'profile' => $canProfile,
            ],
            'presensi_hari_ini' => $canPresensi && $hasIdentity && $hasActiveYear
                ? $this->studentAttendanceToday($idSiswa, $idTahun)
                : null,
            'rekap_presensi_bulan_ini' => $canPresensi && $hasIdentity && $hasActiveYear
                ? $this->studentAttendanceMonth($idSiswa, $idTahun)
                : null,
            'presensi_terbaru' => $canPresensi && $hasIdentity && $hasActiveYear
                ? $this->studentRecentAbsence($idSiswa, $idTahun, self::SISWA_LIMIT)
                : [],
            'riwayat_prestasi' => $canPrestasi && $hasIdentity && $hasActiveYear
                ? $this->studentPrestasiForYear($idSiswa, $idTahun, self::SISWA_LIMIT)
                : [],
            'riwayat_pelanggaran' => $canPelanggaran && $hasIdentity && $hasActiveYear
                ? $this->studentCasesForYear($idSiswa, $idTahun, self::SISWA_LIMIT)
                : [],
            'kartu' => $canKartu && $hasIdentity
                ? $this->studentCard($idSiswa)
                : null,
            'profile_available' => $canProfile && $hasIdentity,
            'quick_actions' => $hasIdentity
                ? $this->siswaQuickActions($userId)
                : [],
        ];
    }

    private function studentAttendanceToday(int $idSiswa, int $idTahun): array
    {
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

        return [
            'tanggal' => $tanggal,
            'status' => $row !== null
                ? (string) ($row['status'] ?? '')
                : null,
            'tersedia' => $row !== null,
        ];
    }

    private function studentPrestasiForYear(
        int $idSiswa,
        int $idTahun,
        int $limit
    ): array {
        return $this->db
            ->table('catatan_prestasi')
            ->where('id_siswa', $idSiswa)
            ->where('id_tahun', $idTahun)
            ->orderBy('tanggal', 'DESC')
            ->orderBy('id', 'DESC')
            ->limit($this->limit($limit))
            ->get()
            ->getResultArray();
    }

    private function studentCasesForYear(
        int $idSiswa,
        int $idTahun,
        int $limit
    ): array {
        return $this->db
            ->table('catatan_kasus ck')
            ->select(
                'ck.id, ck.tanggal, ck.keterangan, ' .
                'rp.nama_pelanggaran, rp.kategori'
            )
            ->join('ref_pelanggaran rp', 'rp.id = ck.id_pelanggaran')
            ->where('ck.id_siswa', $idSiswa)
            ->where('ck.id_tahun', $idTahun)
            ->orderBy('ck.tanggal', 'DESC')
            ->orderBy('ck.id', 'DESC')
            ->limit($this->limit($limit))
            ->get()
            ->getResultArray();
    }

    private function siswaQuickActions(int $userId): array
    {
        $candidates = [
            [
                'permission' => 'presensi_siswa.view',
                'label' => 'Presensi Saya',
                'description' => 'Lihat rekap Presensi diri sendiri',
                'icon' => 'bx-list-check',
                'url' => 'presensi/siswa/rekap',
            ],
            [
                'permission' => 'kartu_pelajar.view',
                'label' => 'Kartu',
                'description' => 'Buka Kartu Pelajar saya',
                'icon' => 'bx-id-card',
                'url' => 'kartu/daftar',
            ],
            [
                'permission' => 'prestasi.view',
                'label' => 'Prestasi',
                'description' => 'Lihat riwayat Prestasi saya',
                'icon' => 'bx-trophy',
                'url' => 'bk/prestasi',
            ],
            [
                'permission' => 'profile_siswa.view',
                'label' => 'Profil',
                'description' => 'Buka profil siswa saya',
                'icon' => 'bx-user',
                'url' => 'profile/siswa',
            ],
        ];

        $actions = [];

        foreach ($candidates as $candidate) {
            if (! $this->can($userId, $candidate['permission'])) {
                continue;
            }

            unset($candidate['permission']);
            $actions[] = $candidate;
        }

        return $actions;
    }

    private function limit(int $limit): int
    {
        return max(1, min(self::SISWA_LIMIT, $limit));
    }
}
