<?php

namespace App\Models;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

class UksCkgModel
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function getPaged(array $filter, ?array $allowedIds, int $limit, int $offset): array
    {
        return $this->baseBuilder($filter, $allowedIds)
            ->orderBy('c.tanggal', 'DESC')
            ->orderBy('c.id', 'DESC')
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();
    }

    public function countFiltered(array $filter, ?array $allowedIds): int
    {
        return $this->baseBuilder($filter, $allowedIds)->countAllResults();
    }

    public function getForExport(array $filter, ?array $allowedIds, int $limit): array
    {
        return $this->baseBuilder($filter, $allowedIds)
            ->orderBy('c.tanggal', 'ASC')
            ->orderBy('s.nama', 'ASC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    public function getById(int $id, bool $withDeleted = false): ?array
    {
        $builder = $this->db
            ->table('uks_ckg c')
            ->select('c.*, s.nisn, s.nama AS nama_siswa, k.nama_kelas, ta.nama_tahun, ta.semester')
            ->join('siswa s', 's.id = c.id_siswa')
            ->join('kelas k', 'k.id = c.id_kelas')
            ->join('tahun_ajaran ta', 'ta.id = c.id_tahun')
            ->where('c.id', $id);

        if (! $withDeleted) {
            $builder->where('c.deleted_at', null);
        }

        return $builder->get()->getRowArray() ?: null;
    }

    public function findActiveDuplicate(int $idSiswa, string $tanggal, ?int $exceptId = null): ?array
    {
        $builder = $this->db
            ->table('uks_ckg')
            ->where('id_siswa', $idSiswa)
            ->where('tanggal', $tanggal)
            ->where('deleted_at', null);

        if ($exceptId !== null) {
            $builder->where('id !=', $exceptId);
        }

        return $builder->get()->getRowArray() ?: null;
    }

    public function insert(array $data): int
    {
        $this->db->table('uks_ckg')->insert($data);
        return (int) $this->db->insertID();
    }

    public function update(int $id, array $data): bool
    {
        return (bool) $this->db->table('uks_ckg')->where('id', $id)->update($data);
    }

    public function softDelete(int $id, int $userId): bool
    {
        return (bool) $this->db
            ->table('uks_ckg')
            ->where('id', $id)
            ->where('deleted_at', null)
            ->update([
                'deleted_at' => date('Y-m-d H:i:s'),
                'updated_by' => $userId,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }

    private function baseBuilder(array $filter, ?array $allowedIds)
    {
        $builder = $this->db
            ->table('uks_ckg c')
            ->select([
                'c.*',
                's.nisn',
                's.nama AS nama_siswa',
                'k.nama_kelas',
                'ta.nama_tahun',
                'ta.semester',
            ])
            ->join('siswa s', 's.id = c.id_siswa')
            ->join('kelas k', 'k.id = c.id_kelas')
            ->join('tahun_ajaran ta', 'ta.id = c.id_tahun')
            ->where('c.deleted_at', null);

        if (is_array($allowedIds)) {
            if ($allowedIds === []) {
                $builder->where('1 = 0', null, false);
            } else {
                $builder->whereIn('c.id_siswa', $allowedIds);
            }
        }

        if (! empty($filter['id_tahun'])) {
            $builder->where('c.id_tahun', (int) $filter['id_tahun']);
        }
        if (! empty($filter['id_kelas'])) {
            $builder->where('c.id_kelas', (int) $filter['id_kelas']);
        }
        if (! empty($filter['tanggal_mulai'])) {
            $builder->where('c.tanggal >=', (string) $filter['tanggal_mulai']);
        }
        if (! empty($filter['tanggal_selesai'])) {
            $builder->where('c.tanggal <=', (string) $filter['tanggal_selesai']);
        }
        if (! empty($filter['search'])) {
            $q = trim((string) $filter['search']);
            $builder->groupStart()
                ->like('s.nama', $q)
                ->orLike('s.nisn', $q)
                ->orLike('k.nama_kelas', $q)
                ->groupEnd();
        }

        return $builder;
    }
}
