<?php

namespace App\Services;

/**
 * G3.5 Dashboard Pimpinan.
 *
 * Hanya experience Pimpinan yang dioverride. Dashboard BK G3.4 dan role lain
 * tetap diwarisi dari service phase sebelumnya.
 *
 * Dashboard Pimpinan adalah current-state Tahun Ajaran aktif. Shortcut hanya
 * membuka endpoint existing ketika actor memiliki permission terkait.
 */
class PimpinanDashboardService extends BkWorkflowDashboardService
{
    private const DASHBOARD_LIMIT = 5;

    protected function widgetsPimpinan(int $userId): array
    {
        $tahun = $this->tahunAktif();
        $idTahun = (int) ($tahun['id'] ?? 0);
        $hasActiveYear = $idTahun > 0;

        $canEws = $this->can($userId, 'ews_radar.view');
        $canPelanggaran = $this->can($userId, 'bk_kasus.view');
        $canPrestasi = $this->can($userId, 'prestasi.view');
        $canKartu = $this->can($userId, 'kartu_pelajar.view');

        return [
            'tahun_aktif' => $tahun,
            'period_available' => $hasActiveYear,
            'access' => [
                'ews' => $canEws,
                'pelanggaran' => $canPelanggaran,
                'prestasi' => $canPrestasi,
                'kartu' => $canKartu,
            ],
            'master' => $this->masterSummary($idTahun),
            'presensi_hari_ini' => $hasActiveYear
                ? $this->presensiTodaySummary($idTahun)
                : [],
            'jurnal_hari_ini' => $hasActiveYear
                ? $this->journalTodaySummary($idTahun)
                : [],
            'ews_count' => $canEws && $hasActiveYear
                ? $this->ewsCount($idTahun)
                : null,
            'ews_top' => $canEws && $hasActiveYear
                ? $this->ewsTop($idTahun, null, self::DASHBOARD_LIMIT)
                : [],
            'kasus_bulan_ini' => $canPelanggaran && $hasActiveYear
                ? $this->countKasusBulanIni($idTahun)
                : null,
            'prestasi_terbaru' => $canPrestasi && $hasActiveYear
                ? $this->latestPrestasiForYear($idTahun, self::DASHBOARD_LIMIT)
                : [],
            'kartu' => $canKartu ? $this->cardSummary() : null,
            'tren_presensi' => $hasActiveYear
                ? $this->trendAttendance($idTahun)
                : [],
            'quick_actions' => $this->pimpinanQuickActions($userId),
        ];
    }

    private function countKasusBulanIni(int $idTahun): int
    {
        [$start, $end] = $this->monthPeriod();

        return $this->db
            ->table('catatan_kasus')
            ->where('id_tahun', $idTahun)
            ->where('tanggal >=', $start)
            ->where('tanggal <=', $end)
            ->countAllResults();
    }

    private function latestPrestasiForYear(int $idTahun, int $limit): array
    {
        return $this->db
            ->table('catatan_prestasi cp')
            ->select('cp.*, s.nama')
            ->join('siswa s', 's.id = cp.id_siswa')
            ->where('cp.id_tahun', $idTahun)
            ->orderBy('cp.tanggal', 'DESC')
            ->orderBy('cp.id', 'DESC')
            ->limit(max(1, min(self::DASHBOARD_LIMIT, $limit)))
            ->get()
            ->getResultArray();
    }

    private function pimpinanQuickActions(int $userId): array
    {
        $candidates = [
            [
                'permission' => 'presensi_siswa.view',
                'label' => 'Rekap',
                'description' => 'Buka rekap Presensi siswa',
                'icon' => 'bx-list-check',
                'url' => 'presensi/siswa/rekap',
            ],
            [
                'permission' => 'laporan_jurnal.view',
                'label' => 'Jurnal',
                'description' => 'Buka laporan Jurnal mengajar',
                'icon' => 'bx-book-content',
                'url' => 'laporan/jurnal',
            ],
            [
                'permission' => 'ews_radar.view',
                'label' => 'EWS',
                'description' => 'Pantau exception Alpha 14 hari',
                'icon' => 'bx-radar',
                'url' => 'presensi/siswa/ews',
            ],
            [
                'permission' => 'laporan_matrix.view',
                'label' => 'Laporan',
                'description' => 'Buka Matrix Presensi',
                'icon' => 'bx-bar-chart-alt-2',
                'url' => 'laporan/presensi/matrix',
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
}
