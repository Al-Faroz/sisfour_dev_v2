<?php

namespace App\Services;

use Config\Database;
use Throwable;

/**
 * Workflow pergantian semester dalam tahun pelajaran yang sama.
 *
 * Ganjil -> Genap bukan kenaikan kelas. Persiapan membuat konteks operasional
 * Semester Genap (kelas, membership siswa aktif, dan opsional mapping wali)
 * tanpa menyentuh data transaksi Semester Ganjil. Jadwal Genap tetap harus
 * diimport/review terpisah sebelum aktivasi.
 *
 * Aktivasi Semester Genap menutup riwayat Aktif Semester Ganjil dan membuka
 * riwayat Aktif Semester Genap secara atomik. Membership tahun/semester lama
 * tetap dipertahankan sebagai jejak per periode.
 */
class SemesterTransitionService
{
    protected $db;
    protected AuthService $authService;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->authService = new AuthService();
    }

    public function prepareFromActive(bool $copyWali = true): array
    {
        if (! $this->canManage()) {
            return $this->failure(
                'FORBIDDEN',
                'Anda tidak memiliki hak mengelola Master Tahun Ajaran.'
            );
        }

        $source = $this->getActiveYear();

        if ($source === null) {
            return $this->failure(
                'NO_ACTIVE_YEAR',
                'Tahun ajaran aktif belum tersedia.'
            );
        }

        if ((string) $source['semester'] !== 'Ganjil') {
            return $this->failure(
                'INVALID_TRANSITION',
                'Persiapan semester berikutnya hanya berlaku dari Semester Ganjil ke Semester Genap pada tahun pelajaran yang sama.'
            );
        }

        $targetAny = $this->db
            ->table('tahun_ajaran')
            ->where('nama_tahun', (string) $source['nama_tahun'])
            ->where('semester', 'Genap')
            ->get()
            ->getRowArray();

        if ($targetAny !== null && ! empty($targetAny['deleted_at'])) {
            return $this->failure(
                'TARGET_IN_RECYCLE',
                'Semester Genap untuk tahun pelajaran ini berada di Recycle Bin. Pulihkan data tersebut terlebih dahulu.'
            );
        }

        $target = $targetAny;

        if ($target !== null && (int) $target['status_aktif'] === 1) {
            return $this->failure(
                'TARGET_ALREADY_ACTIVE',
                'Semester Genap tersebut sudah aktif.'
            );
        }

        if ($target !== null && $this->hasOperationalData((int) $target['id'])) {
            $prepared = $this->isPreparedStructure(
                (int) $source['id'],
                (int) $target['id'],
                $copyWali
            );

            if ($prepared) {
                return [
                    'success' => true,
                    'code' => 'ALREADY_PREPARED',
                    'message' => 'Semester Genap sudah pernah disiapkan. Review Mapping Wali dan import/review Jadwal Guru sebelum aktivasi.',
                    'source' => $source,
                    'target' => $target,
                    'counts' => $this->transitionCounts(
                        (int) $source['id'],
                        (int) $target['id']
                    ),
                ];
            }

            return $this->failure(
                'TARGET_NOT_EMPTY',
                'Semester Genap sudah memiliki data operasional yang tidak identik dengan hasil persiapan otomatis. Jangan menimpa data tersebut; review data Semester Genap secara manual.'
            );
        }

        $sourceClasses = $this->db
            ->table('kelas')
            ->select('id, tingkat, rombel, nama_kelas')
            ->where('id_tahun', (int) $source['id'])
            ->where('deleted_at', null)
            ->orderBy('tingkat', 'ASC')
            ->orderBy('rombel', 'ASC')
            ->get()
            ->getResultArray();

        if ($sourceClasses === []) {
            return $this->failure(
                'NO_SOURCE_CLASS',
                'Semester Ganjil aktif belum memiliki kelas yang dapat disalin.'
            );
        }

        $sourceMembers = $this->sourceActiveMemberships((int) $source['id']);

        if ($sourceMembers === []) {
            return $this->failure(
                'NO_SOURCE_MEMBERSHIP',
                'Semester Ganjil aktif belum memiliki membership siswa aktif yang dapat disalin.'
            );
        }

        $sourceWali = $this->db
            ->table('mapping_wali_kelas')
            ->select('id_guru, id_kelas')
            ->where('id_tahun', (int) $source['id'])
            ->where('deleted_at', null)
            ->get()
            ->getResultArray();

        $now = date('Y-m-d H:i:s');
        $this->db->transBegin();

        try {
            if ($target === null) {
                $inserted = $this->db
                    ->table('tahun_ajaran')
                    ->insert([
                        'nama_tahun' => (string) $source['nama_tahun'],
                        'semester' => 'Genap',
                        'status_aktif' => 0,
                        'deleted_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                if (! $inserted) {
                    throw new \RuntimeException(
                        'Semester Genap gagal dibuat.'
                    );
                }

                $idTarget = (int) $this->db->insertID();
            } else {
                $idTarget = (int) $target['id'];
            }

            $classMap = [];

            foreach ($sourceClasses as $kelas) {
                $inserted = $this->db
                    ->table('kelas')
                    ->insert([
                        'tingkat' => (string) $kelas['tingkat'],
                        'rombel' => (string) $kelas['rombel'],
                        'nama_kelas' => (string) $kelas['nama_kelas'],
                        'id_tahun' => $idTarget,
                        'deleted_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                if (! $inserted) {
                    throw new \RuntimeException(
                        'Struktur kelas Semester Genap gagal dibuat.'
                    );
                }

                $classMap[(int) $kelas['id']] = (int) $this->db->insertID();
            }

            foreach ($sourceMembers as $anggota) {
                $sourceClassId = (int) $anggota['id_kelas'];

                if (! isset($classMap[$sourceClassId])) {
                    throw new \RuntimeException(
                        'Membership siswa mengacu ke kelas sumber yang tidak dapat dipetakan.'
                    );
                }

                if (! $this->db->table('anggota_kelas')->insert([
                    'id_siswa' => (int) $anggota['id_siswa'],
                    'id_kelas' => $classMap[$sourceClassId],
                    'id_tahun' => $idTarget,
                ])) {
                    throw new \RuntimeException(
                        'Membership siswa Semester Genap gagal dibuat.'
                    );
                }
            }

            $waliCopied = 0;

            if ($copyWali) {
                foreach ($sourceWali as $wali) {
                    $sourceClassId = (int) $wali['id_kelas'];

                    if (! isset($classMap[$sourceClassId])) {
                        throw new \RuntimeException(
                            'Mapping Wali mengacu ke kelas sumber yang tidak dapat dipetakan.'
                        );
                    }

                    if (! $this->db->table('mapping_wali_kelas')->insert([
                        'id_guru' => (int) $wali['id_guru'],
                        'id_kelas' => $classMap[$sourceClassId],
                        'id_tahun' => $idTarget,
                        'deleted_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])) {
                        throw new \RuntimeException(
                            'Mapping Wali Semester Genap gagal disalin.'
                        );
                    }

                    $waliCopied++;
                }
            }

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException(
                    'Transaksi persiapan Semester Genap gagal.'
                );
            }

            $this->logActivity(
                'SIAPKAN_SEMESTER',
                sprintf(
                    'Menyiapkan %s - Genap dari Semester Ganjil: %d kelas, %d membership siswa aktif, %d mapping wali. Jadwal Guru tidak disalin otomatis.',
                    (string) $source['nama_tahun'],
                    count($sourceClasses),
                    count($sourceMembers),
                    $waliCopied
                )
            );

            $this->db->transCommit();

            $target = $this->getYearById($idTarget);

            return [
                'success' => true,
                'code' => 'PREPARED',
                'message' => 'Semester Genap berhasil disiapkan dalam status Nonaktif. Kelas dan membership siswa aktif sudah disalin. Review Mapping Wali lalu import/review Jadwal Guru sebelum aktivasi.',
                'source' => $source,
                'target' => $target,
                'counts' => [
                    'kelas' => count($sourceClasses),
                    'anggota' => count($sourceMembers),
                    'wali' => $waliCopied,
                    'jadwal' => 0,
                ],
            ];
        } catch (Throwable $e) {
            $this->db->transRollback();

            return $this->failure(
                'PREPARE_FAILED',
                $e->getMessage()
            );
        }
    }

    /**
     * Jika target bukan transisi Ganjil -> Genap tahun yang sama, return null
     * agar caller dapat memakai mekanisme aktivasi umum yang sudah ada.
     */
    public function activateIfSemesterTransition(int $targetId): ?array
    {
        if (! $this->canManage()) {
            return $this->failure(
                'FORBIDDEN',
                'Anda tidak memiliki hak mengelola Master Tahun Ajaran.'
            );
        }

        $source = $this->getActiveYear();
        $target = $this->getYearById($targetId);

        if ($target === null) {
            return $this->failure(
                'NOT_FOUND',
                'Tahun ajaran tujuan tidak ditemukan.'
            );
        }

        if ($source === null) {
            return null;
        }

        if (
            (string) $source['semester'] !== 'Ganjil'
            || (string) $target['semester'] !== 'Genap'
            || (string) $source['nama_tahun'] !== (string) $target['nama_tahun']
        ) {
            return null;
        }

        if ((int) $target['status_aktif'] === 1) {
            return [
                'success' => true,
                'message' => 'Semester Genap tersebut sudah aktif.',
            ];
        }

        $check = $this->activationPrecheck(
            (int) $source['id'],
            (int) $target['id']
        );

        if (! $check['ready']) {
            return [
                'success' => false,
                'code' => 'SEMESTER_NOT_READY',
                'message' => 'Semester Genap belum siap diaktifkan: '
                    . implode(' ', $check['errors']),
                'precheck' => $check,
            ];
        }

        $members = $this->sourceActiveMemberships((int) $target['id']);
        $tanggal = date('Y-m-d');
        $now = date('Y-m-d H:i:s');

        $this->db->transBegin();

        try {
            foreach ($members as $anggota) {
                $idSiswa = (int) $anggota['id_siswa'];

                $activeSourceHistory = $this->db
                    ->table('riwayat_siswa')
                    ->select('id')
                    ->where('id_siswa', $idSiswa)
                    ->where('id_tahun', (int) $source['id'])
                    ->where('status', 'Aktif')
                    ->where('tanggal_selesai', null)
                    ->get()
                    ->getRowArray();

                if ($activeSourceHistory === null) {
                    throw new \RuntimeException(
                        "Histori aktif siswa ID {$idSiswa} pada Semester Ganjil tidak ditemukan."
                    );
                }

                $closed = $this->db
                    ->table('riwayat_siswa')
                    ->where('id', (int) $activeSourceHistory['id'])
                    ->update([
                        'tanggal_selesai' => $tanggal,
                    ]);

                if (! $closed) {
                    throw new \RuntimeException(
                        "Histori Semester Ganjil siswa ID {$idSiswa} gagal ditutup."
                    );
                }

                $existingTargetHistory = $this->db
                    ->table('riwayat_siswa')
                    ->where('id_siswa', $idSiswa)
                    ->where('id_tahun', (int) $target['id'])
                    ->where('status', 'Aktif')
                    ->where('tanggal_selesai', null)
                    ->countAllResults();

                if ($existingTargetHistory > 0) {
                    throw new \RuntimeException(
                        "Histori aktif Semester Genap siswa ID {$idSiswa} sudah ada."
                    );
                }

                if (! $this->db->table('riwayat_siswa')->insert([
                    'id_siswa' => $idSiswa,
                    'id_tahun' => (int) $target['id'],
                    'id_kelas' => (int) $anggota['id_kelas'],
                    'status' => 'Aktif',
                    'tanggal_mulai' => $tanggal,
                    'tanggal_selesai' => null,
                    'keterangan' => 'Pergantian semester dari '
                        . (string) $source['nama_tahun']
                        . ' - Ganjil ke Genap.',
                    'created_at' => $now,
                ])) {
                    throw new \RuntimeException(
                        "Histori Semester Genap siswa ID {$idSiswa} gagal dibuat."
                    );
                }
            }

            $this->db
                ->table('tahun_ajaran')
                ->where('status_aktif', 1)
                ->where('id !=', (int) $target['id'])
                ->update([
                    'status_aktif' => 0,
                    'updated_at' => $now,
                ]);

            $activated = $this->db
                ->table('tahun_ajaran')
                ->where('id', (int) $target['id'])
                ->where('deleted_at', null)
                ->update([
                    'status_aktif' => 1,
                    'updated_at' => $now,
                ]);

            if (! $activated || $this->db->transStatus() === false) {
                throw new \RuntimeException(
                    'Aktivasi Semester Genap gagal.'
                );
            }

            $this->logActivity(
                'AKTIFKAN_SEMESTER',
                sprintf(
                    'Mengaktifkan %s - Genap dan menutup histori Aktif Semester Ganjil untuk %d siswa.',
                    (string) $target['nama_tahun'],
                    count($members)
                )
            );

            $this->db->transCommit();

            return [
                'success' => true,
                'code' => 'SEMESTER_ACTIVATED',
                'message' => 'Semester Genap berhasil diaktifkan. Semester Ganjil dinonaktifkan, histori Ganjil ditutup, dan histori Aktif Genap dibuat tanpa menghapus membership semester sebelumnya.',
                'precheck' => $check,
            ];
        } catch (Throwable $e) {
            $this->db->transRollback();

            return $this->failure(
                'ACTIVATION_FAILED',
                $e->getMessage()
            );
        }
    }

    private function activationPrecheck(
        int $sourceId,
        int $targetId
    ): array {
        $errors = [];

        $sourceClasses = $this->classNameSet($sourceId);
        $targetClasses = $this->classNameSet($targetId);

        if ($sourceClasses !== $targetClasses) {
            $errors[] = 'Struktur kelas Semester Genap belum identik dengan Semester Ganjil.';
        }

        $sourceMembers = $this->membershipMap($sourceId);
        $targetMembers = $this->membershipMap($targetId);

        if ($sourceMembers !== $targetMembers) {
            $errors[] = 'Membership siswa aktif Semester Genap belum identik dengan Semester Ganjil.';
        }

        $sourceHistory = $this->activeHistoryStudentIds($sourceId);
        $sourceMemberIds = array_keys($sourceMembers);
        sort($sourceMemberIds);

        if ($sourceHistory !== $sourceMemberIds) {
            $errors[] = 'Histori Aktif Semester Ganjil tidak konsisten dengan membership siswa aktif.';
        }

        $targetActiveHistory = $this->activeHistoryStudentIds($targetId);

        if ($targetActiveHistory !== []) {
            $errors[] = 'Semester Genap sudah mempunyai histori Aktif sebelum proses aktivasi.';
        }

        $sourceWali = $this->activeWaliCount($sourceId);
        $targetWali = $this->activeWaliCount($targetId);

        if ($sourceWali > 0 && $targetWali !== $sourceWali) {
            $errors[] = sprintf(
                'Mapping Wali Semester Genap belum lengkap (%d/%d).',
                $targetWali,
                $sourceWali
            );
        }

        $sourceScheduleCount = $this->activeScheduleCount($sourceId);
        $targetScheduleCount = $this->activeScheduleCount($targetId);

        if ($sourceScheduleCount > 0 && $targetScheduleCount === 0) {
            $errors[] = 'Jadwal Guru Semester Genap belum tersedia. Import/review Jadwal Genap sebelum aktivasi.';
        }

        if ($targetScheduleCount > 0) {
            $invalidGroups = $this->invalidScheduleGroupCount($targetId);

            if ($invalidGroups > 0) {
                $errors[] = sprintf(
                    'Topology Sesi Jadwal Semester Genap belum valid pada %d kelompok kelas/hari.',
                    $invalidGroups
                );
            }
        }

        return [
            'ready' => $errors === [],
            'errors' => $errors,
            'counts' => $this->transitionCounts($sourceId, $targetId),
        ];
    }

    private function isPreparedStructure(
        int $sourceId,
        int $targetId,
        bool $copyWali
    ): bool {
        if ($this->classNameSet($sourceId) !== $this->classNameSet($targetId)) {
            return false;
        }

        if ($this->membershipMap($sourceId) !== $this->membershipMap($targetId)) {
            return false;
        }

        if ($this->activeHistoryStudentIds($targetId) !== []) {
            return false;
        }

        if (
            $copyWali
            && $this->activeWaliCount($sourceId)
                !== $this->activeWaliCount($targetId)
        ) {
            return false;
        }

        return $this->countByYear('presensi', $targetId) === 0
            && $this->countByYear('presensi_mengajar', $targetId) === 0;
    }

    private function hasOperationalData(int $idTahun): bool
    {
        foreach ([
            'kelas',
            'anggota_kelas',
            'mapping_wali_kelas',
            'jadwal_guru',
            'riwayat_siswa',
            'presensi',
            'presensi_mengajar',
        ] as $table) {
            if ($this->countByYear($table, $idTahun) > 0) {
                return true;
            }
        }

        return false;
    }

    private function classNameSet(int $idTahun): array
    {
        $rows = $this->db
            ->table('kelas')
            ->select('nama_kelas')
            ->where('id_tahun', $idTahun)
            ->where('deleted_at', null)
            ->orderBy('nama_kelas', 'ASC')
            ->get()
            ->getResultArray();

        return array_values(array_map(
            static fn (array $row): string => (string) $row['nama_kelas'],
            $rows
        ));
    }

    private function membershipMap(int $idTahun): array
    {
        $rows = $this->db
            ->table('anggota_kelas ak')
            ->select('ak.id_siswa, k.nama_kelas')
            ->join('kelas k', 'k.id = ak.id_kelas')
            ->join('siswa s', 's.id = ak.id_siswa')
            ->where('ak.id_tahun', $idTahun)
            ->where('k.deleted_at', null)
            ->where('s.deleted_at', null)
            ->where('s.status_aktif', 'Aktif')
            ->get()
            ->getResultArray();

        $map = [];

        foreach ($rows as $row) {
            $map[(int) $row['id_siswa']] = (string) $row['nama_kelas'];
        }

        ksort($map);

        return $map;
    }

    private function sourceActiveMemberships(int $idTahun): array
    {
        return $this->db
            ->table('anggota_kelas ak')
            ->select('ak.id_siswa, ak.id_kelas, ak.id_tahun')
            ->join('siswa s', 's.id = ak.id_siswa')
            ->where('ak.id_tahun', $idTahun)
            ->where('s.deleted_at', null)
            ->where('s.status_aktif', 'Aktif')
            ->orderBy('ak.id_siswa', 'ASC')
            ->get()
            ->getResultArray();
    }

    private function activeHistoryStudentIds(int $idTahun): array
    {
        $rows = $this->db
            ->table('riwayat_siswa')
            ->select('id_siswa')
            ->where('id_tahun', $idTahun)
            ->where('status', 'Aktif')
            ->where('tanggal_selesai', null)
            ->orderBy('id_siswa', 'ASC')
            ->get()
            ->getResultArray();

        $ids = array_map(
            static fn (array $row): int => (int) $row['id_siswa'],
            $rows
        );

        sort($ids);

        return $ids;
    }

    private function activeWaliCount(int $idTahun): int
    {
        return $this->db
            ->table('mapping_wali_kelas')
            ->where('id_tahun', $idTahun)
            ->where('deleted_at', null)
            ->countAllResults();
    }

    private function activeScheduleCount(int $idTahun): int
    {
        return $this->db
            ->table('jadwal_guru')
            ->where('id_tahun', $idTahun)
            ->where('status_jadwal', 'Aktif')
            ->countAllResults();
    }

    private function invalidScheduleGroupCount(int $idTahun): int
    {
        $rows = $this->db
            ->table('jadwal_guru')
            ->select('id, id_kelas, hari, jam_mulai, jam_selesai, sesi')
            ->where('id_tahun', $idTahun)
            ->where('status_jadwal', 'Aktif')
            ->orderBy('id_kelas', 'ASC')
            ->orderBy('hari', 'ASC')
            ->orderBy('jam_mulai', 'ASC')
            ->orderBy('jam_selesai', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        $groups = [];

        foreach ($rows as $row) {
            $key = (int) $row['id_kelas'] . '|' . (string) $row['hari'];
            $groups[$key][] = $row;
        }

        $invalid = 0;

        foreach ($groups as $items) {
            $count = count($items);

            if ($count < 2) {
                $invalid++;
                continue;
            }

            if ((string) $items[0]['sesi'] !== 'Sesi Awal') {
                $invalid++;
                continue;
            }

            if ((string) $items[$count - 1]['sesi'] !== 'Sesi Akhir') {
                $invalid++;
                continue;
            }

            for ($i = 1; $i < $count - 1; $i++) {
                if ((string) $items[$i]['sesi'] !== 'Non Sesi') {
                    $invalid++;
                    break;
                }
            }
        }

        return $invalid;
    }

    private function transitionCounts(int $sourceId, int $targetId): array
    {
        return [
            'source' => [
                'kelas' => $this->countActiveClasses($sourceId),
                'anggota' => count($this->membershipMap($sourceId)),
                'wali' => $this->activeWaliCount($sourceId),
                'jadwal' => $this->activeScheduleCount($sourceId),
            ],
            'target' => [
                'kelas' => $this->countActiveClasses($targetId),
                'anggota' => count($this->membershipMap($targetId)),
                'wali' => $this->activeWaliCount($targetId),
                'jadwal' => $this->activeScheduleCount($targetId),
            ],
        ];
    }

    private function countActiveClasses(int $idTahun): int
    {
        return $this->db
            ->table('kelas')
            ->where('id_tahun', $idTahun)
            ->where('deleted_at', null)
            ->countAllResults();
    }

    private function countByYear(string $table, int $idTahun): int
    {
        if (! $this->db->tableExists($table)) {
            return 0;
        }

        return $this->db
            ->table($table)
            ->where('id_tahun', $idTahun)
            ->countAllResults();
    }

    private function getActiveYear(): ?array
    {
        $row = $this->db
            ->table('tahun_ajaran')
            ->where('status_aktif', 1)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    private function getYearById(int $id): ?array
    {
        $row = $this->db
            ->table('tahun_ajaran')
            ->where('id', $id)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    private function canManage(): bool
    {
        $userId = (int) (session()->get('user_id') ?? 0);

        return $userId > 0
            && $this->authService->resolveScope(
                'master_tahun_ajaran.manage',
                $userId
            ) === 'SEMUA';
    }

    private function logActivity(
        string $aksi,
        string $keterangan
    ): void {
        $idUser = session()->get('user_id');

        $this->db
            ->table('log_activity')
            ->insert([
                'id_user' => $idUser ? (int) $idUser : null,
                'aksi' => $aksi,
                'modul' => 'Master Tahun Ajaran',
                'keterangan' => $keterangan,
                'waktu' => date('Y-m-d H:i:s'),
            ]);
    }

    private function failure(
        string $code,
        string $message
    ): array {
        return [
            'success' => false,
            'code' => $code,
            'message' => $message,
        ];
    }
}
