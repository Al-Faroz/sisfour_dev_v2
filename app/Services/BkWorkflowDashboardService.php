<?php

namespace App\Services;

/**
 * G3.4 Dashboard/Workflow BK.
 *
 * Hanya experience BK yang dioverride. Role lain tetap memakai payload
 * RoleAwareDashboardService yang sudah lulus gate phase sebelumnya.
 *
 * Dashboard adalah current-state Tahun Ajaran aktif. Surface history/listing
 * tetap menangani Period Context selectable pada service domain masing-masing.
 */
class BkWorkflowDashboardService extends RoleAwareDashboardService
{
    private const TZ = 'Asia/Jakarta';
    private const DASHBOARD_LIMIT = 5;

    protected function widgetsBk(int $userId): array
    {
        $tahun = $this->tahunAktif();
        $idTahun = (int) ($tahun['id'] ?? 0);

        $canKonseling = $this->can($userId, 'bk_konseling.view');
        $canPelanggaran = $this->can($userId, 'bk_kasus.view');
        $canEws = $this->can($userId, 'ews_radar.view');
        $canPrestasi = $this->can($userId, 'prestasi.view');
        $hasActiveYear = $idTahun > 0;

        return [
            'tahun_aktif' => $tahun,
            'access' => [
                'konseling' => $canKonseling,
                'pelanggaran' => $canPelanggaran,
                'ews' => $canEws,
                'prestasi' => $canPrestasi,
            ],
            'konseling_proses' => $canKonseling && $hasActiveYear
                ? $this->countKonselingProses($idTahun)
                : null,
            'kasus_bulan_ini' => $canPelanggaran && $hasActiveYear
                ? $this->countKasusBulanIni($idTahun)
                : null,
            'pelanggaran_berat_bulan_ini' => $canPelanggaran && $hasActiveYear
                ? $this->countKasusBulanIni($idTahun, 'Berat')
                : null,
            'ews_count' => $canEws && $hasActiveYear
                ? $this->ewsCount($idTahun)
                : null,
            'prestasi_bulan_ini' => $canPrestasi && $hasActiveYear
                ? $this->countPrestasiBulanIni($idTahun)
                : null,
            'quick_actions' => $this->bkQuickActions($userId),
            'konseling_terdekat' => $canKonseling && $hasActiveYear
                ? $this->nearestCounselingSchedules($idTahun, self::DASHBOARD_LIMIT)
                : [],
            'kasus_terbaru' => $canPelanggaran && $hasActiveYear
                ? $this->latestCasesForYear($idTahun, self::DASHBOARD_LIMIT)
                : [],
            'ews_top' => $canEws && $hasActiveYear
                ? $this->ewsTop($idTahun, null, self::DASHBOARD_LIMIT)
                : [],
            'prestasi_terbaru' => $canPrestasi && $hasActiveYear
                ? $this->latestPrestasiForYear($idTahun, self::DASHBOARD_LIMIT)
                : [],
        ];
    }

    private function countKonselingProses(int $idTahun): int
    {
        return $this->db
            ->table('konseling_bk')
            ->where('id_tahun', $idTahun)
            ->where('status', 'Proses')
            ->countAllResults();
    }

    private function countKasusBulanIni(int $idTahun, ?string $kategori = null): int
    {
        [$start, $end] = $this->monthBounds();

        $builder = $this->db
            ->table('catatan_kasus ck')
            ->where('ck.id_tahun', $idTahun)
            ->where('ck.tanggal >=', $start)
            ->where('ck.tanggal <=', $end);

        if ($kategori !== null) {
            $builder
                ->join('ref_pelanggaran rp', 'rp.id = ck.id_pelanggaran')
                ->where('rp.kategori', $kategori);
        }

        return $builder->countAllResults();
    }

    private function countPrestasiBulanIni(int $idTahun): int
    {
        [$start, $end] = $this->monthBounds();

        return $this->db
            ->table('catatan_prestasi')
            ->where('id_tahun', $idTahun)
            ->where('tanggal >=', $start)
            ->where('tanggal <=', $end)
            ->countAllResults();
    }

    private function latestCasesForYear(int $idTahun, int $limit): array
    {
        return $this->db
            ->table('catatan_kasus ck')
            ->select('ck.id, ck.id_siswa, ck.tanggal, ck.keterangan, s.nisn, s.nama, rp.nama_pelanggaran, rp.kategori')
            ->join('siswa s', 's.id = ck.id_siswa')
            ->join('ref_pelanggaran rp', 'rp.id = ck.id_pelanggaran')
            ->where('ck.id_tahun', $idTahun)
            ->orderBy('ck.tanggal', 'DESC')
            ->orderBy('ck.id', 'DESC')
            ->limit($this->limit($limit))
            ->get()
            ->getResultArray();
    }

    private function latestPrestasiForYear(int $idTahun, int $limit): array
    {
        return $this->db
            ->table('catatan_prestasi cp')
            ->select('cp.*, s.nisn, s.nama')
            ->join('siswa s', 's.id = cp.id_siswa')
            ->where('cp.id_tahun', $idTahun)
            ->orderBy('cp.tanggal', 'DESC')
            ->orderBy('cp.id', 'DESC')
            ->limit($this->limit($limit))
            ->get()
            ->getResultArray();
    }

    /**
     * Ambil tanggal berikutnya dari entry tindak lanjut terbaru bila histori
     * sudah ada. Jika belum ada tindak lanjut, gunakan tanggal berikutnya pada
     * hasil pertemuan awal. Nilai kosong tidak diberi label SLA/overdue.
     */
    private function nearestCounselingSchedules(int $idTahun, int $limit): array
    {
        $parents = $this->db
            ->table('konseling_bk kb')
            ->select([
                'kb.id',
                'kb.id_siswa',
                'kb.tanggal',
                'kb.tanggal_berikutnya',
                'kb.bidang',
                'kb.topik',
                'kb.status',
                's.nisn',
                's.nama AS nama_siswa',
                'k.nama_kelas',
            ])
            ->join('siswa s', 's.id = kb.id_siswa')
            ->join('kelas k', 'k.id = kb.id_kelas')
            ->where('kb.id_tahun', $idTahun)
            ->where('kb.status', 'Proses')
            ->get()
            ->getResultArray();

        if ($parents === []) {
            return [];
        }

        $ids = array_values(array_unique(array_map(
            'intval',
            array_column($parents, 'id')
        )));

        $followUps = $this->db
            ->table('tindak_lanjut_konseling_bk')
            ->select('id, id_konseling, tanggal, tanggal_berikutnya, status')
            ->whereIn('id_konseling', $ids)
            ->orderBy('id_konseling', 'ASC')
            ->orderBy('tanggal', 'DESC')
            ->orderBy('id', 'DESC')
            ->get()
            ->getResultArray();

        $latestByParent = [];
        foreach ($followUps as $followUp) {
            $parentId = (int) ($followUp['id_konseling'] ?? 0);
            if ($parentId > 0 && ! isset($latestByParent[$parentId])) {
                $latestByParent[$parentId] = $followUp;
            }
        }

        $rows = [];
        foreach ($parents as $parent) {
            $id = (int) ($parent['id'] ?? 0);
            $latest = $latestByParent[$id] ?? null;

            // Jika histori sudah ada, latest follow-up adalah source of truth.
            $nextDate = $latest !== null
                ? trim((string) ($latest['tanggal_berikutnya'] ?? ''))
                : trim((string) ($parent['tanggal_berikutnya'] ?? ''));

            if ($nextDate === '') {
                continue;
            }

            $parent['tanggal_follow_up'] = $nextDate;
            $parent['sumber_jadwal'] = $latest !== null
                ? 'Tindak Lanjut'
                : 'Pertemuan Awal';
            $rows[] = $parent;
        }

        usort(
            $rows,
            static function (array $a, array $b): int {
                $dateCompare = strcmp(
                    (string) ($a['tanggal_follow_up'] ?? ''),
                    (string) ($b['tanggal_follow_up'] ?? '')
                );

                if ($dateCompare !== 0) {
                    return $dateCompare;
                }

                return (int) ($a['id'] ?? 0) <=> (int) ($b['id'] ?? 0);
            }
        );

        return array_slice($rows, 0, $this->limit($limit));
    }

    private function bkQuickActions(int $userId): array
    {
        $candidates = [
            [
                'permission' => 'bk_konseling.view',
                'label' => 'Konseling BK',
                'description' => 'Buka workflow konseling dan tindak lanjut',
                'icon' => 'bx-message-rounded-dots',
                'url' => 'bk/konseling',
            ],
            [
                'permission' => 'bk_kasus.view',
                'label' => 'Catatan Pelanggaran',
                'description' => 'Lihat catatan dan tindak lanjut pelanggaran',
                'icon' => 'bx-error-circle',
                'url' => 'bk/kasus',
            ],
            [
                'permission' => 'ews_radar.view',
                'label' => 'EWS',
                'description' => 'Pantau siswa yang perlu perhatian',
                'icon' => 'bx-radar',
                'url' => 'presensi/siswa/ews',
            ],
            [
                'permission' => 'prestasi.view',
                'label' => 'Prestasi',
                'description' => 'Lihat riwayat prestasi siswa',
                'icon' => 'bx-trophy',
                'url' => 'bk/prestasi',
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

    private function monthBounds(): array
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone(self::TZ));

        return [
            $now->modify('first day of this month')->format('Y-m-d'),
            $now->modify('last day of this month')->format('Y-m-d'),
        ];
    }

    private function limit(int $limit): int
    {
        return max(1, min(self::DASHBOARD_LIMIT, $limit));
    }
}
