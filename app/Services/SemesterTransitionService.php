<?php

namespace App\Services;

use Config\Database;
use Throwable;

/**
 * Atomic Ganjil -> Genap transition for the same academic year.
 *
 * One execution creates and activates Semester Genap while preserving all
 * Semester Ganjil transactional history. Kelas, active-student membership,
 * active Wali mapping, and active Guru schedules are copied as the new
 * semester baseline. Presensi and Jurnal Mengajar are never copied.
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

    public function transitionFromActive(): array
    {
        $steps = $this->stepBlueprint();

        if (! $this->canManage()) {
            return $this->precheckFailure(
                $steps,
                'FORBIDDEN',
                'Anda tidak memiliki hak mengelola Master Tahun Ajaran.'
            );
        }

        $source = $this->getActiveYear();

        if ($source === null) {
            return $this->precheckFailure(
                $steps,
                'NO_ACTIVE_YEAR',
                'Tahun ajaran aktif belum tersedia.'
            );
        }

        if ((string) $source['semester'] !== 'Ganjil') {
            return $this->precheckFailure(
                $steps,
                'INVALID_TRANSITION',
                'Siapkan Genap hanya dapat dijalankan ketika Semester Ganjil sedang aktif.'
            );
        }

        $targetAny = $this->db
            ->table('tahun_ajaran')
            ->where('nama_tahun', (string) $source['nama_tahun'])
            ->where('semester', 'Genap')
            ->get()
            ->getRowArray();

        if ($targetAny !== null) {
            $message = ! empty($targetAny['deleted_at'])
                ? 'Semester Genap untuk tahun pelajaran ini berada di Recycle Bin. Pulihkan atau selesaikan data tersebut sebelum menjalankan Siapkan Genap.'
                : 'Semester Genap untuk tahun pelajaran ini sudah ada. Proses tidak dijalankan ulang agar data tidak terduplikasi.';

            return $this->precheckFailure(
                $steps,
                'TARGET_EXISTS',
                $message
            );
        }

        $sourceId = (int) $source['id'];
        $sourceClasses = $this->sourceClasses($sourceId);
        $sourceMembers = $this->sourceActiveMemberships($sourceId);
        $sourceWali = $this->sourceActiveWali($sourceId);
        $sourceSchedules = $this->sourceActiveSchedules($sourceId);

        $precheckErrors = $this->sourcePrecheckErrors(
            $sourceId,
            $sourceClasses,
            $sourceMembers,
            $sourceWali,
            $sourceSchedules
        );

        if ($precheckErrors !== []) {
            return $this->precheckFailure(
                $steps,
                'SOURCE_NOT_READY',
                implode(' ', $precheckErrors)
            );
        }

        $steps = $this->markStep(
            $steps,
            'precheck',
            'success',
            null,
            null,
            sprintf(
                'Sumber valid: %d kelas, %d siswa aktif, %d wali, %d jadwal aktif.',
                count($sourceClasses),
                count($sourceMembers),
                count($sourceWali),
                count($sourceSchedules)
            )
        );

        $expected = [
            'kelas' => count($sourceClasses),
            'anggota' => count($sourceMembers),
            'wali' => count($sourceWali),
            'jadwal' => count($sourceSchedules),
            'histori' => count($sourceMembers),
        ];

        $steps = $this->setExpectedCounts($steps, $expected);
        $now = date('Y-m-d H:i:s');
        $tanggal = date('Y-m-d');
        $currentStep = 'create_year';
        $targetId = 0;

        $this->db->transBegin();

        try {
            $currentStep = 'create_year';
            if (! $this->db->table('tahun_ajaran')->insert([
                'nama_tahun' => (string) $source['nama_tahun'],
                'semester' => 'Genap',
                'status_aktif' => 0,
                'deleted_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ])) {
                throw new \RuntimeException('Semester Genap gagal dibuat.');
            }

            $targetId = (int) $this->db->insertID();
            $steps = $this->markStep(
                $steps,
                $currentStep,
                'success',
                1,
                1,
                'Semester Genap berhasil dibuat sebagai bagian dari transaksi.'
            );

            $currentStep = 'copy_classes';
            $classMap = [];
            foreach ($sourceClasses as $kelas) {
                if (! $this->db->table('kelas')->insert([
                    'tingkat' => (string) $kelas['tingkat'],
                    'rombel' => (string) $kelas['rombel'],
                    'nama_kelas' => (string) $kelas['nama_kelas'],
                    'id_tahun' => $targetId,
                    'deleted_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])) {
                    throw new \RuntimeException(
                        'Gagal menyalin kelas ' . (string) $kelas['nama_kelas'] . '.'
                    );
                }

                $classMap[(int) $kelas['id']] = (int) $this->db->insertID();
            }
            $steps = $this->markStep(
                $steps,
                $currentStep,
                'success',
                count($classMap),
                $expected['kelas'],
                'Struktur kelas berhasil disalin.'
            );

            $currentStep = 'copy_members';
            $memberCopied = 0;
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
                    'id_tahun' => $targetId,
                ])) {
                    throw new \RuntimeException(
                        'Gagal menyalin membership siswa ID ' . (int) $anggota['id_siswa'] . '.'
                    );
                }
                $memberCopied++;
            }
            $steps = $this->markStep(
                $steps,
                $currentStep,
                'success',
                $memberCopied,
                $expected['anggota'],
                'Anggota kelas siswa Aktif berhasil disalin.'
            );

            $currentStep = 'copy_wali';
            $waliCopied = 0;
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
                    'id_tahun' => $targetId,
                    'deleted_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])) {
                    throw new \RuntimeException(
                        'Gagal menyalin Mapping Wali Guru ID ' . (int) $wali['id_guru'] . '.'
                    );
                }
                $waliCopied++;
            }
            $steps = $this->markStep(
                $steps,
                $currentStep,
                'success',
                $waliCopied,
                $expected['wali'],
                'Mapping Wali berhasil disalin.'
            );

            $currentStep = 'copy_schedule';
            $scheduleCopied = 0;
            foreach ($sourceSchedules as $jadwal) {
                $sourceClassId = (int) $jadwal['id_kelas'];
                if (! isset($classMap[$sourceClassId])) {
                    throw new \RuntimeException(
                        'Jadwal Guru mengacu ke kelas sumber yang tidak dapat dipetakan.'
                    );
                }

                if (! $this->db->table('jadwal_guru')->insert([
                    'id_guru' => (int) $jadwal['id_guru'],
                    'id_kelas' => $classMap[$sourceClassId],
                    'id_mapel' => (int) $jadwal['id_mapel'],
                    'id_tahun' => $targetId,
                    'hari' => (string) $jadwal['hari'],
                    'jam_mulai' => (string) $jadwal['jam_mulai'],
                    'jam_selesai' => (string) $jadwal['jam_selesai'],
                    'sesi' => (string) $jadwal['sesi'],
                    'status_jadwal' => 'Aktif',
                ])) {
                    throw new \RuntimeException(
                        'Gagal menyalin Jadwal Guru ID ' . (int) $jadwal['id'] . '.'
                    );
                }
                $scheduleCopied++;
            }
            $steps = $this->markStep(
                $steps,
                $currentStep,
                'success',
                $scheduleCopied,
                $expected['jadwal'],
                'Jadwal Guru aktif berhasil disalin sebagai baseline Semester Genap.'
            );

            $memberIds = array_values(array_map(
                static fn (array $row): int => (int) $row['id_siswa'],
                $sourceMembers
            ));

            $currentStep = 'close_history';
            $closed = $this->db
                ->table('riwayat_siswa')
                ->where('id_tahun', $sourceId)
                ->where('status', 'Aktif')
                ->where('tanggal_selesai', null)
                ->whereIn('id_siswa', $memberIds)
                ->update(['tanggal_selesai' => $tanggal]);

            if (! $closed || $this->db->affectedRows() !== $expected['histori']) {
                throw new \RuntimeException(
                    'Jumlah histori Aktif Ganjil yang ditutup tidak sesuai dengan jumlah siswa aktif.'
                );
            }
            $steps = $this->markStep(
                $steps,
                $currentStep,
                'success',
                $expected['histori'],
                $expected['histori'],
                'Histori Aktif Semester Ganjil berhasil ditutup.'
            );

            $currentStep = 'open_history';
            $historyCreated = 0;
            foreach ($sourceMembers as $anggota) {
                $sourceClassId = (int) $anggota['id_kelas'];
                if (! $this->db->table('riwayat_siswa')->insert([
                    'id_siswa' => (int) $anggota['id_siswa'],
                    'id_tahun' => $targetId,
                    'id_kelas' => $classMap[$sourceClassId],
                    'status' => 'Aktif',
                    'tanggal_mulai' => $tanggal,
                    'tanggal_selesai' => null,
                    'keterangan' => 'Pergantian semester dari '
                        . (string) $source['nama_tahun']
                        . ' - Ganjil ke Genap.',
                    'created_at' => $now,
                ])) {
                    throw new \RuntimeException(
                        'Gagal membuat histori Genap siswa ID ' . (int) $anggota['id_siswa'] . '.'
                    );
                }
                $historyCreated++;
            }
            $steps = $this->markStep(
                $steps,
                $currentStep,
                'success',
                $historyCreated,
                $expected['histori'],
                'Histori Aktif Semester Genap berhasil dibuat.'
            );

            $currentStep = 'deactivate_source';
            $deactivated = $this->db
                ->table('tahun_ajaran')
                ->where('id', $sourceId)
                ->where('status_aktif', 1)
                ->update([
                    'status_aktif' => 0,
                    'updated_at' => $now,
                ]);

            if (! $deactivated || $this->db->affectedRows() !== 1) {
                throw new \RuntimeException('Semester Ganjil gagal dinonaktifkan.');
            }
            $steps = $this->markStep(
                $steps,
                $currentStep,
                'success',
                1,
                1,
                'Semester Ganjil berhasil dinonaktifkan.'
            );

            $currentStep = 'activate_target';
            $activated = $this->db
                ->table('tahun_ajaran')
                ->where('id', $targetId)
                ->where('status_aktif', 0)
                ->where('deleted_at', null)
                ->update([
                    'status_aktif' => 1,
                    'updated_at' => $now,
                ]);

            if (! $activated || $this->db->affectedRows() !== 1) {
                throw new \RuntimeException('Semester Genap gagal diaktifkan.');
            }
            $steps = $this->markStep(
                $steps,
                $currentStep,
                'success',
                1,
                1,
                'Semester Genap berhasil diaktifkan.'
            );

            $currentStep = 'verify';
            $verification = $this->finalVerification(
                $sourceId,
                $targetId,
                $expected
            );

            if (! $verification['ready']) {
                throw new \RuntimeException(
                    'Verifikasi akhir gagal: ' . implode(' ', $verification['errors'])
                );
            }

            $steps = $this->markStep(
                $steps,
                $currentStep,
                'success',
                null,
                null,
                'Verifikasi akhir berhasil. Presensi dan Jurnal Genap tetap kosong.'
            );

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi pergantian semester gagal.');
            }

            $this->logActivity(
                'SIAPKAN_GENAP',
                sprintf(
                    'Pergantian atomic %s Ganjil -> Genap: %d kelas, %d anggota, %d wali, %d jadwal, %d histori ditutup/dibuka.',
                    (string) $source['nama_tahun'],
                    $expected['kelas'],
                    $expected['anggota'],
                    $expected['wali'],
                    $expected['jadwal'],
                    $expected['histori']
                )
            );

            $this->db->transCommit();

            return [
                'success' => true,
                'code' => 'SEMESTER_TRANSITION_COMPLETED',
                'message' => sprintf(
                    '%s - Genap berhasil disiapkan dan diaktifkan. Semester Ganjil menjadi Nonaktif.',
                    (string) $source['nama_tahun']
                ),
                'rolled_back' => false,
                'source' => [
                    'id' => $sourceId,
                    'nama_tahun' => (string) $source['nama_tahun'],
                    'semester' => 'Ganjil',
                ],
                'target' => [
                    'id' => $targetId,
                    'nama_tahun' => (string) $source['nama_tahun'],
                    'semester' => 'Genap',
                ],
                'counts' => $verification['counts'],
                'steps' => array_values($steps),
            ];
        } catch (Throwable $e) {
            $this->db->transRollback();

            $steps = $this->rollbackSteps($steps, $currentStep, $e->getMessage());

            return [
                'success' => false,
                'code' => 'SEMESTER_TRANSITION_FAILED',
                'message' => $e->getMessage(),
                'rolled_back' => true,
                'source' => [
                    'id' => $sourceId,
                    'nama_tahun' => (string) $source['nama_tahun'],
                    'semester' => 'Ganjil',
                ],
                'target' => $targetId > 0 ? [
                    'id' => $targetId,
                    'nama_tahun' => (string) $source['nama_tahun'],
                    'semester' => 'Genap',
                ] : null,
                'steps' => array_values($steps),
            ];
        }
    }

    /**
     * Compatibility alias for the existing controller/service contract.
     */
    public function prepareFromActive(bool $copyWali = true): array
    {
        return $this->transitionFromActive();
    }

    private function sourcePrecheckErrors(
        int $sourceId,
        array $classes,
        array $members,
        array $wali,
        array $schedules
    ): array {
        $errors = [];

        if ($classes === []) {
            $errors[] = 'Semester Ganjil aktif belum memiliki kelas.';
        }

        if ($members === []) {
            $errors[] = 'Semester Ganjil aktif belum memiliki membership siswa Aktif.';
        }

        $classIds = [];
        foreach ($classes as $kelas) {
            $classIds[(int) $kelas['id']] = true;
        }

        foreach ($members as $row) {
            if (! isset($classIds[(int) $row['id_kelas']])) {
                $errors[] = 'Ada membership siswa yang mengacu ke kelas di luar Semester Ganjil aktif.';
                break;
            }
        }

        foreach ($wali as $row) {
            if (! isset($classIds[(int) $row['id_kelas']])) {
                $errors[] = 'Ada Mapping Wali yang mengacu ke kelas di luar Semester Ganjil aktif.';
                break;
            }
        }

        foreach ($schedules as $row) {
            if (! isset($classIds[(int) $row['id_kelas']])) {
                $errors[] = 'Ada Jadwal Guru yang mengacu ke kelas di luar Semester Ganjil aktif.';
                break;
            }
        }

        $memberByStudent = [];
        foreach ($members as $row) {
            $memberByStudent[(int) $row['id_siswa']] = (int) $row['id_kelas'];
        }
        ksort($memberByStudent);

        $historyRows = $this->db
            ->table('riwayat_siswa')
            ->select('id_siswa, id_kelas')
            ->where('id_tahun', $sourceId)
            ->where('status', 'Aktif')
            ->where('tanggal_selesai', null)
            ->get()
            ->getResultArray();

        $historyByStudent = [];
        $duplicateHistory = false;
        foreach ($historyRows as $row) {
            $idSiswa = (int) $row['id_siswa'];
            if (isset($historyByStudent[$idSiswa])) {
                $duplicateHistory = true;
                break;
            }
            $historyByStudent[$idSiswa] = (int) $row['id_kelas'];
        }
        ksort($historyByStudent);

        if ($duplicateHistory || $memberByStudent !== $historyByStudent) {
            $errors[] = 'Histori Aktif Semester Ganjil tidak identik dengan membership siswa Aktif.';
        }

        $scheduleErrors = $this->scheduleTopologyErrors($schedules);
        if ($scheduleErrors !== []) {
            $errors[] = $scheduleErrors[0];
        }

        return $errors;
    }

    private function finalVerification(
        int $sourceId,
        int $targetId,
        array $expected
    ): array {
        $errors = [];

        if ($this->classSignature($sourceId) !== $this->classSignature($targetId)) {
            $errors[] = 'Struktur kelas Genap tidak identik dengan Ganjil.';
        }

        if ($this->membershipSignature($sourceId) !== $this->membershipSignature($targetId)) {
            $errors[] = 'Membership siswa Aktif Genap tidak identik dengan Ganjil.';
        }

        if ($this->waliSignature($sourceId) !== $this->waliSignature($targetId)) {
            $errors[] = 'Mapping Wali Genap tidak identik dengan Ganjil.';
        }

        if ($this->scheduleSignature($sourceId) !== $this->scheduleSignature($targetId)) {
            $errors[] = 'Jadwal Guru Genap tidak identik dengan baseline Ganjil.';
        }

        $sourceOpenHistory = $this->openActiveHistoryCount($sourceId);
        $targetOpenHistory = $this->openActiveHistoryCount($targetId);

        if ($sourceOpenHistory !== 0) {
            $errors[] = 'Masih ada histori Aktif Ganjil yang belum ditutup.';
        }

        if ($targetOpenHistory !== $expected['histori']) {
            $errors[] = 'Jumlah histori Aktif Genap tidak sesuai jumlah siswa Aktif.';
        }

        $activeRows = $this->db
            ->table('tahun_ajaran')
            ->select('id')
            ->where('status_aktif', 1)
            ->where('deleted_at', null)
            ->get()
            ->getResultArray();

        if (count($activeRows) !== 1 || (int) $activeRows[0]['id'] !== $targetId) {
            $errors[] = 'Status semester aktif tidak tunggal pada Semester Genap.';
        }

        $presensi = $this->countByYear('presensi', $targetId);
        $jurnal = $this->countByYear('presensi_mengajar', $targetId);

        if ($presensi !== 0) {
            $errors[] = 'Presensi Semester Genap seharusnya masih kosong.';
        }

        if ($jurnal !== 0) {
            $errors[] = 'Jurnal Mengajar Semester Genap seharusnya masih kosong.';
        }

        $targetSchedules = $this->sourceActiveSchedules($targetId);
        $scheduleErrors = $this->scheduleTopologyErrors($targetSchedules);
        if ($scheduleErrors !== []) {
            $errors[] = 'Topology Jadwal Genap tidak valid setelah penyalinan.';
        }

        return [
            'ready' => $errors === [],
            'errors' => $errors,
            'counts' => [
                'kelas' => count($this->sourceClasses($targetId)),
                'anggota' => count($this->sourceActiveMemberships($targetId)),
                'wali' => count($this->sourceActiveWali($targetId)),
                'jadwal' => count($targetSchedules),
                'histori_aktif_ganjil' => $sourceOpenHistory,
                'histori_aktif_genap' => $targetOpenHistory,
                'presensi_genap' => $presensi,
                'jurnal_genap' => $jurnal,
            ],
        ];
    }

    private function sourceClasses(int $idTahun): array
    {
        return $this->db
            ->table('kelas')
            ->select('id, tingkat, rombel, nama_kelas')
            ->where('id_tahun', $idTahun)
            ->where('deleted_at', null)
            ->orderBy('tingkat', 'ASC')
            ->orderBy('rombel', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();
    }

    private function sourceActiveMemberships(int $idTahun): array
    {
        return $this->db
            ->table('anggota_kelas ak')
            ->select('ak.id_siswa, ak.id_kelas')
            ->join('siswa s', 's.id = ak.id_siswa')
            ->where('ak.id_tahun', $idTahun)
            ->where('s.status_aktif', 'Aktif')
            ->where('s.deleted_at', null)
            ->orderBy('ak.id_siswa', 'ASC')
            ->get()
            ->getResultArray();
    }

    private function sourceActiveWali(int $idTahun): array
    {
        return $this->db
            ->table('mapping_wali_kelas')
            ->select('id_guru, id_kelas')
            ->where('id_tahun', $idTahun)
            ->where('deleted_at', null)
            ->orderBy('id_kelas', 'ASC')
            ->get()
            ->getResultArray();
    }

    private function sourceActiveSchedules(int $idTahun): array
    {
        return $this->db
            ->table('jadwal_guru')
            ->select(
                'id, id_guru, id_kelas, id_mapel, hari, jam_mulai, ' .
                'jam_selesai, sesi, status_jadwal'
            )
            ->where('id_tahun', $idTahun)
            ->where('status_jadwal', 'Aktif')
            ->orderBy('id_kelas', 'ASC')
            ->orderBy('hari', 'ASC')
            ->orderBy('jam_mulai', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();
    }

    private function scheduleTopologyErrors(array $rows): array
    {
        $groups = [];

        foreach ($rows as $row) {
            $key = (int) $row['id_kelas'] . '|' . (string) $row['hari'];
            $groups[$key][] = $row;
        }

        $errors = [];

        foreach ($groups as $key => $items) {
            usort($items, static function (array $a, array $b): int {
                return [
                    (string) $a['jam_mulai'],
                    (string) $a['jam_selesai'],
                    (int) $a['id'],
                ] <=> [
                    (string) $b['jam_mulai'],
                    (string) $b['jam_selesai'],
                    (int) $b['id'],
                ];
            });

            $count = count($items);
            if ($count < 2) {
                $errors[] = 'Topology Jadwal sumber tidak valid pada kelompok ' . $key . ': minimal dua slot diperlukan.';
                continue;
            }

            if ((string) $items[0]['sesi'] !== 'Sesi Awal') {
                $errors[] = 'Topology Jadwal sumber tidak valid pada kelompok ' . $key . ': slot pertama bukan Sesi Awal.';
            }

            if ((string) $items[$count - 1]['sesi'] !== 'Sesi Akhir') {
                $errors[] = 'Topology Jadwal sumber tidak valid pada kelompok ' . $key . ': slot terakhir bukan Sesi Akhir.';
            }

            for ($i = 1; $i < $count - 1; $i++) {
                if ((string) $items[$i]['sesi'] !== 'Non Sesi') {
                    $errors[] = 'Topology Jadwal sumber tidak valid pada kelompok ' . $key . ': slot tengah wajib Non Sesi.';
                    break;
                }
            }
        }

        return $errors;
    }

    private function classSignature(int $idTahun): array
    {
        $rows = $this->sourceClasses($idTahun);
        $signature = array_map(
            static fn (array $row): string => implode('|', [
                (string) $row['tingkat'],
                (string) $row['rombel'],
                (string) $row['nama_kelas'],
            ]),
            $rows
        );
        sort($signature);
        return $signature;
    }

    private function membershipSignature(int $idTahun): array
    {
        $rows = $this->db
            ->table('anggota_kelas ak')
            ->select('ak.id_siswa, k.nama_kelas')
            ->join('kelas k', 'k.id = ak.id_kelas')
            ->join('siswa s', 's.id = ak.id_siswa')
            ->where('ak.id_tahun', $idTahun)
            ->where('s.status_aktif', 'Aktif')
            ->where('s.deleted_at', null)
            ->where('k.deleted_at', null)
            ->get()
            ->getResultArray();

        $signature = array_map(
            static fn (array $row): string => (int) $row['id_siswa'] . '|' . (string) $row['nama_kelas'],
            $rows
        );
        sort($signature);
        return $signature;
    }

    private function waliSignature(int $idTahun): array
    {
        $rows = $this->db
            ->table('mapping_wali_kelas mw')
            ->select('mw.id_guru, k.nama_kelas')
            ->join('kelas k', 'k.id = mw.id_kelas')
            ->where('mw.id_tahun', $idTahun)
            ->where('mw.deleted_at', null)
            ->where('k.deleted_at', null)
            ->get()
            ->getResultArray();

        $signature = array_map(
            static fn (array $row): string => (int) $row['id_guru'] . '|' . (string) $row['nama_kelas'],
            $rows
        );
        sort($signature);
        return $signature;
    }

    private function scheduleSignature(int $idTahun): array
    {
        $rows = $this->db
            ->table('jadwal_guru jg')
            ->select(
                'jg.id_guru, jg.id_mapel, jg.hari, jg.jam_mulai, ' .
                'jg.jam_selesai, jg.sesi, k.nama_kelas'
            )
            ->join('kelas k', 'k.id = jg.id_kelas')
            ->where('jg.id_tahun', $idTahun)
            ->where('jg.status_jadwal', 'Aktif')
            ->where('k.deleted_at', null)
            ->get()
            ->getResultArray();

        $signature = array_map(
            static fn (array $row): string => implode('|', [
                (int) $row['id_guru'],
                (string) $row['nama_kelas'],
                (int) $row['id_mapel'],
                (string) $row['hari'],
                (string) $row['jam_mulai'],
                (string) $row['jam_selesai'],
                (string) $row['sesi'],
            ]),
            $rows
        );
        sort($signature);
        return $signature;
    }

    private function openActiveHistoryCount(int $idTahun): int
    {
        return (int) $this->db
            ->table('riwayat_siswa')
            ->where('id_tahun', $idTahun)
            ->where('status', 'Aktif')
            ->where('tanggal_selesai', null)
            ->countAllResults();
    }

    private function countByYear(string $table, int $idTahun): int
    {
        return (int) $this->db
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
            ->orderBy('id', 'DESC')
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    private function stepBlueprint(): array
    {
        $labels = [
            'precheck' => 'Validasi data Semester Ganjil',
            'create_year' => 'Membuat Semester Genap',
            'copy_classes' => 'Menyalin struktur Kelas',
            'copy_members' => 'Menyalin Anggota Kelas siswa Aktif',
            'copy_wali' => 'Menyalin Mapping Wali',
            'copy_schedule' => 'Menyalin Jadwal Guru',
            'close_history' => 'Menutup histori Aktif Ganjil',
            'open_history' => 'Membuat histori Aktif Genap',
            'deactivate_source' => 'Menonaktifkan Semester Ganjil',
            'activate_target' => 'Mengaktifkan Semester Genap',
            'verify' => 'Verifikasi akhir',
        ];

        $steps = [];
        foreach ($labels as $key => $label) {
            $steps[$key] = [
                'key' => $key,
                'label' => $label,
                'status' => 'pending',
                'count' => null,
                'expected' => null,
                'message' => null,
            ];
        }
        return $steps;
    }

    private function setExpectedCounts(array $steps, array $expected): array
    {
        $map = [
            'copy_classes' => 'kelas',
            'copy_members' => 'anggota',
            'copy_wali' => 'wali',
            'copy_schedule' => 'jadwal',
            'close_history' => 'histori',
            'open_history' => 'histori',
        ];

        foreach ($map as $stepKey => $countKey) {
            $steps[$stepKey]['expected'] = $expected[$countKey] ?? null;
        }
        return $steps;
    }

    private function markStep(
        array $steps,
        string $key,
        string $status,
        ?int $count = null,
        ?int $expected = null,
        ?string $message = null
    ): array {
        if (! isset($steps[$key])) {
            return $steps;
        }

        $steps[$key]['status'] = $status;
        if ($count !== null) {
            $steps[$key]['count'] = $count;
        }
        if ($expected !== null) {
            $steps[$key]['expected'] = $expected;
        }
        if ($message !== null) {
            $steps[$key]['message'] = $message;
        }
        return $steps;
    }

    private function rollbackSteps(array $steps, string $failedKey, string $message): array
    {
        foreach ($steps as $key => &$step) {
            if ($key === 'precheck') {
                continue;
            }

            if ($key === $failedKey) {
                $step['status'] = 'failed';
                $step['message'] = $message;
                continue;
            }

            if ($step['status'] === 'success') {
                $step['status'] = 'rolled_back';
                $step['message'] = 'Dibatalkan karena transaksi di-rollback.';
                continue;
            }

            if ($step['status'] === 'pending') {
                $step['status'] = 'skipped';
                $step['message'] = 'Tidak dijalankan karena langkah sebelumnya gagal.';
            }
        }
        unset($step);
        return $steps;
    }

    private function precheckFailure(
        array $steps,
        string $code,
        string $message
    ): array {
        $steps['precheck']['status'] = 'failed';
        $steps['precheck']['message'] = $message;

        foreach ($steps as $key => &$step) {
            if ($key !== 'precheck') {
                $step['status'] = 'skipped';
                $step['message'] = 'Tidak dijalankan karena validasi sumber gagal.';
            }
        }
        unset($step);

        return [
            'success' => false,
            'code' => $code,
            'message' => $message,
            'rolled_back' => false,
            'steps' => array_values($steps),
        ];
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

    private function logActivity(string $aksi, string $keterangan): void
    {
        $idUser = session()->get('user_id');

        $this->db->table('log_activity')->insert([
            'id_user' => $idUser ? (int) $idUser : null,
            'aksi' => $aksi,
            'modul' => 'Master Tahun Ajaran',
            'keterangan' => $keterangan,
            'waktu' => date('Y-m-d H:i:s'),
        ]);
    }
}
