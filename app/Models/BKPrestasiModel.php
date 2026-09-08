<?php

namespace App\Models;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

class BKPrestasiModel
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
            ->orderBy('cp.tanggal', 'DESC')
            ->orderBy('cp.id', 'DESC')
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();
    }

    public function countFiltered(array $filter, ?array $allowedStudentIds): int
    {
        return $this->baseBuilder($filter, $allowedStudentIds)
            ->countAllResults();
    }

    public function getForExport(
        array $filter,
        ?array $allowedStudentIds,
        int $maxRows
    ): array {
        return $this->baseBuilder($filter, $allowedStudentIds)
            ->orderBy('cp.tanggal', 'ASC')
            ->orderBy('s.nama', 'ASC')
            ->limit($maxRows)
            ->get()
            ->getResultArray();
    }

    public function getById(int $id): ?array
    {
        $row = $this->db
            ->table('catatan_prestasi')
            ->where('id', $id)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    public function insert(array $data): int
    {
        $this->db->table('catatan_prestasi')->insert($data);

        return (int) $this->db->insertID();
    }

    public function update(int $id, array $data): bool
    {
        return (bool) $this->db
            ->table('catatan_prestasi')
            ->where('id', $id)
            ->update($data);
    }

    public function delete(int $id): bool
    {
        return (bool) $this->db
            ->table('catatan_prestasi')
            ->where('id', $id)
            ->delete();
    }

    private function baseBuilder(array $filter, ?array $allowedStudentIds)
    {
        $builder = $this->db
            ->table('catatan_prestasi cp')
            ->select([
                'cp.id',
                'cp.id_siswa',
                'cp.nama_prestasi',
                'cp.tingkat',
                'cp.tanggal',
                'cp.penyelenggara',
                'cp.keterangan',
                'cp.created_at',
                's.nisn',
                's.nama AS nama_siswa',
            ])
            ->join('siswa s', 's.id = cp.id_siswa');

        if (is_array($allowedStudentIds)) {
            if ($allowedStudentIds === []) {
                $builder->where('1 = 0', null, false);
            } else {
                $builder->whereIn('cp.id_siswa', $allowedStudentIds);
            }
        }

        if (!empty($filter['tingkat'])) {
            $builder->where('cp.tingkat', (string) $filter['tingkat']);
        }

        if (!empty($filter['tanggal_mulai'])) {
            $builder->where('cp.tanggal >=', (string) $filter['tanggal_mulai']);
        }

        if (!empty($filter['tanggal_selesai'])) {
            $builder->where('cp.tanggal <=', (string) $filter['tanggal_selesai']);
        }

        if (!empty($filter['search'])) {
            $search = trim((string) $filter['search']);
            $builder->groupStart()
                ->like('s.nama', $search)
                ->orLike('s.nisn', $search)
                ->orLike('cp.nama_prestasi', $search)
                ->orLike('cp.penyelenggara', $search)
                ->groupEnd();
        }

        return $builder;
    }
}
