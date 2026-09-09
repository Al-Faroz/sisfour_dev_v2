<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use Config\Database;
use Throwable;

class SiswaKelasService
{
    protected BaseConnection $db;
    protected AuthService $authService;
    protected ActivityLogService $activityLog;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->authService = new AuthService();
        $this->activityLog = new ActivityLogService();
    }

    public function setOrMoveClass(
        int $actorUserId,
        int $idSiswa,
        int $idKelasTujuan
    ): array {
        if (
            $this->authService->resolveScope(
                'master_siswa.manage',
                $actorUserId
            ) !== 'SEMUA'
        ) {
            return $this->fail(
                'FORBIDDEN',
                'Anda tidak memiliki hak untuk mengatur kelas siswa.'
            );
        }

        if ($idSiswa <= 0 || $idKelasTujuan <= 0) {
            return $this->fail(
                'VALIDATION',
                'Siswa dan kelas tujuan wajib dipilih.'
            );
        }

        $tahun = $this->db
            ->table('tahun_ajaran')
            ->select('id, nama_tahun, semester')
            ->where('status_aktif', 1)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        if (!$tahun) {
            return $this->fail(
                'NO_ACTIVE_YEAR',
                'Tahun ajaran aktif belum tersedia.'
            );
        }

        $idTahun = (int) $tahun['id'];

        $siswa = $this->db
            ->table('siswa')
            ->select('id, nisn, nama, status_aktif')
            ->where('id', $idSiswa)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        if (!$siswa) {
            return $this->fail(
                'NOT_FOUND',
                'Data Siswa tidak ditemukan.'
            );
        }

        if (($siswa['status_aktif'] ?? '') !== 'Aktif') {
            return $this->fail(
                'INVALID_STATUS',
                'Kelas hanya dapat diatur untuk siswa berstatus Aktif.'
            );
        }

        $kelasTujuan = $this->db
            ->table('kelas')
            ->select('id, nama_kelas, tingkat, rombel, id_tahun')
            ->where('id', $idKelasTujuan)
            ->where('id_tahun', $idTahun)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        if (!$kelasTujuan) {
            return $this->fail(
                'INVALID_CLASS',
                'Kelas tujuan tidak valid atau bukan kelas pada tahun ajaran aktif.'
            );
        }

        $membership = $this->db
            ->table('anggota_kelas ak')
            ->select('ak.id, ak.id_kelas, ak.id_tahun, k.nama_kelas')
            ->join('kelas k', 'k.id = ak.id_kelas')
            ->where('ak.id_siswa', $idSiswa)
            ->where('ak.id_tahun', $idTahun)
            ->get()
            ->getRowArray();

        if (
            $membership
            && (int) $membership['id_kelas'] === $idKelasTujuan
        ) {
            return [
                'success' => true,
                'message' => 'Siswa sudah berada di kelas tersebut.',
                'id_kelas' => $idKelasTujuan,
                'nama_kelas' => $kelasTujuan['nama_kelas'],
                'action' => 'NO_CHANGE',
            ];
        }

        $today = date('Y-m-d');
        $this->db->transBegin();

        try {
            if (!$membership) {
                if (!$this->db->table('anggota_kelas')->insert([
                    'id_siswa' => $idSiswa,
                    'id_kelas' => $idKelasTujuan,
                    'id_tahun' => $idTahun,
                ])) {
                    throw new \RuntimeException(
                        'Keanggotaan kelas gagal disimpan.'
                    );
                }

                if (!$this->db->table('riwayat_siswa')->insert([
                    'id_siswa' => $idSiswa,
                    'id_tahun' => $idTahun,
                    'id_kelas' => $idKelasTujuan,
                    'status' => 'Aktif',
                    'tanggal_mulai' => $today,
                    'tanggal_selesai' => null,
                    'keterangan' => 'Penempatan awal melalui Manajemen Siswa.',
                    'created_at' => date('Y-m-d H:i:s'),
                ])) {
                    throw new \RuntimeException(
                        'Riwayat kelas gagal disimpan.'
                    );
                }

                $action = 'SET_CLASS';
                $message = sprintf(
                    'Siswa berhasil ditempatkan ke kelas %s.',
                    $kelasTujuan['nama_kelas']
                );
                $logText = sprintf(
                    'Menempatkan siswa ID %d - %s ke kelas %s.',
                    $idSiswa,
                    $siswa['nama'],
                    $kelasTujuan['nama_kelas']
                );
            } else {
                $kelasAsal = (string) $membership['nama_kelas'];

                $activeHistory = $this->db
                    ->table('riwayat_siswa')
                    ->where('id_siswa', $idSiswa)
                    ->where('id_tahun', $idTahun)
                    ->where('status', 'Aktif')
                    ->where('tanggal_selesai', null)
                    ->orderBy('id', 'DESC')
                    ->get()
                    ->getRowArray();

                if (!$this->db
                    ->table('anggota_kelas')
                    ->where('id', (int) $membership['id'])
                    ->update(['id_kelas' => $idKelasTujuan])
                ) {
                    throw new \RuntimeException(
                        'Keanggotaan kelas gagal dipindahkan.'
                    );
                }

                if (
                    $activeHistory
                    && (string) $activeHistory['tanggal_mulai'] === $today
                ) {
                    if (!$this->db
                        ->table('riwayat_siswa')
                        ->where('id', (int) $activeHistory['id'])
                        ->update([
                            'id_kelas' => $idKelasTujuan,
                            'keterangan' => sprintf(
                                'Koreksi/pindah kelas pada hari penempatan: %s → %s.',
                                $kelasAsal,
                                $kelasTujuan['nama_kelas']
                            ),
                        ])
                    ) {
                        throw new \RuntimeException(
                            'Riwayat kelas gagal dikoreksi.'
                        );
                    }
                } else {
                    if ($activeHistory) {
                        $yesterday = date(
                            'Y-m-d',
                            strtotime($today . ' -1 day')
                        );

                        if (!$this->db
                            ->table('riwayat_siswa')
                            ->where('id', (int) $activeHistory['id'])
                            ->update([
                                'tanggal_selesai' => $yesterday,
                                'keterangan' => trim(
                                    (string) ($activeHistory['keterangan'] ?? '')
                                    . sprintf(
                                        ' | Pindah kelas ke %s efektif %s.',
                                        $kelasTujuan['nama_kelas'],
                                        $today
                                    )
                                ),
                            ])
                        ) {
                            throw new \RuntimeException(
                                'Riwayat kelas asal gagal ditutup.'
                            );
                        }
                    }

                    if (!$this->db->table('riwayat_siswa')->insert([
                        'id_siswa' => $idSiswa,
                        'id_tahun' => $idTahun,
                        'id_kelas' => $idKelasTujuan,
                        'status' => 'Aktif',
                        'tanggal_mulai' => $today,
                        'tanggal_selesai' => null,
                        'keterangan' => sprintf(
                            'Pindah kelas dari %s ke %s.',
                            $kelasAsal,
                            $kelasTujuan['nama_kelas']
                        ),
                        'created_at' => date('Y-m-d H:i:s'),
                    ])) {
                        throw new \RuntimeException(
                            'Riwayat kelas tujuan gagal disimpan.'
                        );
                    }
                }

                $action = 'MOVE_CLASS';
                $message = sprintf(
                    'Siswa berhasil dipindahkan dari %s ke %s.',
                    $kelasAsal,
                    $kelasTujuan['nama_kelas']
                );
                $logText = sprintf(
                    'Memindahkan siswa ID %d - %s dari %s ke %s.',
                    $idSiswa,
                    $siswa['nama'],
                    $kelasAsal,
                    $kelasTujuan['nama_kelas']
                );
            }

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException(
                    'Transaksi penempatan kelas gagal.'
                );
            }

            $this->activityLog->write(
                $actorUserId,
                $action,
                'Manajemen Siswa',
                $logText
            );

            $this->db->transCommit();

            return [
                'success' => true,
                'message' => $message,
                'id_kelas' => $idKelasTujuan,
                'nama_kelas' => $kelasTujuan['nama_kelas'],
                'action' => $action,
            ];
        } catch (Throwable $e) {
            $this->db->transRollback();

            return $this->fail(
                'CLASS_CHANGE_FAILED',
                $e->getMessage()
            );
        }
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
