<?php

namespace App\Models;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * LaporanJurnalModel
 *
 * Histori Jurnal tidak memfilter status_jadwal = Aktif.
 * Child siswa Jurnal diagregasi terpisah agar satu row laporan tetap satu Jurnal.
 */
class LaporanJurnalModel
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function getTahun(int $idTahun): ?array
    {
        $row = $this->db
            ->table('tahun_ajaran')
            ->select('id, nama_tahun, semester, status_aktif')
            ->where('id', $idTahun)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    public function getTahunOptions(): array
    {
        return $this->db
            ->table('tahun_ajaran')
            ->select('id, nama_tahun, semester, status_aktif')
            ->where('deleted_at', null)
            ->orderBy('nama_tahun', 'DESC')
            ->orderBy('semester', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getTahunAktif(): ?array
    {
        $row = $this->db
            ->table('tahun_ajaran')
            ->select('id, nama_tahun, semester, status_aktif')
            ->where('status_aktif', 1)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    public function getGuruOptions(int $idTahun): array
    {
        return $this->db
            ->table('presensi_mengajar pm')
            ->select('pm.id_guru, MAX(pm.nama_guru_snapshot) AS nama_guru, MAX(g.nip) AS nip', false)
            ->join('guru g', 'g.id = pm.id_guru', 'left')
            ->where('pm.id_tahun', $idTahun)
            ->where('pm.id_guru IS NOT NULL', null, false)
            ->groupBy('pm.id_guru')
            ->orderBy('nama_guru', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getKelasOptions(int $idTahun, ?int $idGuru = null): array
    {
        $builder = $this->db
            ->table('presensi_mengajar pm')
            ->select('pm.id_kelas, MAX(k.nama_kelas) AS nama_kelas', false)
            ->join('kelas k', 'k.id = pm.id_kelas', 'left')
            ->where('pm.id_tahun', $idTahun);

        if ($idGuru !== null) {
            $builder->where('pm.id_guru', $idGuru);
        }

        return $builder
            ->groupBy('pm.id_kelas')
            ->orderBy('nama_kelas', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getPaged(array $filter, int $limit, int $offset): array
    {
        return $this->baseBuilder($filter)
            ->orderBy('pm.tanggal', 'DESC')
            ->orderBy('jg.jam_mulai', 'ASC')
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();
    }

    public function countFiltered(array $filter): int
    {
        return $this->baseBuilder($filter)
            ->countAllResults();
    }

    public function getForExport(array $filter): array
    {
        return $this->baseBuilder($filter)
            ->orderBy('pm.tanggal', 'ASC')
            ->orderBy('jg.jam_mulai', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getById(int $idJurnal): ?array
    {
        if ($idJurnal <= 0) {
            return null;
        }

        $row = $this->baseBuilder([])
            ->where('pm.id', $idJurnal)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    /**
     * Aggregate child untuk sekumpulan parent tanpa N+1 query.
     *
     * @return array<int,array{Sakit:int,Izin:int,Alpha:int,total:int}>
     */
    public function getStudentExceptionSummary(array $idJurnal): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $idJurnal))));

        if ($ids === []) {
            return [];
        }

        $rows = $this->db
            ->table('presensi_mengajar_siswa')
            ->select('id_presensi_mengajar, status, COUNT(*) AS jumlah')
            ->whereIn('id_presensi_mengajar', $ids)
            ->groupBy('id_presensi_mengajar, status')
            ->get()
            ->getResultArray();

        $map = [];

        foreach ($rows as $row) {
            $id = (int) ($row['id_presensi_mengajar'] ?? 0);
            $status = (string) ($row['status'] ?? '');
            $jumlah = (int) ($row['jumlah'] ?? 0);

            if (! isset($map[$id])) {
                $map[$id] = [
                    'Sakit' => 0,
                    'Izin' => 0,
                    'Alpha' => 0,
                    'total' => 0,
                ];
            }

            if (array_key_exists($status, $map[$id])) {
                $map[$id][$status] = $jumlah;
                $map[$id]['total'] += $jumlah;
            }
        }

        return $map;
    }

    public function getStudentExceptions(int $idJurnal): array
    {
        if ($idJurnal <= 0) {
            return [];
        }

        return $this->db
            ->table('presensi_mengajar_siswa')
            ->select('id, id_siswa, nama_siswa_snapshot, nisn_snapshot, status')
            ->where('id_presensi_mengajar', $idJurnal)
            ->orderBy('nama_siswa_snapshot', 'ASC')
            ->get()
            ->getResultArray();
    }

    private function baseBuilder(array $filter)
    {
        $builder = $this->db
            ->table('presensi_mengajar pm')
            ->select([
                'pm.id',
                'pm.id_guru',
                'pm.nama_guru_snapshot',
                'pm.id_jadwal',
                'pm.id_kelas',
                'pm.id_tahun',
                'pm.tanggal',
                'pm.status',
                'pm.materi',
                'pm.catatan',
                'jg.hari',
                'jg.jam_mulai',
                'jg.jam_selesai',
                'jg.sesi',
                'jg.status_jadwal',
                'g.nip',
                'k.nama_kelas',
                'mp.kode_mapel',
                'mp.nama_mapel',
                'ta.nama_tahun',
                'ta.semester',
            ])
            ->join('jadwal_guru jg', 'jg.id = pm.id_jadwal')
            ->join('guru g', 'g.id = pm.id_guru', 'left')
            ->join('kelas k', 'k.id = pm.id_kelas', 'left')
            ->join('mata_pelajaran mp', 'mp.id = jg.id_mapel', 'left')
            ->join('tahun_ajaran ta', 'ta.id = pm.id_tahun');

        if (!empty($filter['id_tahun'])) {
            $builder->where('pm.id_tahun', (int) $filter['id_tahun']);
        }

        if (!empty($filter['id_guru'])) {
            $builder->where('pm.id_guru', (int) $filter['id_guru']);
        }

        if (!empty($filter['id_kelas'])) {
            $builder->where('pm.id_kelas', (int) $filter['id_kelas']);
        }

        if (!empty($filter['hari'])) {
            $builder->where('jg.hari', (string) $filter['hari']);
        }

        if (!empty($filter['status'])) {
            $builder->where('pm.status', (string) $filter['status']);
        }

        if (!empty($filter['tanggal_mulai'])) {
            $builder->where('pm.tanggal >=', (string) $filter['tanggal_mulai']);
        }

        if (!empty($filter['tanggal_selesai'])) {
            $builder->where('pm.tanggal <=', (string) $filter['tanggal_selesai']);
        }

        return $builder;
    }
}
