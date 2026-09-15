<?php

namespace App\Services;

/**
 * F14 integration layer untuk lifecycle Manajemen Siswa.
 *
 * - Kelulusan dan mutasi memakai KelasLifecycleService agar membership terminal,
 *   histori, status siswa, dan kartu diproses atomically.
 * - Kenaikan kelas dipisahkan tegas dari pergantian semester: hanya Genap tahun
 *   aktif -> Ganjil tahun pelajaran berikutnya dengan tingkat 7->8 atau 8->9.
 * - Kenaikan bersifat idempotent pada level siswa: siswa yang sudah mempunyai
 *   membership atau histori Aktif terbuka pada tahun tujuan tidak boleh diproses
 *   ulang sehingga histori aktif ganda tidak dapat dibuat lewat workflow normal.
 * - Daftar kelas Kenaikan membawa progress Belum/Sebagian/Selesai agar operator
 *   dapat melihat pekerjaan yang sudah dilakukan sebelum mengaktifkan tahun baru.
 */
class ManajemenSiswaIntegrityService extends ManajemenSiswaService
{
    public function __construct()
    {
        parent::__construct();
        $this->kelasService = new KelasLifecycleService();
    }

    public function getKenaikanSourceClasses(int $actorUserId): array
    {
        if (
            $actorUserId <= 0
            || $this->authService->resolveScope(
                'master_siswa.manage',
                $actorUserId
            ) !== 'SEMUA'
        ) {
            return [];
        }

        $active = $this->db
            ->table('tahun_ajaran')
            ->select('id, nama_tahun, semester, status_aktif')
            ->where('status_aktif', 1)
            ->where('deleted_at', null)
            ->orderBy('id', 'DESC')
            ->get()
            ->getRowArray();

        if ($active === null) {
            return [];
        }

        $source = $active;
        $sourceIsActive = (string) $active['semester'] === 'Genap';

        // Setelah Ganjil tahun baru diaktifkan, tampilkan ringkasan Genap tahun
        // sebelumnya secara readonly agar operator tetap dapat melihat progress.
        if ((string) $active['semester'] === 'Ganjil') {
            $previousName = $this->previousAcademicYearName(
                (string) $active['nama_tahun']
            );

            if ($previousName === null) {
                return [];
            }

            $source = $this->db
                ->table('tahun_ajaran')
                ->select('id, nama_tahun, semester, status_aktif')
                ->where('nama_tahun', $previousName)
                ->where('semester', 'Genap')
                ->where('deleted_at', null)
                ->orderBy('id', 'DESC')
                ->get()
                ->getRowArray();

            if ($source === null) {
                return [];
            }

            $sourceIsActive = false;
        } elseif ((string) $active['semester'] !== 'Genap') {
            return [];
        }

        $targetName = $this->nextAcademicYearName(
            (string) $source['nama_tahun']
        );
        $target = null;

        if ($targetName !== null) {
            $target = $this->db
                ->table('tahun_ajaran')
                ->select('id, nama_tahun, semester, status_aktif')
                ->where('nama_tahun', $targetName)
                ->where('semester', 'Ganjil')
                ->where('deleted_at', null)
                ->orderBy('id', 'DESC')
                ->get()
                ->getRowArray();
        }

        $rows = $this->db
            ->table('kelas k')
            ->select(
                'k.id, k.nama_kelas, k.tingkat, k.rombel, ' .
                'COUNT(DISTINCT ak.id_siswa) AS jumlah_siswa'
            )
            ->join(
                'anggota_kelas ak',
                'ak.id_kelas = k.id AND ak.id_tahun = k.id_tahun',
                'left'
            )
            ->where('k.id_tahun', (int) $source['id'])
            ->where('k.deleted_at', null)
            ->whereIn('k.tingkat', ['7', '8'])
            ->groupBy(['k.id', 'k.nama_kelas', 'k.tingkat', 'k.rombel'])
            ->orderBy('k.tingkat', 'ASC')
            ->orderBy('k.rombel', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($rows as &$row) {
            $total = (int) ($row['jumlah_siswa'] ?? 0);
            $processed = 0;

            if ($target !== null && $total > 0) {
                $processedRow = $this->db
                    ->table('riwayat_siswa rs')
                    ->select('COUNT(DISTINCT rs.id_siswa) AS total', false)
                    ->join(
                        'anggota_kelas aks',
                        'aks.id_siswa = rs.id_siswa'
                        . ' AND aks.id_kelas = ' . (int) $row['id']
                        . ' AND aks.id_tahun = ' . (int) $source['id']
                    )
                    ->where('rs.id_tahun', (int) $target['id'])
                    ->where('rs.status', 'Aktif')
                    ->like(
                        'rs.keterangan',
                        'Kenaikan kelas dari ' . (string) $row['nama_kelas'],
                        'after'
                    )
                    ->get()
                    ->getRowArray();

                $processed = min(
                    $total,
                    (int) ($processedRow['total'] ?? 0)
                );
            }

            if ($total > 0 && $processed >= $total) {
                $progressStatus = 'Selesai';
            } elseif ($processed > 0) {
                $progressStatus = 'Sebagian';
            } else {
                $progressStatus = 'Belum Diproses';
            }

            $row['jumlah_dinaikkan'] = $processed;
            $row['jumlah_sisa'] = max(0, $total - $processed);
            $row['progress_status'] = $progressStatus;
            $row['periode_sumber'] = (string) $source['nama_tahun']
                . ' - ' . (string) $source['semester'];
            $row['periode_tujuan'] = $target !== null
                ? (string) $target['nama_tahun'] . ' - ' . (string) $target['semester']
                : ($targetName !== null ? $targetName . ' - Ganjil' : '-');
            $row['target_year_exists'] = $target !== null;
            $row['source_is_active'] = $sourceIsActive;
            $row['can_process'] = $sourceIsActive
                && $target !== null
                && $total > 0
                && $processed < $total;
        }
        unset($row);

        return $rows;
    }

    public function getProcessData(int $actorUserId, int $idKelas): array
    {
        if (
            $actorUserId <= 0
            || $this->authService->resolveScope(
                'master_siswa.manage',
                $actorUserId
            ) !== 'SEMUA'
        ) {
            return $this->forbiddenIntegrity();
        }

        $source = $this->db
            ->table('kelas k')
            ->select(
                'k.id, k.tingkat, k.id_tahun, k.nama_kelas, ' .
                'ta.nama_tahun, ta.semester, ta.status_aktif'
            )
            ->join('tahun_ajaran ta', 'ta.id = k.id_tahun')
            ->where('k.id', $idKelas)
            ->where('k.deleted_at', null)
            ->where('ta.deleted_at', null)
            ->get()
            ->getRowArray();

        if ($source === null) {
            return [
                'success' => false,
                'message' => 'Kelas asal tidak ditemukan.',
            ];
        }

        if (
            (int) $source['status_aktif'] !== 1
            || (string) $source['semester'] !== 'Genap'
        ) {
            return [
                'success' => false,
                'code' => 'PROMOTION_SOURCE_CLOSED',
                'message' => 'Periode sumber kenaikan sudah ditutup. Ringkasan tetap dapat dilihat, tetapi proses baru tidak boleh dijalankan.',
            ];
        }

        $result = parent::getProcessData($actorUserId, $idKelas);

        if (empty($result['success'])) {
            return $result;
        }

        $targetName = $this->nextAcademicYearName(
            (string) $source['nama_tahun']
        );
        $targetYear = null;

        if ($targetName !== null) {
            $targetYear = $this->db
                ->table('tahun_ajaran')
                ->select('id, nama_tahun, semester, status_aktif')
                ->where('nama_tahun', $targetName)
                ->where('semester', 'Ganjil')
                ->where('deleted_at', null)
                ->orderBy('id', 'DESC')
                ->get()
                ->getRowArray();
        }

        $targetLevel = (int) $source['tingkat'] + 1;

        $result['target_kelas'] = $targetYear === null
            ? []
            : array_values(array_filter(
                (array) ($result['target_kelas'] ?? []),
                static fn (array $kelas): bool =>
                    (int) ($kelas['id_tahun'] ?? 0) === (int) $targetYear['id']
                    && (int) ($kelas['tingkat'] ?? 0) === $targetLevel
            ));

        foreach ($result['siswa'] as &$siswa) {
            $idSiswa = (int) ($siswa['id_siswa'] ?? 0);
            $alreadyPromoted = false;

            if ($idSiswa > 0 && $targetYear !== null) {
                $targetMembership = $this->db
                    ->table('anggota_kelas')
                    ->where('id_siswa', $idSiswa)
                    ->where('id_tahun', (int) $targetYear['id'])
                    ->countAllResults() > 0;

                $targetHistory = $this->db
                    ->table('riwayat_siswa')
                    ->where('id_siswa', $idSiswa)
                    ->where('id_tahun', (int) $targetYear['id'])
                    ->where('status', 'Aktif')
                    ->where('tanggal_selesai', null)
                    ->countAllResults() > 0;

                $alreadyPromoted = $targetMembership || $targetHistory;
            }

            $siswa['sudah_dinaikkan'] = $alreadyPromoted;
        }
        unset($siswa);

        $result['target_tahun'] = $targetYear;

        return $result;
    }

    public function naikKelas(
        int $actorUserId,
        int $idKelasAsal,
        int $idKelasTujuan,
        int $idTahunBaru,
        array $selected
    ): array {
        if (
            $actorUserId <= 0
            || $this->authService->resolveScope(
                'master_siswa.manage',
                $actorUserId
            ) !== 'SEMUA'
        ) {
            return $this->forbiddenIntegrity();
        }

        $source = $this->db
            ->table('kelas k')
            ->select(
                'k.id, k.tingkat, k.id_tahun, k.nama_kelas, ' .
                'ta.nama_tahun, ta.semester, ta.status_aktif'
            )
            ->join('tahun_ajaran ta', 'ta.id = k.id_tahun')
            ->where('k.id', $idKelasAsal)
            ->where('k.deleted_at', null)
            ->where('ta.deleted_at', null)
            ->get()
            ->getRowArray();

        $target = $this->db
            ->table('kelas k')
            ->select(
                'k.id, k.tingkat, k.id_tahun, k.nama_kelas, ' .
                'ta.nama_tahun, ta.semester, ta.status_aktif'
            )
            ->join('tahun_ajaran ta', 'ta.id = k.id_tahun')
            ->where('k.id', $idKelasTujuan)
            ->where('k.deleted_at', null)
            ->where('ta.deleted_at', null)
            ->get()
            ->getRowArray();

        if ($source === null || $target === null) {
            return [
                'success' => false,
                'message' => 'Kelas asal atau kelas tujuan tidak ditemukan.',
            ];
        }

        if ((int) $target['id_tahun'] !== $idTahunBaru) {
            return [
                'success' => false,
                'message' => 'Kelas tujuan tidak berada pada tahun ajaran tujuan.',
            ];
        }

        if ((int) $source['status_aktif'] !== 1) {
            return [
                'success' => false,
                'message' => 'Kenaikan kelas hanya dapat diproses dari tahun ajaran/semester yang sedang aktif.',
            ];
        }

        if ((string) $source['nama_tahun'] === (string) $target['nama_tahun']) {
            return [
                'success' => false,
                'code' => 'USE_SEMESTER_TRANSITION',
                'message' => 'Ganjil ke Genap pada tahun pelajaran yang sama bukan Kenaikan Kelas. Gunakan workflow Siapkan Semester Genap pada Master Tahun Ajaran.',
            ];
        }

        if (
            (string) $source['semester'] !== 'Genap'
            || (string) $target['semester'] !== 'Ganjil'
        ) {
            return [
                'success' => false,
                'code' => 'INVALID_PROMOTION_PERIOD',
                'message' => 'Kenaikan Kelas hanya boleh dari Semester Genap ke Semester Ganjil tahun pelajaran berikutnya.',
            ];
        }

        if (! $this->isNextAcademicYear(
            (string) $source['nama_tahun'],
            (string) $target['nama_tahun']
        )) {
            return [
                'success' => false,
                'code' => 'INVALID_PROMOTION_YEAR',
                'message' => 'Tahun pelajaran tujuan Kenaikan Kelas harus tepat satu periode setelah tahun pelajaran asal.',
            ];
        }

        $sourceLevel = (int) $source['tingkat'];
        $targetLevel = (int) $target['tingkat'];

        if (
            ! in_array($sourceLevel, [7, 8], true)
            || $targetLevel !== $sourceLevel + 1
        ) {
            return [
                'success' => false,
                'code' => 'INVALID_PROMOTION_LEVEL',
                'message' => 'Kenaikan Kelas hanya mendukung tingkat 7 ke 8 atau tingkat 8 ke 9. Tingkat 9 diproses melalui Kelulusan.',
            ];
        }

        $selectedIds = array_values(array_unique(array_filter(
            array_map('intval', $selected),
            static fn (int $id): bool => $id > 0
        )));

        if ($selectedIds === []) {
            return [
                'success' => false,
                'message' => 'Tidak ada siswa yang dipilih untuk dinaikkan.',
            ];
        }

        foreach ($selectedIds as $idSiswa) {
            $siswa = $this->db
                ->table('siswa')
                ->select('id, nama, nisn, status_aktif')
                ->where('id', $idSiswa)
                ->where('deleted_at', null)
                ->get()
                ->getRowArray();

            if ($siswa === null || (string) $siswa['status_aktif'] !== 'Aktif') {
                return [
                    'success' => false,
                    'code' => 'INVALID_STUDENT',
                    'message' => "Siswa ID {$idSiswa} tidak ditemukan atau tidak berstatus Aktif.",
                ];
            }

            $sourceMembershipCount = $this->db
                ->table('anggota_kelas')
                ->where('id_siswa', $idSiswa)
                ->where('id_kelas', $idKelasAsal)
                ->where('id_tahun', (int) $source['id_tahun'])
                ->countAllResults();

            if ($sourceMembershipCount !== 1) {
                return [
                    'success' => false,
                    'code' => 'INVALID_SOURCE_MEMBERSHIP',
                    'message' => sprintf(
                        '%s tidak memiliki membership tunggal yang valid pada kelas asal.',
                        (string) $siswa['nama']
                    ),
                ];
            }

            $targetMembershipCount = $this->db
                ->table('anggota_kelas')
                ->where('id_siswa', $idSiswa)
                ->where('id_tahun', $idTahunBaru)
                ->countAllResults();

            $targetOpenHistoryCount = $this->db
                ->table('riwayat_siswa')
                ->where('id_siswa', $idSiswa)
                ->where('id_tahun', $idTahunBaru)
                ->where('status', 'Aktif')
                ->where('tanggal_selesai', null)
                ->countAllResults();

            if ($targetMembershipCount > 0 || $targetOpenHistoryCount > 0) {
                return [
                    'success' => false,
                    'code' => 'ALREADY_PROMOTED',
                    'message' => sprintf(
                        '%s sudah memiliki membership/histori aktif pada tahun pelajaran tujuan. Siswa tidak diproses ulang.',
                        (string) $siswa['nama']
                    ),
                ];
            }

            $sourceOpenHistoryCount = $this->db
                ->table('riwayat_siswa')
                ->where('id_siswa', $idSiswa)
                ->where('id_tahun', (int) $source['id_tahun'])
                ->where('status', 'Aktif')
                ->where('tanggal_selesai', null)
                ->countAllResults();

            if ($sourceOpenHistoryCount !== 1) {
                return [
                    'success' => false,
                    'code' => 'INVALID_SOURCE_HISTORY',
                    'message' => sprintf(
                        'Histori aktif sumber %s tidak valid. Periksa integritas riwayat sebelum menjalankan kenaikan kelas.',
                        (string) $siswa['nama']
                    ),
                ];
            }
        }

        return parent::naikKelas(
            $actorUserId,
            $idKelasAsal,
            $idKelasTujuan,
            $idTahunBaru,
            $selectedIds
        );
    }

    public function mutasi(
        int $actorUserId,
        int $idSiswa,
        string $status,
        string $keterangan
    ): array {
        if ($actorUserId <= 0) {
            return $this->forbiddenIntegrity();
        }

        return $this->kelasService->mutasiSiswa(
            $idSiswa,
            $status,
            $keterangan
        );
    }

    private function nextAcademicYearName(string $source): ?string
    {
        if (! preg_match('/^(\d{4})\/(\d{4})$/', $source, $match)) {
            return null;
        }

        $nextStart = (int) $match[2];

        return sprintf('%04d/%04d', $nextStart, $nextStart + 1);
    }

    private function previousAcademicYearName(string $current): ?string
    {
        if (! preg_match('/^(\d{4})\/(\d{4})$/', $current, $match)) {
            return null;
        }

        $previousEnd = (int) $match[1];

        return sprintf('%04d/%04d', $previousEnd - 1, $previousEnd);
    }

    private function isNextAcademicYear(
        string $source,
        string $target
    ): bool {
        return $this->nextAcademicYearName($source) === $target;
    }

    private function forbiddenIntegrity(): array
    {
        return [
            'success' => false,
            'code' => 'FORBIDDEN',
            'message' => 'Anda tidak memiliki hak untuk mengelola siswa.',
        ];
    }
}
