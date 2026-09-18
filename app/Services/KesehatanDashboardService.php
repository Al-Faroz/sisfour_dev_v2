<?php

namespace App\Services;

class KesehatanDashboardService extends SiswaDashboardService
{
    private const LIMIT = 5;

    protected function widgetsKesehatan(int $userId): array
    {
        $user = $this->getUser($userId);
        $hasIdentity = (int) ($user['id_pegawai'] ?? 0) > 0;
        $tahun = $this->tahunAktif();
        $idTahun = (int) ($tahun['id'] ?? 0);
        $hasPeriod = $idTahun > 0;

        if (! $hasIdentity) {
            return [
                'tahun_aktif' => $tahun,
                'period_available' => $hasPeriod,
                'identity_available' => false,
                'access' => [
                    'ckg' => false,
                    'harian' => false,
                    'import' => false,
                    'master' => false,
                ],
                'ckg_bulan_ini' => null,
                'kunjungan_hari_ini' => null,
                'kunjungan_bulan_ini' => null,
                'rujuk_klinik_bulan_ini' => null,
                'ckg_terbaru' => [],
                'kunjungan_terbaru' => [],
                'quick_actions' => [],
            ];
        }

        $canCkg = $this->can($userId, 'uks_ckg.view');
        $canHarian = $this->can($userId, 'uks_harian.view');
        $canImport = $this->can($userId, 'uks_ckg.import');
        $canMaster = $this->can($userId, 'uks_master.manage');

        [$monthStart, $monthEnd] = $this->monthPeriod();
        $today = $this->today();

        return [
            'tahun_aktif' => $tahun,
            'period_available' => $hasPeriod,
            'identity_available' => true,
            'access' => [
                'ckg' => $canCkg,
                'harian' => $canHarian,
                'import' => $canImport,
                'master' => $canMaster,
            ],
            'ckg_bulan_ini' => $canCkg && $hasPeriod
                ? $this->countCkg($idTahun, $monthStart, $monthEnd)
                : null,
            'kunjungan_hari_ini' => $canHarian && $hasPeriod
                ? $this->countVisits($idTahun, $today, $today)
                : null,
            'kunjungan_bulan_ini' => $canHarian && $hasPeriod
                ? $this->countVisits($idTahun, $monthStart, $monthEnd)
                : null,
            'rujuk_klinik_bulan_ini' => $canHarian && $hasPeriod
                ? $this->countClinicReferrals($idTahun, $monthStart, $monthEnd)
                : null,
            'ckg_terbaru' => $canCkg && $hasPeriod
                ? $this->latestCkg($idTahun)
                : [],
            'kunjungan_terbaru' => $canHarian && $hasPeriod
                ? $this->latestVisits($idTahun)
                : [],
            'quick_actions' => $this->quickActions($userId),
        ];
    }

    private function countCkg(int $idTahun, string $start, string $end): int
    {
        return $this->db->table('uks_ckg')
            ->where('id_tahun', $idTahun)
            ->where('deleted_at', null)
            ->where('tanggal >=', $start)
            ->where('tanggal <=', $end)
            ->countAllResults();
    }

    private function countVisits(int $idTahun, string $start, string $end): int
    {
        return $this->db->table('uks_kunjungan')
            ->where('id_tahun', $idTahun)
            ->where('deleted_at', null)
            ->where('tanggal >=', $start)
            ->where('tanggal <=', $end)
            ->countAllResults();
    }

    private function countClinicReferrals(int $idTahun, string $start, string $end): int
    {
        return $this->db->table('uks_kunjungan u')
            ->join('uks_ref_hasil h', 'h.id = u.id_hasil')
            ->where('u.id_tahun', $idTahun)
            ->where('u.deleted_at', null)
            ->where('u.tanggal >=', $start)
            ->where('u.tanggal <=', $end)
            ->where('h.nama', 'Dirujuk ke klinik')
            ->countAllResults();
    }

    private function latestCkg(int $idTahun): array
    {
        return $this->db->table('uks_ckg c')
            ->select('c.id, c.tanggal, s.nama, s.nisn, k.nama_kelas')
            ->join('siswa s', 's.id = c.id_siswa')
            ->join('kelas k', 'k.id = c.id_kelas')
            ->where('c.id_tahun', $idTahun)
            ->where('c.deleted_at', null)
            ->orderBy('c.tanggal', 'DESC')
            ->orderBy('c.id', 'DESC')
            ->limit(self::LIMIT)
            ->get()
            ->getResultArray();
    }

    private function latestVisits(int $idTahun): array
    {
        return $this->db->table('uks_kunjungan u')
            ->select('u.id, u.tanggal, u.jam_masuk, s.nama, s.nisn, k.nama_kelas, r.nama AS keluhan, h.nama AS hasil')
            ->join('siswa s', 's.id = u.id_siswa')
            ->join('kelas k', 'k.id = u.id_kelas')
            ->join('uks_ref_keluhan r', 'r.id = u.id_keluhan')
            ->join('uks_ref_hasil h', 'h.id = u.id_hasil')
            ->where('u.id_tahun', $idTahun)
            ->where('u.deleted_at', null)
            ->orderBy('u.tanggal', 'DESC')
            ->orderBy('u.jam_masuk', 'DESC')
            ->orderBy('u.id', 'DESC')
            ->limit(self::LIMIT)
            ->get()
            ->getResultArray();
    }

    private function quickActions(int $userId): array
    {
        $candidates = [
            [
                'permission' => 'uks_ckg.view',
                'label' => 'Data CKG',
                'description' => 'Buka data pemeriksaan CKG',
                'icon' => 'bx-pulse',
                'url' => 'uks/ckg',
            ],
            [
                'permission' => 'uks_harian.view',
                'label' => 'Data UKS',
                'description' => 'Buka Catatan Harian UKS',
                'icon' => 'bx-plus-medical',
                'url' => 'uks/harian',
            ],
            [
                'permission' => 'uks_ckg.import',
                'label' => 'Import CKG',
                'description' => 'Import pemeriksaan CKG via XLSX',
                'icon' => 'bx-import',
                'url' => 'uks/ckg#import',
            ],
            [
                'permission' => 'uks_master.manage',
                'label' => 'Master UKS',
                'description' => 'Kelola keluhan, tindakan, dan hasil',
                'icon' => 'bx-list-ul',
                'url' => 'uks/master',
            ],
        ];

        $result = [];
        foreach ($candidates as $candidate) {
            if (! $this->can($userId, $candidate['permission'])) {
                continue;
            }
            unset($candidate['permission']);
            $result[] = $candidate;
        }

        return $result;
    }
}
