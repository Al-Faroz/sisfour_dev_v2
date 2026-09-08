<?php

namespace App\Models;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * LaporanPresensiModel
 *
 * Query bounded untuk Matrix dan Export Presensi.
 * Transformasi pivot dilakukan di Service setelah dataset dibatasi
 * satu kelas + satu periode.
 */
class LaporanPresensiModel
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

    public function getKelas(int $idKelas, int $idTahun): ?array
    {
        $row = $this->db
            ->table('kelas')
            ->select('id, nama_kelas, tingkat, rombel, id_tahun')
            ->where('id', $idKelas)
            ->where('id_tahun', $idTahun)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    public function getKelasOptions(int $idTahun, ?array $allowedIds = null): array
    {
        $builder = $this->db
            ->table('kelas')
            ->select('id, nama_kelas, tingkat, rombel, id_tahun')
            ->where('id_tahun', $idTahun);

        if (is_array($allowedIds)) {
            if ($allowedIds === []) {
                return [];
            }

            $builder->whereIn('id', array_map('intval', $allowedIds));
        }

        return $builder
            ->orderBy('tingkat', 'ASC')
            ->orderBy('rombel', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Membership yang overlap dengan periode.
     * Tidak memfilter status siswa aktif saat ini.
     */
    public function getMembershipPeriod(
        int $idTahun,
        int $idKelas,
        string $mulai,
        string $selesai
    ): array {
        return $this->db
            ->table('riwayat_siswa rs')
            ->select([
                'rs.id_siswa',
                'rs.tanggal_mulai',
                'rs.tanggal_selesai',
                'rs.status AS status_riwayat',
                's.nisn',
                's.nama',
            ])
            ->join('siswa s', 's.id = rs.id_siswa')
            ->where('rs.id_tahun', $idTahun)
            ->where('rs.id_kelas', $idKelas)
            ->where('rs.tanggal_mulai <=', $selesai)
            ->groupStart()
                ->where('rs.tanggal_selesai', null)
                ->orWhere('rs.tanggal_selesai >=', $mulai)
            ->groupEnd()
            ->orderBy('s.nama', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getPresensiPeriod(
        int $idTahun,
        int $idKelas,
        string $mulai,
        string $selesai,
        ?string $sesi = null
    ): array {
        $builder = $this->db
            ->table('presensi p')
            ->select([
                'p.id',
                'p.id_siswa',
                'p.nama_siswa_snapshot',
                'p.tanggal',
                'p.sesi',
                'p.status',
            ])
            ->where('p.id_tahun', $idTahun)
            ->where('p.id_kelas', $idKelas)
            ->where('p.tanggal >=', $mulai)
            ->where('p.tanggal <=', $selesai);

        if ($sesi !== null) {
            $builder->where('p.sesi', $sesi);
        }

        return $builder
            ->orderBy('p.tanggal', 'ASC')
            ->orderBy('p.id_siswa', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Rekap semester DB-side, hanya Sesi Awal.
     */
    public function getSemesterAggregate(
        int $idTahun,
        int $idKelas,
        string $mulai,
        string $selesai
    ): array {
        return $this->db
            ->table('presensi p')
            ->select("
                p.id_siswa,
                MAX(p.nama_siswa_snapshot) AS nama_siswa_snapshot,
                MONTH(p.tanggal) AS bulan,
                SUM(p.status = 'Hadir') AS hadir,
                SUM(p.status = 'Sakit') AS sakit,
                SUM(p.status = 'Izin') AS izin,
                SUM(p.status = 'Alpha') AS alpha
            ", false)
            ->where('p.id_tahun', $idTahun)
            ->where('p.id_kelas', $idKelas)
            ->where('p.sesi', 'Sesi Awal')
            ->where(
                "EXISTS (
                    SELECT 1
                    FROM riwayat_siswa rs
                    WHERE rs.id_siswa = p.id_siswa
                      AND rs.id_tahun = p.id_tahun
                      AND rs.id_kelas = p.id_kelas
                      AND rs.tanggal_mulai <= p.tanggal
                      AND (
                          rs.tanggal_selesai IS NULL
                          OR rs.tanggal_selesai >= p.tanggal
                      )
                )",
                null,
                false
            )
            ->where('p.tanggal >=', $mulai)
            ->where('p.tanggal <=', $selesai)
            ->groupBy('p.id_siswa, MONTH(p.tanggal)')
            ->orderBy('p.id_siswa', 'ASC')
            ->orderBy('bulan', 'ASC')
            ->get()
            ->getResultArray();
    }
}
