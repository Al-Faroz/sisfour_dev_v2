<?php

namespace App\Services;

use Config\Database;
use Throwable;

/**
 * Histori lifecycle siswa untuk halaman Manajemen Siswa.
 *
 * Selain read model Pindah/Keluar/Lulus, Service ini menangani restore terminal
 * secara transactional dengan guard ketat:
 * - hanya actor master_siswa.manage = SEMUA;
 * - hanya histori terminal terbaru siswa yang boleh direstore;
 * - periode histori wajib masih menjadi tahun/semester aktif saat ini;
 * - kelas asal wajib masih tersedia pada periode aktif tersebut;
 * - membership dan histori Aktif tidak boleh sudah terbuka;
 * - histori terminal tetap dipertahankan sebagai audit trail;
 * - restore membuka histori Aktif baru, mengembalikan membership/status siswa,
 *   serta mengaktifkan kembali kartu terakhir bila belum ada kartu aktif.
 */
class SiswaLifecycleHistoryService
{
    protected $db;
    protected AuthService $authService;
    protected ActivityLogService $activityLog;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->authService = new AuthService();
        $this->activityLog = new ActivityLogService();
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

    public function restoreMutasi(
        int $actorUserId,
        int $idRiwayat
    ): array {
        return $this->restoreTerminal(
            $actorUserId,
            $idRiwayat,
            ['Pindah', 'Keluar']
        );
    }

    public function restoreKelulusan(
        int $actorUserId,
        int $idRiwayat
    ): array {
        return $this->restoreTerminal(
            $actorUserId,
            $idRiwayat,
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
        if (! $this->canManage($actorUserId)) {
            return [];
        }

        $rows = $this->db
            ->table('riwayat_siswa rs')
            ->select(
                'rs.id, rs.id_siswa, rs.id_tahun, rs.id_kelas, ' .
                'rs.status, rs.tanggal_mulai, rs.tanggal_selesai, ' .
                'rs.keterangan, s.nisn, s.nik, s.nama, ' .
                's.jenis_kelamin, s.status_aktif, s.tanggal_mutasi, ' .
                's.keterangan_mutasi, k.nama_kelas, ' .
                'ta.nama_tahun, ta.semester, ta.status_aktif AS tahun_aktif'
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

        $seenStudent = [];

        foreach ($rows as &$row) {
            $idSiswa = (int) ($row['id_siswa'] ?? 0);
            $isLatestTerminal = ! isset($seenStudent[$idSiswa]);
            $seenStudent[$idSiswa] = true;

            $row['can_restore'] = $isLatestTerminal
                && (int) ($row['tahun_aktif'] ?? 0) === 1
                && (string) ($row['status_aktif'] ?? '')
                    === (string) ($row['status'] ?? '');
        }
        unset($row);

        return $rows;
    }

    /**
     * @param string[] $allowedStatuses
     */
    private function restoreTerminal(
        int $actorUserId,
        int $idRiwayat,
        array $allowedStatuses
    ): array {
        if (! $this->canManage($actorUserId)) {
            return $this->fail(
                'FORBIDDEN',
                'Anda tidak memiliki hak untuk restore lifecycle siswa.'
            );
        }

        if ($idRiwayat <= 0) {
            return $this->fail(
                'VALIDATION',
                'Histori siswa tidak valid.'
            );
        }

        $terminal = $this->db
            ->table('riwayat_siswa rs')
            ->select(
                'rs.id, rs.id_siswa, rs.id_tahun, rs.id_kelas, ' .
                'rs.status, rs.tanggal_mulai, rs.tanggal_selesai, ' .
                'rs.keterangan, s.nama, s.nisn, ' .
                's.status_aktif AS status_siswa, ' .
                'ta.nama_tahun, ta.semester, ta.status_aktif AS tahun_aktif, ' .
                'ta.deleted_at AS tahun_deleted_at, ' .
                'k.nama_kelas, k.id_tahun AS kelas_id_tahun, ' .
                'k.deleted_at AS kelas_deleted_at'
            )
            ->join('siswa s', 's.id = rs.id_siswa')
            ->join('tahun_ajaran ta', 'ta.id = rs.id_tahun')
            ->join('kelas k', 'k.id = rs.id_kelas')
            ->where('rs.id', $idRiwayat)
            ->where('s.deleted_at', null)
            ->get()
            ->getRowArray();

        if ($terminal === null) {
            return $this->fail(
                'NOT_FOUND',
                'Histori terminal siswa tidak ditemukan.'
            );
        }

        $status = (string) $terminal['status'];

        if (! in_array($status, $allowedStatuses, true)) {
            return $this->fail(
                'INVALID_STATUS',
                'Histori tersebut tidak dapat direstore dari workflow ini.'
            );
        }

        if ((string) $terminal['status_siswa'] !== $status) {
            return $this->fail(
                'STATUS_CHANGED',
                'Status siswa sudah berubah sehingga histori ini tidak lagi dapat direstore.'
            );
        }

        $latestHistory = $this->db
            ->table('riwayat_siswa')
            ->select('id, status')
            ->where('id_siswa', (int) $terminal['id_siswa'])
            ->orderBy('id', 'DESC')
            ->get()
            ->getRowArray();

        if (
            $latestHistory === null
            || (int) $latestHistory['id'] !== $idRiwayat
        ) {
            return $this->fail(
                'HISTORY_NOT_CURRENT',
                'Hanya histori lifecycle terbaru siswa yang dapat direstore.'
            );
        }

        $activeYear = $this->db
            ->table('tahun_ajaran')
            ->select('id, nama_tahun, semester')
            ->where('status_aktif', 1)
            ->where('deleted_at', null)
            ->orderBy('id', 'DESC')
            ->get()
            ->getRowArray();

        if (
            $activeYear === null
            || (int) $activeYear['id'] !== (int) $terminal['id_tahun']
            || (int) $terminal['tahun_aktif'] !== 1
            || ! empty($terminal['tahun_deleted_at'])
        ) {
            return $this->fail(
                'PERIOD_INACTIVE',
                'Restore ditolak karena tahun ajaran/semester asal siswa sudah tidak aktif.'
            );
        }

        if (
            ! empty($terminal['kelas_deleted_at'])
            || (int) $terminal['kelas_id_tahun'] !== (int) $terminal['id_tahun']
        ) {
            return $this->fail(
                'CLASS_UNAVAILABLE',
                'Restore ditolak karena kelas terakhir siswa sudah tidak tersedia pada periode aktif.'
            );
        }

        $existingMembership = $this->db
            ->table('anggota_kelas')
            ->select('id')
            ->where('id_siswa', (int) $terminal['id_siswa'])
            ->where('id_tahun', (int) $terminal['id_tahun'])
            ->get()
            ->getRowArray();

        if ($existingMembership !== null) {
            return $this->fail(
                'MEMBERSHIP_EXISTS',
                'Restore ditolak karena siswa sudah memiliki membership pada periode aktif.'
            );
        }

        $openHistory = $this->db
            ->table('riwayat_siswa')
            ->select('id')
            ->where('id_siswa', (int) $terminal['id_siswa'])
            ->where('id_tahun', (int) $terminal['id_tahun'])
            ->where('status', 'Aktif')
            ->where('tanggal_selesai', null)
            ->get()
            ->getRowArray();

        if ($openHistory !== null) {
            return $this->fail(
                'ACTIVE_HISTORY_EXISTS',
                'Restore ditolak karena siswa sudah memiliki histori Aktif terbuka.'
            );
        }

        $now = date('Y-m-d H:i:s');
        $today = date('Y-m-d');
        $cardReactivated = false;

        $this->db->transBegin();

        try {
            if (! $this->db->table('anggota_kelas')->insert([
                'id_siswa' => (int) $terminal['id_siswa'],
                'id_kelas' => (int) $terminal['id_kelas'],
                'id_tahun' => (int) $terminal['id_tahun'],
            ])) {
                throw new \RuntimeException(
                    'Membership kelas siswa gagal dipulihkan.'
                );
            }

            if (! $this->db->table('riwayat_siswa')->insert([
                'id_siswa' => (int) $terminal['id_siswa'],
                'id_tahun' => (int) $terminal['id_tahun'],
                'id_kelas' => (int) $terminal['id_kelas'],
                'status' => 'Aktif',
                'tanggal_mulai' => $today,
                'tanggal_selesai' => null,
                'keterangan' => sprintf(
                    'Restore dari status %s. Siswa kembali aktif di %s.',
                    $status,
                    (string) $terminal['nama_kelas']
                ),
                'created_at' => $now,
            ])) {
                throw new \RuntimeException(
                    'Histori Aktif hasil restore gagal dibuat.'
                );
            }

            $studentUpdated = $this->db
                ->table('siswa')
                ->where('id', (int) $terminal['id_siswa'])
                ->update([
                    'status_aktif' => 'Aktif',
                    'tanggal_mutasi' => null,
                    'keterangan_mutasi' => null,
                    'updated_at' => $now,
                ]);

            if (! $studentUpdated) {
                throw new \RuntimeException(
                    'Status siswa gagal dipulihkan menjadi Aktif.'
                );
            }

            $activeCard = $this->db
                ->table('kartu_pelajar')
                ->select('id')
                ->where('id_siswa', (int) $terminal['id_siswa'])
                ->where('status_aktif', 'Aktif')
                ->orderBy('id', 'DESC')
                ->get()
                ->getRowArray();

            if ($activeCard === null) {
                $inactiveCard = $this->db
                    ->table('kartu_pelajar')
                    ->select('id')
                    ->where('id_siswa', (int) $terminal['id_siswa'])
                    ->where('status_aktif', 'Nonaktif')
                    ->orderBy('id', 'DESC')
                    ->get()
                    ->getRowArray();

                if ($inactiveCard !== null) {
                    $cardReactivated = (bool) $this->db
                        ->table('kartu_pelajar')
                        ->where('id', (int) $inactiveCard['id'])
                        ->update(['status_aktif' => 'Aktif']);

                    if (! $cardReactivated) {
                        throw new \RuntimeException(
                            'Kartu Pelajar terakhir gagal diaktifkan kembali.'
                        );
                    }
                }
            }

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException(
                    'Transaksi restore lifecycle siswa gagal.'
                );
            }

            $this->activityLog->write(
                $actorUserId,
                'RESTORE_LIFECYCLE',
                'Manajemen Siswa',
                sprintf(
                    'Restore siswa ID %d - %s dari status %s menjadi Aktif pada %s - %s, kembali ke %s.',
                    (int) $terminal['id_siswa'],
                    (string) $terminal['nama'],
                    $status,
                    (string) $terminal['nama_tahun'],
                    (string) $terminal['semester'],
                    (string) $terminal['nama_kelas']
                )
            );

            $this->db->transCommit();

            return [
                'success' => true,
                'message' => sprintf(
                    '%s berhasil direstore menjadi siswa Aktif di %s.',
                    (string) $terminal['nama'],
                    (string) $terminal['nama_kelas']
                ),
                'id_siswa' => (int) $terminal['id_siswa'],
                'id_kelas' => (int) $terminal['id_kelas'],
                'id_tahun' => (int) $terminal['id_tahun'],
                'status_sebelumnya' => $status,
                'card_reactivated' => $cardReactivated,
            ];
        } catch (Throwable $e) {
            $this->db->transRollback();

            return $this->fail(
                'RESTORE_FAILED',
                $e->getMessage()
            );
        }
    }

    private function canManage(int $actorUserId): bool
    {
        return $actorUserId > 0
            && $this->authService->resolveScope(
                'master_siswa.manage',
                $actorUserId
            ) === 'SEMUA';
    }

    private function fail(string $code, string $message): array
    {
        return [
            'success' => false,
            'code' => $code,
            'message' => $message,
        ];
    }
}
