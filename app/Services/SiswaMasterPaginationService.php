<?php

namespace App\Services;

/**
 * Year-aware read service khusus Master Siswa.
 *
 * Kontrak:
 * - id_tahun menentukan konteks membership/kelas yang ditampilkan;
 * - Admin/Operator dengan master_siswa.manage = SEMUA dapat melihat semua siswa;
 * - scope Wali/Siswa tetap dibatasi pada kelas/identitas yang diizinkan;
 * - pagination tetap dilakukan di DB, bukan setelah seluruh dataset dimuat.
 */
class SiswaMasterPaginationService extends MasterPaginationService
{
    public function pageSiswa(
        array $filter,
        int $userId,
        int $limit,
        int $offset,
        bool $deletedOnly = false
    ): array {
        if ($userId <= 0) {
            return $this->emptySiswaPage($limit);
        }

        $canManageAll = $this->authService->resolveScope(
            'master_siswa.manage',
            $userId
        ) === 'SEMUA';

        if ($deletedOnly && ! $canManageAll) {
            return $this->emptySiswaPage($limit);
        }

        $idTahun = $this->resolveTahunId((int) ($filter['id_tahun'] ?? 0));

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

        if ($deletedOnly) {
            $builder->where('s.deleted_at IS NOT NULL', null, false);
        } else {
            $builder->where('s.deleted_at', null);

            if (! $canManageAll && ! $this->applySiswaScope(
                $builder,
                $userId,
                $idTahun
            )) {
                return $this->emptySiswaPage($limit);
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

        $limit = in_array($limit, [25, 50, 100], true) ? $limit : 25;
        $offset = max(0, $offset);

        $countBuilder = clone $builder;
        $total = (int) $countBuilder->countAllResults();

        if ($total > 0 && $offset >= $total) {
            $offset = (int) (floor(($total - 1) / $limit) * $limit);
        }

        $rows = $builder
            ->orderBy('s.nama', 'ASC')
            ->orderBy('s.id', 'ASC')
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();

        return $this->pagePayloadSiswa($rows, $total, $limit, $offset);
    }

    private function resolveTahunId(int $requested): ?int
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

        $row = $this->db
            ->table('tahun_ajaran')
            ->select('id')
            ->where('status_aktif', 1)
            ->where('deleted_at', null)
            ->orderBy('id', 'DESC')
            ->get()
            ->getRowArray();

        return $row !== null ? (int) $row['id'] : null;
    }

    private function applySiswaScope(
        $builder,
        int $userId,
        ?int $idTahun
    ): bool {
        $scopes = $this->authService->getPermissionScopes(
            'master_siswa.view',
            $userId
        );

        if (in_array('SEMUA', $scopes, true)) {
            return true;
        }

        $kelas = [];
        $idSiswa = 0;

        if (
            in_array('KELAS_DIAMPU', $scopes, true)
            && $idTahun !== null
        ) {
            $idGuru = $this->identityId($userId, 'id_guru');

            if ($idGuru > 0) {
                $kelas = $this->authService->getKelasDiampu(
                    $idGuru,
                    $idTahun
                );
            }
        }

        if (in_array('DIRI_SENDIRI', $scopes, true)) {
            $idSiswa = $this->identityId($userId, 'id_siswa');
        }

        if ($kelas !== [] && $idSiswa > 0) {
            $builder
                ->groupStart()
                    ->whereIn('ak.id_kelas', $kelas)
                    ->orWhere('s.id', $idSiswa)
                ->groupEnd();

            return true;
        }

        if ($kelas !== []) {
            $builder->whereIn('ak.id_kelas', $kelas);
            return true;
        }

        if ($idSiswa > 0) {
            $builder->where('s.id', $idSiswa);
            return true;
        }

        return false;
    }

    private function identityId(int $userId, string $column): int
    {
        if (! in_array($column, ['id_guru', 'id_siswa'], true)) {
            return 0;
        }

        $row = $this->db
            ->table('users')
            ->select($column)
            ->where('id', $userId)
            ->where('status_aktif', 1)
            ->get()
            ->getRowArray();

        return isset($row[$column]) ? (int) $row[$column] : 0;
    }

    private function emptySiswaPage(int $limit): array
    {
        $limit = in_array($limit, [25, 50, 100], true) ? $limit : 25;

        return $this->pagePayloadSiswa([], 0, $limit, 0);
    }

    private function pagePayloadSiswa(
        array $rows,
        int $total,
        int $limit,
        int $offset
    ): array {
        return [
            'rows' => $rows,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'page' => (int) floor($offset / $limit) + 1,
            'total_pages' => max(1, (int) ceil($total / $limit)),
            'has_previous' => $offset > 0,
            'has_next' => $offset + $limit < $total,
        ];
    }
}
