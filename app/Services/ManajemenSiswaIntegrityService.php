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
 */
class ManajemenSiswaIntegrityService extends ManajemenSiswaService
{
    public function __construct()
    {
        parent::__construct();
        $this->kelasService = new KelasLifecycleService();
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

    private function isNextAcademicYear(
        string $source,
        string $target
    ): bool {
        if (
            ! preg_match('/^(\d{4})\/(\d{4})$/', $source, $sourceMatch)
            || ! preg_match('/^(\d{4})\/(\d{4})$/', $target, $targetMatch)
        ) {
            return false;
        }

        $sourceEnd = (int) $sourceMatch[2];
        $targetStart = (int) $targetMatch[1];
        $targetEnd = (int) $targetMatch[2];

        return $targetStart === $sourceEnd
            && $targetEnd === $targetStart + 1;
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
