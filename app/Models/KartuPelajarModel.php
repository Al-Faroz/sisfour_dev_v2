<?php

namespace App\Models;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

class KartuPelajarModel
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function getPaged(
        array $filter,
        ?array $allowedStudentIds,
        int $limit,
        int $offset
    ): array {
        return $this->baseBuilder($filter, $allowedStudentIds)
            ->orderBy('s.nama', 'ASC')
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();
    }

    public function countFiltered(
        array $filter,
        ?array $allowedStudentIds
    ): int {
        return $this->baseBuilder($filter, $allowedStudentIds)
            ->countAllResults();
    }

    public function getById(int $id): ?array
    {
        $row = $this->db->table('kartu_pelajar kp')
            ->select([
                'kp.id',
                'kp.id_siswa',
                'kp.nomor_kartu',
                'kp.kode_verifikasi',
                'kp.tanggal_terbit',
                'kp.status_aktif',
                's.nik',
                's.nisn',
                's.nama',
                's.jenis_kelamin',
                's.tempat_lahir',
                's.tanggal_lahir',
                's.alamat',
                's.foto',
                's.status_aktif AS status_siswa',
            ])
            ->join('siswa s', 's.id = kp.id_siswa')
            ->where('kp.id', $id)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    public function getByIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        if ($ids === []) {
            return [];
        }

        return $this->db->table('kartu_pelajar kp')
            ->select([
                'kp.id',
                'kp.id_siswa',
                'kp.nomor_kartu',
                'kp.kode_verifikasi',
                'kp.tanggal_terbit',
                'kp.status_aktif',
                's.nik',
                's.nisn',
                's.nama',
                's.jenis_kelamin',
                's.tempat_lahir',
                's.tanggal_lahir',
                's.alamat',
                's.foto',
                's.status_aktif AS status_siswa',
            ])
            ->join('siswa s', 's.id = kp.id_siswa')
            ->whereIn('kp.id', $ids)
            ->orderBy('s.nama', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getByVerificationCode(string $code): ?array
    {
        $row = $this->db->table('kartu_pelajar kp')
            ->select([
                'kp.id',
                'kp.id_siswa',
                'kp.nomor_kartu',
                'kp.kode_verifikasi',
                'kp.tanggal_terbit',
                'kp.status_aktif',
                's.nik',
                's.nisn',
                's.nama',
                's.status_aktif AS status_siswa',
            ])
            ->join('siswa s', 's.id = kp.id_siswa')
            ->where('kp.kode_verifikasi', $code)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    public function getActiveByStudent(int $idSiswa): ?array
    {
        $row = $this->db->table('kartu_pelajar')
            ->where('id_siswa', $idSiswa)
            ->where('status_aktif', 'Aktif')
            ->orderBy('id', 'DESC')
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    public function insert(array $data): int
    {
        $this->db->table('kartu_pelajar')->insert($data);

        return (int) $this->db->insertID();
    }

    public function getStudent(int $idSiswa): ?array
    {
        $row = $this->db->table('siswa')
            ->where('id', $idSiswa)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    public function getEligibleStudents(
        ?array $allowedStudentIds,
        ?int $idKelas = null
    ): array {
        $builder = $this->db->table('siswa s')
            ->select([
                's.id',
                's.nisn',
                's.nama',
                'k.id AS id_kelas',
                'k.nama_kelas',
            ])
            ->join(
                'anggota_kelas ak',
                'ak.id_siswa = s.id',
                'left'
            )
            ->join(
                'tahun_ajaran ta',
                'ta.id = ak.id_tahun AND ta.status_aktif = 1 AND ta.deleted_at IS NULL',
                'left',
                false
            )
            ->join(
                'kelas k',
                'k.id = ak.id_kelas AND k.deleted_at IS NULL',
                'left',
                false
            )
            ->join(
                'kartu_pelajar kp',
                "kp.id_siswa = s.id AND kp.status_aktif = 'Aktif'",
                'left',
                false
            )
            ->where('s.deleted_at', null)
            ->where('s.status_aktif', 'Aktif')
            ->where('kp.id', null);

        if (is_array($allowedStudentIds)) {
            if ($allowedStudentIds === []) {
                return [];
            }

            $builder->whereIn('s.id', $allowedStudentIds);
        }

        if ($idKelas !== null && $idKelas > 0) {
            $builder->where('k.id', $idKelas);
        }

        return $builder
            ->orderBy('k.tingkat', 'ASC')
            ->orderBy('k.nama_kelas', 'ASC')
            ->orderBy('s.nama', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getEligibleStudentIds(
        ?array $allowedStudentIds,
        ?int $idKelas = null
    ): array {
        return array_map(
            static fn(array $row): int => (int) $row['id'],
            $this->getEligibleStudents($allowedStudentIds, $idKelas)
        );
    }

    public function getActiveClassOptions(
        ?array $allowedStudentIds = null
    ): array {
        $builder = $this->db->table('kelas k')
            ->distinct()
            ->select([
                'k.id',
                'k.nama_kelas',
                'k.tingkat',
            ])
            ->join(
                'tahun_ajaran ta',
                'ta.id = k.id_tahun'
            )
            ->join(
                'anggota_kelas ak',
                'ak.id_kelas = k.id',
                'left'
            )
            ->where('ta.status_aktif', 1)
            ->where('ta.deleted_at', null)
            ->where('k.deleted_at', null);

        if (is_array($allowedStudentIds)) {
            if ($allowedStudentIds === []) {
                return [];
            }

            $builder->whereIn('ak.id_siswa', $allowedStudentIds);
        }

        return $builder
            ->orderBy('k.tingkat', 'ASC')
            ->orderBy('k.nama_kelas', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getCurrentClass(int $idSiswa): ?array
    {
        $row = $this->db->table('anggota_kelas ak')
            ->select([
                'k.id',
                'k.nama_kelas',
                'ta.nama_tahun',
                'ta.semester',
            ])
            ->join('kelas k', 'k.id = ak.id_kelas')
            ->join('tahun_ajaran ta', 'ta.id = ak.id_tahun')
            ->where('ak.id_siswa', $idSiswa)
            ->where('ta.status_aktif', 1)
            ->where('ta.deleted_at', null)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    public function getActiveCardsForClass(
        int $idKelas,
        ?array $allowedStudentIds
    ): array {
        $builder = $this->db->table('kartu_pelajar kp')
            ->select([
                'kp.id',
                'kp.id_siswa',
                'kp.nomor_kartu',
                'kp.kode_verifikasi',
                'kp.tanggal_terbit',
                'kp.status_aktif',
                's.nik',
                's.nisn',
                's.nama',
                's.jenis_kelamin',
                's.tempat_lahir',
                's.tanggal_lahir',
                's.alamat',
                's.foto',
                's.status_aktif AS status_siswa',
            ])
            ->join('siswa s', 's.id = kp.id_siswa')
            ->join('anggota_kelas ak', 'ak.id_siswa = s.id')
            ->join(
                'tahun_ajaran ta',
                'ta.id = ak.id_tahun AND ta.status_aktif = 1 AND ta.deleted_at IS NULL',
                'inner',
                false
            )
            ->where('ak.id_kelas', $idKelas)
            ->where('kp.status_aktif', 'Aktif')
            ->where('s.deleted_at', null);

        if (is_array($allowedStudentIds)) {
            if ($allowedStudentIds === []) {
                return [];
            }

            $builder->whereIn('s.id', $allowedStudentIds);
        }

        return $builder
            ->orderBy('s.nama', 'ASC')
            ->get()
            ->getResultArray();
    }

    private function baseBuilder(
        array $filter,
        ?array $allowedStudentIds
    ) {
        $builder = $this->db->table('kartu_pelajar kp')
            ->select([
                'kp.id',
                'kp.id_siswa',
                'kp.nomor_kartu',
                'kp.kode_verifikasi',
                'kp.tanggal_terbit',
                'kp.status_aktif',
                's.nisn',
                's.nama',
                's.status_aktif AS status_siswa',
                'k.id AS id_kelas',
                'k.nama_kelas',
            ])
            ->join('siswa s', 's.id = kp.id_siswa')
            ->join(
                'anggota_kelas ak',
                'ak.id_siswa = s.id',
                'left'
            )
            ->join(
                'tahun_ajaran ta',
                'ta.id = ak.id_tahun AND ta.status_aktif = 1 AND ta.deleted_at IS NULL',
                'left',
                false
            )
            ->join(
                'kelas k',
                'k.id = ak.id_kelas AND k.deleted_at IS NULL',
                'left',
                false
            );

        if (is_array($allowedStudentIds)) {
            if ($allowedStudentIds === []) {
                $builder->where('1 = 0', null, false);
            } else {
                $builder->whereIn('kp.id_siswa', $allowedStudentIds);
            }
        }

        if (!empty($filter['status'])) {
            $builder->where(
                'kp.status_aktif',
                (string) $filter['status']
            );
        }

        if (!empty($filter['id_kelas'])) {
            $builder->where(
                'k.id',
                (int) $filter['id_kelas']
            );
        }

        if (!empty($filter['search'])) {
            $search = trim((string) $filter['search']);

            $builder->groupStart()
                ->like('s.nama', $search)
                ->orLike('s.nisn', $search)
                ->orLike('kp.nomor_kartu', $search)
                ->groupEnd();
        }

        return $builder;
    }
}
