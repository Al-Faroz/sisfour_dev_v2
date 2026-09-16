<?php

namespace App\Services;

/**
 * Integrity guard untuk permanent delete Master Siswa dan read model Admin.
 *
 * Riwayat siswa, kartu, dan presensi legacy tidak seluruhnya memiliki FK.
 * Permanent delete hanya untuk data salah/unused yang belum mempunyai histori.
 * Master Siswa memakai konteks Tahun Ajaran terpilih untuk membership/kelas,
 * dengan Tahun Ajaran aktif sebagai default.
 */
class SiswaIntegrityService extends SiswaService
{
    public function getList(
        array $filter,
        int $userId,
        bool $deletedOnly = false
    ): array {
        if ($deletedOnly) {
            return parent::getList($filter, $userId, true);
        }

        $idTahun = $this->resolveFilterTahunId(
            (int) ($filter['id_tahun'] ?? 0)
        );

        $builder = $this->db
            ->table('siswa s')
            ->select(
                's.id, s.nik, s.nisn, s.nama, s.jenis_kelamin, s.tempat_lahir, ' .
                's.tanggal_lahir, s.alamat, s.no_telepon, s.kebutuhan_khusus, ' .
                's.disabilitas, s.nomor_kip_pip, s.nama_ayah_kandung, ' .
                's.nama_ibu_kandung, s.nama_wali, s.foto, s.status_aktif, ' .
                's.tanggal_mutasi, s.keterangan_mutasi, s.deleted_at, ' .
                's.created_at, s.updated_at'
            );

        if ($idTahun !== null) {
            $builder
                ->select(
                    'ak.id_kelas AS id_kelas_aktif, ' .
                    'k.nama_kelas AS nama_kelas_aktif'
                )
                ->join(
                    'anggota_kelas ak',
                    'ak.id_siswa = s.id AND ak.id_tahun = ' . $idTahun,
                    'left'
                )
                ->join(
                    'kelas k',
                    'k.id = ak.id_kelas AND k.deleted_at IS NULL',
                    'left'
                );
        } else {
            $builder->select(
                'NULL AS id_kelas_aktif, NULL AS nama_kelas_aktif',
                false
            );
        }

        $builder->where('s.deleted_at', null);

        if (! $this->canSeeAllStudents($userId)) {
            if (! $this->applyViewScope($builder, $userId, $idTahun)) {
                return [];
            }
        }

        $nama = trim((string) ($filter['nama'] ?? ''));
        $nik = trim((string) ($filter['nik'] ?? ''));
        $nisn = trim((string) ($filter['nisn'] ?? ''));
        $idKelas = (int) ($filter['id_kelas'] ?? 0);
        $status = trim((string) ($filter['status_aktif'] ?? ''));

        if ($nama !== '') {
            $builder->like('s.nama', $nama);
        }

        if ($nik !== '') {
            $builder->like('s.nik', $nik);
        }

        if ($nisn !== '') {
            $builder->like('s.nisn', $nisn);
        }

        if ($idKelas > 0 && $idTahun !== null) {
            $builder->where('ak.id_kelas', $idKelas);
        }

        if (in_array($status, ['Aktif', 'Lulus', 'Pindah', 'Keluar'], true)) {
            $builder->where('s.status_aktif', $status);
        }

        return $builder
            ->orderBy('s.nama', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Dipakai Template Import. Import selalu mengikuti tahun ajaran aktif.
     */
    public function getKelasOptions(int $userId): array
    {
        $idTahun = $this->getIdTahunAktif();

        if ($idTahun === null) {
            return [];
        }

        return $this->getKelasOptionsForYear($userId, $idTahun);
    }

    /**
     * Seluruh kelas yang dapat dipakai oleh filter Master Siswa.
     * id_tahun disertakan agar browser dapat mengganti dropdown Kelas ketika
     * Tahun Ajaran berubah tanpa menambah route baru.
     */
    public function getFilterKelasOptions(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $builder = $this->db
            ->table('kelas k')
            ->select(
                'k.id, k.nama_kelas, k.tingkat, k.rombel, k.id_tahun, ' .
                'ta.nama_tahun, ta.semester, ta.status_aktif'
            )
            ->join('tahun_ajaran ta', 'ta.id = k.id_tahun')
            ->where('k.deleted_at', null)
            ->where('ta.deleted_at', null);

        if ($this->canSeeAllStudents($userId)) {
            return $builder
                ->orderBy('ta.nama_tahun', 'DESC')
                ->orderBy('ta.semester', 'ASC')
                ->orderBy('k.tingkat', 'ASC')
                ->orderBy('k.rombel', 'ASC')
                ->get()
                ->getResultArray();
        }

        $allowed = $this->allowedClassIdsAcrossYears($userId);

        if ($allowed === []) {
            return [];
        }

        return $builder
            ->whereIn('k.id', $allowed)
            ->orderBy('ta.nama_tahun', 'DESC')
            ->orderBy('ta.semester', 'ASC')
            ->orderBy('k.tingkat', 'ASC')
            ->orderBy('k.rombel', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getTahunOptions(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $scopes = $this->authService->getPermissionScopes(
            'master_siswa.view',
            $userId
        );

        if (
            ! $this->canSeeAllStudents($userId)
            && $scopes === []
        ) {
            return [];
        }

        return $this->db
            ->table('tahun_ajaran')
            ->select('id, nama_tahun, semester, status_aktif')
            ->where('deleted_at', null)
            ->orderBy('nama_tahun', 'DESC')
            ->orderBy(
                "FIELD(semester,'Ganjil','Genap')",
                '',
                false
            )
            ->get()
            ->getResultArray();
    }

    public function getActiveTahunId(): ?int
    {
        return $this->getIdTahunAktif();
    }

    public function forceDelete(int $id): array
    {
        if (! $this->canManageIntegrity()) {
            return [
                'success' => false,
                'code' => 'FORBIDDEN',
                'message' => 'Anda tidak memiliki hak mengelola Master Siswa.',
            ];
        }

        $dependencies = $this->dependencies($id);

        if ($dependencies !== []) {
            return [
                'success' => false,
                'code' => 'DATA_IN_USE',
                'message' => 'Hapus permanen ditolak karena Siswa masih memiliki histori/data terkait: '
                    . implode(', ', $dependencies)
                    . '. Pertahankan data di Recycle Bin agar histori akademik tidak menjadi yatim.',
            ];
        }

        return parent::forceDelete($id);
    }

    private function getKelasOptionsForYear(
        int $userId,
        int $idTahun
    ): array {
        $builder = $this->db
            ->table('kelas')
            ->select('id, nama_kelas, tingkat, rombel, id_tahun')
            ->where('id_tahun', $idTahun)
            ->where('deleted_at', null);

        if ($this->canSeeAllStudents($userId)) {
            return $builder
                ->orderBy('tingkat', 'ASC')
                ->orderBy('rombel', 'ASC')
                ->get()
                ->getResultArray();
        }

        $scopes = $this->authService->getPermissionScopes(
            'master_siswa.view',
            $userId
        );
        $allowed = [];

        if (in_array('KELAS_DIAMPU', $scopes, true)) {
            $allowed = array_merge(
                $allowed,
                $this->authService->getKelasDiampu(
                    $this->getIdGuruUser($userId),
                    $idTahun
                )
            );
        }

        if (in_array('DIRI_SENDIRI', $scopes, true)) {
            $idSiswa = $this->getIdSiswaUser($userId);

            if ($idSiswa > 0) {
                $anggota = $this->db
                    ->table('anggota_kelas')
                    ->select('id_kelas')
                    ->where('id_siswa', $idSiswa)
                    ->where('id_tahun', $idTahun)
                    ->get()
                    ->getRowArray();

                if ($anggota !== null) {
                    $allowed[] = (int) $anggota['id_kelas'];
                }
            }
        }

        $allowed = array_values(array_unique(array_filter(
            array_map('intval', $allowed)
        )));

        if ($allowed === []) {
            return [];
        }

        return $builder
            ->whereIn('id', $allowed)
            ->orderBy('tingkat', 'ASC')
            ->orderBy('rombel', 'ASC')
            ->get()
            ->getResultArray();
    }

    private function allowedClassIdsAcrossYears(int $userId): array
    {
        $scopes = $this->authService->getPermissionScopes(
            'master_siswa.view',
            $userId
        );
        $allowed = [];

        if (in_array('KELAS_DIAMPU', $scopes, true)) {
            $idGuru = $this->getIdGuruUser($userId);

            if ($idGuru > 0) {
                $years = $this->db
                    ->table('tahun_ajaran')
                    ->select('id')
                    ->where('deleted_at', null)
                    ->get()
                    ->getResultArray();

                foreach ($years as $year) {
                    $allowed = array_merge(
                        $allowed,
                        $this->authService->getKelasDiampu(
                            $idGuru,
                            (int) $year['id']
                        )
                    );
                }
            }
        }

        if (in_array('DIRI_SENDIRI', $scopes, true)) {
            $idSiswa = $this->getIdSiswaUser($userId);

            if ($idSiswa > 0) {
                $rows = $this->db
                    ->table('anggota_kelas')
                    ->select('id_kelas')
                    ->where('id_siswa', $idSiswa)
                    ->get()
                    ->getResultArray();

                foreach ($rows as $row) {
                    $allowed[] = (int) $row['id_kelas'];
                }
            }
        }

        return array_values(array_unique(array_filter(array_map(
            'intval',
            $allowed
        ))));
    }

    private function resolveFilterTahunId(int $requested): ?int
    {
        if ($requested > 0) {
            $exists = $this->db
                ->table('tahun_ajaran')
                ->where('id', $requested)
                ->where('deleted_at', null)
                ->countAllResults() > 0;

            if ($exists) {
                return $requested;
            }
        }

        return $this->getIdTahunAktif();
    }

    private function canSeeAllStudents(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        if (
            $this->authService->resolveScope(
                'master_siswa.manage',
                $userId
            ) === 'SEMUA'
        ) {
            return true;
        }

        return in_array(
            'SEMUA',
            $this->authService->getPermissionScopes(
                'master_siswa.view',
                $userId
            ),
            true
        );
    }

    /**
     * @return string[]
     */
    private function dependencies(int $id): array
    {
        $checks = [
            ['anggota_kelas', 'id_siswa', 'keanggotaan kelas'],
            ['riwayat_siswa', 'id_siswa', 'riwayat siswa'],
            ['presensi', 'id_siswa', 'presensi siswa'],
            ['presensi_mengajar_siswa', 'id_siswa', 'catatan jurnal pembelajaran'],
            ['kartu_pelajar', 'id_siswa', 'kartu pelajar'],
            ['catatan_kasus', 'id_siswa', 'catatan kasus BK'],
            ['catatan_prestasi', 'id_siswa', 'catatan prestasi'],
        ];

        $found = [];

        foreach ($checks as [$table, $column, $label]) {
            if (! $this->db->tableExists($table)) {
                continue;
            }

            if (
                $this->db
                    ->table($table)
                    ->where($column, $id)
                    ->countAllResults() > 0
            ) {
                $found[] = $label;
            }
        }

        return $found;
    }

    private function canManageIntegrity(): bool
    {
        $userId = (int) (session()->get('user_id') ?? 0);

        return $userId > 0
            && $this->authService->resolveScope(
                'master_siswa.manage',
                $userId
            ) === 'SEMUA';
    }
}
