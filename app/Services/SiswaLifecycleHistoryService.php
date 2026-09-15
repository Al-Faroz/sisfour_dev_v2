<?php

namespace App\Services;

use Config\Database;

/**
 * Read model histori lifecycle siswa untuk halaman Manajemen Siswa.
 *
 * Memisahkan daftar siswa yang masih dapat diproses dari data terminal yang
 * tetap harus mudah dilihat oleh Admin/Operator setelah Mutasi atau Kelulusan.
 */
class SiswaLifecycleHistoryService
{
    protected $db;
    protected AuthService $authService;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->authService = new AuthService();
    }

    public function getMutasiHistory(int $actorUserId): array
    {
        return $this->getTerminalRows(
            $actorUserId,
            ['Pindah', 'Keluar']
        );
    }

    public function getAlumni(int $actorUserId): array
    {
        return $this->getTerminalRows(
            $actorUserId,
            ['Lulus']
        );
    }

    /**
     * @param string[] $statuses
     */
    private function getTerminalRows(
        int $actorUserId,
        array $statuses
    ): array {
        if (
            $actorUserId <= 0
            || $this->authService->resolveScope(
                'master_siswa.manage',
                $actorUserId
            ) !== 'SEMUA'
        ) {
            return [];
        }

        return $this->db
            ->table('riwayat_siswa rs')
            ->select(
                'rs.id, rs.id_siswa, rs.status, rs.tanggal_mulai, ' .
                'rs.tanggal_selesai, rs.keterangan, ' .
                's.nisn, s.nik, s.nama, s.jenis_kelamin, ' .
                's.status_aktif, s.tanggal_mutasi, s.keterangan_mutasi, ' .
                'k.nama_kelas, ta.nama_tahun, ta.semester'
            )
            ->join('siswa s', 's.id = rs.id_siswa')
            ->join('kelas k', 'k.id = rs.id_kelas', 'left')
            ->join('tahun_ajaran ta', 'ta.id = rs.id_tahun', 'left')
            ->where('s.deleted_at', null)
            ->whereIn('rs.status', $statuses)
            ->orderBy('rs.tanggal_selesai', 'DESC')
            ->orderBy('rs.id', 'DESC')
            ->get()
            ->getResultArray();
    }
}
